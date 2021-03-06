<?php
/**
 * User: yongli
 * Date: 16/9/28
 * Time: 下午12:43
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Console\Queue;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RedisQueue\ResQueue;
use RedisQueue\ReQueue\Log;
use RedisQueue\ReQueue\QueueException;

/**
 * 创建队列任务
 *
 * Class CreateJobCommand
 *
 * @package Console
 */
class CreateCommand extends QueueCommand
{
    /**
     * 日志对象
     *
     * @var null|Log
     */
    private $log = null;

    /**
     * CreateJobCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->log = new Log();
    }

    /**
     * 命令配置
     */
    protected function configure()
    {
        $this->setName('queue:create')->setDescription('Create a queue job with the redis.')->setDefinition([
            new InputOption('job-name', 'j', InputOption::VALUE_REQUIRED, 'Create a queue job name.'),
            new InputOption('job-describe', 'd', InputOption::VALUE_NONE, 'Describe the function of the queue.'),
            new InputOption('queue-name', null, InputOption::VALUE_NONE, 'queue name.'),
            new InputOption('redis-host', 'rh', InputOption::VALUE_NONE, 'Redis service host.'),
            new InputOption('redis-port', 'rp', InputOption::VALUE_NONE, 'Redis service port.'),
        ]);
    }

    /**
     * 命令操作
     *
     * @param InputInterface  $input  命令的输入
     * @param OutputInterface $output 命令的输出
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = $input->getOption('job-name');
        $jobDir  = $_SERVER['JOBPATH'];
        is_dir($jobDir) or mkdir($jobDir, 0777, true);
        $host         = $input->getOption('redis-host');
        $port         = $input->getOption('redis-port');
        $host         = $host ? $host : '127.0.0.1';
        $port         = $port ? $port : 6379;
        $redisServer  = $host . ':' . $port;
        $redisBackEnd = $_SERVER['REDIS_BACKEND'];
        $redisBackEnd ? ResQueue::setBackend($redisBackEnd) : ResQueue::setBackend($redisServer);
        $queueName = $input->getOption('queue-name');
        $queueName = $queueName ? $queueName : 'default';
        $payload   = ['class' => 'sentEmail', 'data' => []];
        if (!$jobName) {
            $jobName     = $payload['class'] ? $payload['class'] : 'default';
            $description = $input->getOption('job-describe');
            $description = $description ? $description : 'Describe the function of the queue';
            //
            $args = [
                'time'  => time(),
                'array' => [
                    'test' => $description,
                ],
            ];
            $args = $payload['data'] ? $payload['data'] : $args;
            try {
                // 队列ID
                $jobId = ResQueue::enqueue($queueName, $jobName . 'Job', $args, true);
                $this->log->writeLog('Create queue job success, the queue job id is ' . $jobId);
                $output->writeln(sprintf('Create queue job success, the queue job id is "<info>%s</info>"', $jobId));
                // 单独创建任务处理程序
                $this->_createJob($jobDir, $jobName);

                return true;
            } catch (\InvalidArgumentException $e) {
                $this->log->writeLog('Create queue job error, the error message is ' . $e->getMessage());
                $output->writeln(sprintf('Create queue job error, the error message is "<info>%s</info>"',
                    $e->getMessage()));

                return false;
            } catch (QueueException $e) {
                $this->log->writeLog('Create queue job error, the error message is ' . $e->getMessage());
                $output->writeln(sprintf('Create queue job error, the error message is "<info>%s</info>"',
                    $e->getMessage()));

                return false;
            }
        }
    }

    /**
     * 创建任务
     *
     * @param $jobDir  队列任务的目录
     * @param $jobName 队列任务
     *
     * @return bool
     */
    private function _createJob($jobDir, $jobName)
    {
        $jobName = $jobName ? $jobName : 'default';
        $jobFile = $jobDir . ucfirst($jobName) . 'Job.php';
        if (!file_exists($jobFile)) {
            touch($jobFile);
            $str = <<<EOT
<?php 
/**
 * 
 *
 */
 namespace Queue\Job;
 
 
class 
EOT;
            $str .= ucfirst($jobName . 'Job') . PHP_EOL;
            $str .= <<<EOT
{
    protected \$email;
    
    /**
     * 运行任务逻辑代码
     *
     */
    public function perform()
    {
        // 在此方法中书写逻辑代码
        
        
        
    }
    
}
EOT;
            $status = file_put_contents($jobFile, $str);

            return $status ? true : false;
        }

        return true;
    }
}

