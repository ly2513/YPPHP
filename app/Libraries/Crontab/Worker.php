<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Libraries\Crontab;

use Libraries\Crontab\Interfaces\WorkerInterface;
use Libraries\Crontab\Interfaces\MiddlewareInterface;

class Worker implements WorkerInterface
{
    /**
     * 休眠时间
     */
    const WORKER_USLEEP = 500000;

    /**
     * worker最大执行任务次数, 超过则重启,防止内存溢出
     * 
     * @var integer
     */
    const RUN_TASK_NUM = 10000;

    /**
     * 中间件
     * 
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * 任务集合
     * 
     * @var array
     */
    private $tasks = [];

    /**
     * 信号支持
     * 
     * @var array
     */
    private $signalSupport = [
        SIGHUP  => 'stop',
        SIGTERM => 'stop',
        SIGUSR1 => 'restart'
    ];

    /**
     * 处理任务次数
     * 
     * @var integer
     */
    private $execNum = 0;

    /**
     * 构造方法
     *
     * @param array $tasks 任务集合
     */
    public function __construct(array $tasks)
    {
        $this->tasks = $tasks;
    }

    /**
     * 设置通讯中间件
     *
     * @param MiddlewareInterface $middleware
     *
     * @return void
     */
    public function setMiddleware(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * 设置进程名称
     *
     * @param string $title
     *
     * @return void
     */
    public function setProcTitle($title)
    {
        if ($title && function_exists('cli_set_process_title')) {
            cli_set_process_title($title . '-worker');
        }
    }

    /**
     * 处理进程信号
     *
     * @return mixed
     */
    public function waitSign()
    {
        $sig = $this->middleware->pop(CronManager::QUEUE_SIG_VALUE);
        if ($sig !== false) {
            return call_user_func([$this, $this->signalSupport[$sig]], intval($sig));
        }
    }

    /**
     * 退出worker
     *
     * @param  integer $exitcode 退出码
     *
     * @return void
     */
    public function stop($exitcode)
    {
        exit($exitcode);
    }

    /**
     * 重启worker
     *
     * @param  integer $exitcode 退出码
     *
     * @return void
     */
    public function restart($exitcode)
    {
        exit($exitcode);
    }

    /**
     * worker主循环
     */
    public function loop()
    {
        while (1) {
            // 自动重启,防止内存溢出
            if ($this->execNum >= static::RUN_TASK_NUM) {
                $this->restart(SIGUSR1);
            }
            $this->waitSign();
            // 取队列任务ID
            if (($taskId = $this->middleware->pop(CronManager::QUEUE_TASK_ID)) !== false) {
                // 运行任务
                $task = $this->tasks[$taskId];
                $task->exec();
                $this->execNum++;
            }
            usleep(static::WORKER_USLEEP);

        }
    }

}