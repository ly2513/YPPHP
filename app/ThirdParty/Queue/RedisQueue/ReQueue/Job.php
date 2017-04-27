<?php
namespace Queue\RedisQueue\ReQueue;

use Queue\RedisQueue\ReQueue\Job\Status;
use Queue\RedisQueue\ReQueue\Job\DontPerform;
use Queue\RedisQueue\ReQueue\Failure;
use Queue\RedisQueue\ReQueue\Event;
use Queue\RedisQueue\Resque;
use InvalidArgumentException;
use Queue\RedisQueue\ReQueue\QueueException;
use TradingMax\Model\Job\FailJobModel;
use TradingMax\Model\Tools\EmailModel;

/**
 * Resque job.
 *
 * @package Resque/Job
 * @author  Chris Boulton <chris@bigcommerce.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class Job
{
    /**
     * @var string The name of the queue that this job belongs to.
     */
    public $queue;

    /**
     * @var Resque_Worker Instance of the Resque worker running this job.
     */
    public $worker;

    /**
     * @var object Object containing details of the job.
     */
    public $payload;

    /**
     * @var object Instance of the class performing work for this job.
     */
    private $instance;

    /**
     * Instantiate a new instance of a job.
     *
     * @param string $queue The queue that the job belongs to.
     * @param array  $payload array containing details of the job.
     */
    public function __construct($queue, $payload)
    {
        $this->queue   = $queue;
        $this->payload = $payload;
    }

    /**
     * Create a new job and save it to the specified queue.
     *
     * @param string  $queue string The name of the queue to place the job in.
     * @param string  $class The name of the class that contains the code to execute the job.
     * @param array   $args Any optional arguments that should be passed when the job is executed.
     * @param boolean $monitor Set to true to be able to monitor the status of a job.
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function create($queue, $class, $args = NULL, $monitor = FALSE)
    {
        if ($args !== NULL && !is_array($args)) {
            throw new InvalidArgumentException(
                'Supplied $args must be an array.'
            );
        }
        $id = md5(uniqid('', TRUE));
        Resque::push($queue, [
                              'class' => $class,
            //'args'  => [$args],
                              'args'  => $args,
                              'id'    => $id,
                             ]);

        if ($monitor) {
            Status::create($id);
        }

        return $id;
    }

    /**
     * Find the next available job from the specified queue and return an
     * instance of Job for it.
     *
     * @param  string $queue The name of the queue to check for a job in.
     * @return null|object Null when there aren't any waiting jobs, instance of Resque_Job when a job was found.
     */
    public static function reserve($queue)
    {
        $payload = Resque::pop($queue);
        if (!is_array($payload)) {
            return FALSE;
        }

        return new Job($queue, $payload);
    }

    /**
     * Update the status of the current job.
     *
     * @param int $status Status constant from Resque_Job_Status indicating the current status of a job.
     */
    public function updateStatus($status)
    {
        if (empty($this->payload['id'])) {
            return;
        }

        $statusInstance = new Status($this->payload['id']);
        $statusInstance->update($status);
    }

    /**
     * Return the status of the current job.
     *
     * @return int The status of the job as one of the Resque_Job_Status constants.
     */
    public function getStatus()
    {
        $status = new Status($this->payload['id']);
        return $status->get();
    }

    /**
     * Get the arguments supplied to this job.
     *
     * @return array Array of arguments.
     */
    public function getArguments()
    {
        if (!isset($this->payload['args'])) {
            return [];
        }

        //return $this->payload['args'][0];
        return $this->payload['args'];
    }

    /**
     * Get the instantiated object for this job that will be performing work.
     *
     * @return object
     * @throws \Queue\RedisQueue\ReQueue\QueueException
     */
    public function getInstance()
    {
        if (!is_null($this->instance)) {
            return $this->instance;
        }
        $class = ucfirst($this->payload['class']);
        if (!class_exists($class)) {
            require $_SERVER['JOBPATH'] . $class . '.php';
        }
        if (!class_exists($class)) {
            throw new QueueException(
                'Could not find job class ' . $class . '.'
            );
        }

        if (!method_exists($class, 'perform')) {
            throw new QueueException(
                'Job class ' . $class . ' does not contain a perform method.'
            );
        }

        $this->instance        = new $class();
        $this->instance->job   = $this;
        $this->instance->args  = $this->getArguments();
        $this->instance->queue = $this->queue;
        return $this->instance;
    }

    /**
     * Actually execute a job by calling the perform method on the class
     * associated with the job with the supplied arguments.
     *
     * @param  $jobId
     * @return bool
     * @throws \Queue\RedisQueue\ReQueue\QueueException
     */
    public function perform($jobId)
    {
        $instance = $this->getInstance();
        try {
            Event::trigger('beforePerform', $this);

            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }

            $instance->perform($jobId);

            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown();
            }

            Event::trigger('afterPerform', $this);
        } // beforePerform/setUp have said don't perform this job. Return.
        catch (DontPerform $e) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Mark the current job as having failed.
     *
     * @param $exception
     */
    public function fail($exception)
    {
        Event::trigger('onFailure', [
                                     'exception' => $exception,
                                     'job'       => $this,
                                    ]);

        $this->updateStatus(Status::STATUS_FAILED);
        // 记录失败队列信息
        Failure::create(
            $this->payload,
            $exception,
            $this->worker,
            $this->queue
        );
        // 向数据库写失败队列信息
        $data = [
                 'queue'       => $this->queue,
                 'job_id'      => $this->payload['args']['jobId'],
                 'failed_at'   => date('Y-m-d H:i:s', time()),
                 'payload'     => json_encode($this->payload),
                 'info'        => json_encode([
                                               'exception' => get_class($exception),
                                               'error'     => $exception->getMessage(),
                                               'info'      => explode("\n", $exception->getTraceAsString()),
                                              ]),
                 'create_time' => time(),
                 'update_time' => time(),
                ];

        FailJobModel::insertGetId($data);

        // 发邮件通知研发
        $email      = new EmailModel();
        $emailGroup = explode(';', $_SERVER['emailGroup']);
        $message    = '各位研发:<br/>本系统报表订阅出现异常,任务ID: ' . $this->payload['args']['jobId'] . '<br/>错误信息如下: <br/>';
        $errorInfo  = explode("\n", $exception->getTraceAsString());
        foreach ($errorInfo as $info) {
            $message .= $info;
        }
        $email->sendEmail($message, $emailGroup, 'TradingMax平台活动报告异常');
        unset($data, $emailGroup, $message, $errorInfo);
        Stat::incr('failed');
        Stat::incr('failed:' . $this->worker);
    }

    /**
     * Re-queue the current job.
     *
     * @return string
     */
    public function recreate()
    {
        $status  = new Status($this->payload['id']);
        $monitor = FALSE;
        if ($status->isTracking()) {
            $monitor = TRUE;
        }

        return self::create($this->queue, $this->payload['class'], $this->payload['args'], $monitor);
    }

    /**
     * Generate a string representation used to describe the current job.
     *
     * @return string The string representation of the job.
     */
    public function __toString()
    {
        $name = ['Job{' . $this->queue . '}'];
        if (!empty($this->payload['id'])) {
            $name[] = 'ID: ' . $this->payload['id'];
        }
        $name[] = $this->payload['class'];
        if (!empty($this->payload['args'])) {
            $name[] = json_encode($this->payload['args']);
        }
        return '(' . implode(' | ', $name) . ')';
    }
}

?>
