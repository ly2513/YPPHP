<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Libraries\Crontab;

use Libraries\Crontab\Middlewares\IpcMessageQueue;
use Libraries\Crontab\ConsoleManager;
use Libraries\Crontab\Worker;

/**
 * 定时任务调度类
 *
 * Class CronManager
 */
class CronManager
{
    const VERSION = '1.5.0';

    /**
     *  标识任务ID
     *
     * @var integer
     */
    const QUEUE_TASK_ID = 1;

    /**
     * 标识任务状态
     *
     * @var integer
     */
    const QUEUE_TASK_STATUS = 2;

    /**
     * 标识任务状态
     *
     * @var integer
     */
    const QUEUE_SIG_VALUE = 3;

    /**
     * 用于接收命令行特别指令, 如stop:n|STOP:n|start:n
     *
     * @var integer
     */
    const QUEUE_COMMAND_VALUE = 4;

    /**
     * 主进程运行状态
     *
     * @var integer
     */
    const MASTER_STATUS_RUN     = 0; //正常
    const MASTER_STATUS_STOP    = 1; //停止中
    const MASTER_STATUS_RESTART = 2; //重启中

    /**
     * 默认中间件
     */
    const DEFAULT_MIDDLEWARE = "Libraries\\Crontab\\Middlewares\\IpcMessageQueue";

    /**
     * 日志文件
     *
     * @var string
     */
    public static $logFile = '';

    /**
     * 主进程运行状态
     *
     * @var integer
     */
    private $status;

    /**
     * 任务集合
     *
     * @var array
     */
    private $tasks = [];

    /**
     * worker集合
     *
     * @var array
     */
    private $workers = [];

    /**
     * 消息队列中间件
     *
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * 信号支持
     *
     * @var array
     */
    private $signalSupport = [
        'stop'    => SIGHUP, //平滑停止
        'STOP'    => SIGTERM, //强行停止
        'restart' => SIGUSR1 //重启worker
    ];

    /**
     * 任务运行文件存放目录
     *
     * @var string
     */
    public $managerDir = '';

    /**
     * 任务主进程管理文件
     *
     * @var string
     */
    public $pidFile = '';

    /**
     * 任务状态记录文件
     *
     * @var string
     */
    public $taskStatusFile = '';

    /**
     * worker数
     *
     * @var integer
     */
    public $workerNum = 1;

    /**
     * 最大worker数
     *
     * @var integer
     */
    public $workerMax = 1024;

    /**
     * 是否守护进程化
     *
     * @var boolean
     */
    public $daemon = false;

    /**
     * 重定向输出,守护进程化时
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * 启动时间
     *
     * @var string
     */
    public $startTime = '';

    /**
     * 命令参数. 默认从控制台接收,仅支持单参数
     *
     * @var string
     */
    public $argv = '';

    /**
     * 进程名称
     *
     * @var string
     */
    public $procTitle = '';

    /**
     * 架构入口
     *
     * @return void
     */
    public function run()
    {
        $this->init();
        $this->parseArgv();
        $this->daemonize();
        $this->registerSignal();
        $this->loop();
    }

    /**
     * 初始化中间件
     */
    private function init()
    {
        $requireFile = static::requireFile();
        if (!$this->managerDir) {
            $this->managerDir = '/tmp/';
        }
        !is_dir($this->managerDir) && mkdir($this->managerDir, 0755, true);
        $prefix               = 'cron-manager-' . substr(md5($requireFile), 0, 16);
        $this->startTime      = date('Y-m-d H:i:s');
        $this->pidFile        = $this->managerDir . $prefix . '.pid';
        $this->taskStatusFile = $this->managerDir . $prefix . '.status';
        if (!static::$logFile) {
            static::$logFile = $this->managerDir . $prefix . '.log';
        }
        if (!$this->middleware) {
            $this->middleware = new IpcMessageQueue(ftok($requireFile, 'a'));
        }
        $this->status = static::MASTER_STATUS_RUN;
        if ($this->procTitle && function_exists('cli_set_process_title')) {
            cli_set_process_title($this->procTitle . '-master');
        }
    }

    /**
     * 解析命令行
     */
    private function parseArgv()
    {
        global $argv;
        $command = $this->argv !== '' ? $this->argv : @$argv[1];
        if (strpos($command, ':') !== false) {
            $this->middleware->push(static::QUEUE_COMMAND_VALUE, $command);
            exit();
        }
        // start
        if (!$command) {
            return;
        }
        switch ($command) {
            // 守护进程化
            case 'd':
                $this->daemon = true;
                break;
            // 停止
            case 'stop':
                $pid = intval(@file_get_contents($this->pidFile));
                posix_kill($pid, $this->signalSupport['stop']);
                echo "\33[KStoping cron-manager:\t\t[\033[40;33m WAIT \033[0m]\r";
                while (file_exists($this->pidFile)) {
                }
                exit("\33[KStoping cron-manager:\t\t[\033[40;33m OK \033[0m]\n");
                break;
            case 'STOP':
                $pid = intval(@file_get_contents($this->pidFile));
                posix_kill($pid, $this->signalSupport['STOP']);
                exit("STOPING cron-manager:\t\t[\033[40;33m OK \033[0m]\n");
                break;
            // 重启worker
            case 'restart':
                $pid = intval(@file_get_contents($this->pidFile));
                posix_kill($pid, $this->signalSupport['restart']);
                exit("Restarting cron-manager:\t\t[\033[40;33m OK \033[0m]\n");
                break;
            // 查看任务状态
            case 'status':
                if (file_exists($this->taskStatusFile)) {
                    exit(file_get_contents($this->taskStatusFile));
                }
                exit("\033[40;33m Faild: $this->taskStatusFile not found!  \033[0m \n");
                break;
            // 打印log
            case 'log':
                if (file_exists(static::$logFile)) {
                    exit(file_get_contents(static::$logFile));
                }
                exit("\033[40;33m Faild: " . static::$logFile . " not found!  \033[0m \n");
                break;
            // 检测运行环境
            case 'check':
                exit(ConsoleManager::checkExtensions());
                break;
            default:
                exit("\033[40;33m Faild: No support for parameter '$command'  \033[0m\n");
                break;
        }
    }

    /**
     * 守护进程化
     */
    private function daemonize()
    {
        if (!$this->daemon) {
            return;
        }
        $this->initFiles();
        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) {
            exit("\033[40;33m Faild: fork fail!  \033[0m \n");
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            exit("\033[40;33m Faild: setsid fail!  \033[0m \n");
        }
        $pid = pcntl_fork();
        if (-1 === $pid) {
            exit("\033[40;33m Faild: fork fail!  \033[0m \n");
        } elseif (0 !== $pid) {
            exit(0);
        }
        // 创建pid文件
        file_put_contents($this->pidFile, getmypid());
        echo "Starting cron-manager:\t\t[ \033[40;33m OK \033[0m ]\n";
        $this->resetStd();
    }

    /**
     * 初始化守护进程化后的所需文件
     */
    private function initFiles()
    {
        if (file_exists($this->pidFile)) {
            $text = "Starting cron-manager:\t[ \033[40;33m NO \033[0m ]\n\n\033[40;31m\tFaild: {$this->pidFile} already exist!\n\033[0m";
            exit($text);
        }
        @touch($this->taskStatusFile) && chmod($this->taskStatusFile, 0755);
        // 重置LOG日志
        file_exists(static::$logFile) && @unlink(static::$logFile);
    }

    /**
     * 重定向输出
     * @return void
     */
    private function resetStd()
    {
        global $stdin, $stdout, $stderr;
        //关闭打开的文件描述符
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        if ($this->output != '/dev/null' && !file_exists($this->output)) {
            touch($this->output);
            chmod($this->output, 0755);
            $this->output = realpath($this->output);
        }
        $stdin  = fopen($this->output, 'r');
        $stdout = fopen($this->output, 'a');
        $stderr = fopen($this->output, 'a');
    }

    /**
     * 启动worker
     * @return void
     */
    private function startWorkers()
    {
        for ($i = 0; $i < $this->workerNum; $i++) {
            $this->forkWorker();
        }
    }

    /**
     * 创建worker进程
     * @return void
     */
    private function forkWorker()
    {
        if (count($this->workers) < $this->workerMax) {
            $pid = pcntl_fork();
            switch ($pid) {
                case -1:
                    exit;
                    break;
                case 0:
                    $worker = new Worker($this->tasks);
                    $worker->setProcTitle($this->procTitle);
                    $worker->setMiddleware($this->middleware);
                    $worker->loop();
                    break;
                default:
                    static::log('debug', '[' . $pid . '] 创建worker成功');
                    $this->workers[$pid] = [];
                    break;
            }
        }
    }

    /**
     * 注册信号
     * @return vold
     */
    private function registerSignal()
    {
        foreach ($this->signalSupport as $v) {
            pcntl_signal($v, [$this, 'dispatchSign'], false);
        }
    }

    /**
     * 触发信号
     *
     * @param $sign 信号量
     *
     * @return vold
     */
    private function dispatchSign($sign)
    {
        switch ($sign) {
            //平滑停止
            case $this->signalSupport['stop']:
                $this->status = static::MASTER_STATUS_STOP;
                for ($i = 0; $i < count($this->workers); $i++) {
                    $this->middleware->push(CronManager::QUEUE_SIG_VALUE, $this->signalSupport['stop']);
                }
                break;
            //强行停止
            case $this->signalSupport['STOP']:
                $this->status = static::MASTER_STATUS_STOP;
                foreach ($this->workers as $pid => $v) {
                    posix_kill($pid, SIGKILL);
                    unset($this->workers[$pid]);
                }
                $this->clear();
                break;
            //通知worker进程重启
            case $this->signalSupport['restart']:
                $this->status = static::MASTER_STATUS_RESTART;
                for ($i = 0; $i < count($this->workers); $i++) {
                    $this->middleware->push(CronManager::QUEUE_SIG_VALUE, $this->signalSupport['restart']);
                }
                break;
            default:
                return;
                break;
        }
    }

    /**
     * 处理命令队列里任务指令
     * @return void
     */
    public function execTaskCommand()
    {
        $command = $this->middleware->pop(static::QUEUE_COMMAND_VALUE);
        if ($command !== false) {
            $mapTaskStatus = function ($taskIds, $status) {
                foreach ($taskIds as $id) {
                    if (isset($this->tasks[$id])) {
                        // 删除
                        if ($status == 2) {
                            unset($this->tasks[$id]);
                        } else {
                            $this->tasks[$id]->setStatus($status);
                        }
                    }
                }
            };
            static::log('debug', '接收到任务指令: ' . $command);
            if (strpos($command, ':') === false) {
                return static::log('debug', '无法解析任务指令: ' . $command);
            }
            list($tag, $taskIds) = explode(':', $command);
            $taskIds = explode(',', $taskIds);
            switch ($tag) {
                // 将任务状态置为开始
                case 'start':
                    $mapTaskStatus($taskIds, 0);
                    break;
                // 将任务状态置为关闭
                case 'stop':
                    $mapTaskStatus($taskIds, 1);
                    break;
                // 删除某个任务
                case 'STOP':
                    $mapTaskStatus($taskIds, 2);
                    break;
                // 手动运行一次任务,不影响原执行时间
                case 'run':
                    foreach ($taskIds as $taskId) {
                        $this->middleware->push(static::QUEUE_TASK_ID, $taskId);
                        $this->tasks[$taskId]->count++;
                    }
                    break;
                default:
                    static::log('debug', '无法解析任务指令: ' . $command);
                    break;
            }

        }
    }

    /**
     * master进程主循环
     * @return void
     */
    private function loop()
    {
        static::log('debug', 'master进程启动');
        $this->startWorkers();
        while (1) {
            pcntl_signal_dispatch();
            // 处理任务指令,如停止,开始某个任务
            $this->execTaskCommand();
            if ($this->status == static::MASTER_STATUS_RUN) {
                $queueNum = $this->middleware->getMessageNum();
                if ($queueNum !== 0) {
                    $queueStr = '列队待处理任务数: ' . $queueNum;
                    // 扩容
                    if ($queueNum >= 5) {
                        $queueStr .= ' 满足条件: [自动扩容]';
                        $this->forkWorker();
                    }
                    static::log('debug', $queueStr);
                }
                foreach ($this->tasks as $id => &$task) {
                    // 任务是否可运行
                    if ($task->valid()) {
                        // 计算下次运行时间
                        $task->calcNextTime();
                        // 向消息队列写任务ID
                        $this->middleware->push(CronManager::QUEUE_TASK_ID, $id);
                        $task->count++;
                    }
                }
                // 将任务运行状态记录进文件
                file_put_contents($this->taskStatusFile, ConsoleManager::taskStatusTable($this->tasks, [
                    'version'    => static::VERSION,
                    'pid'        => getmypid(),
                    'output'     => $this->output,
                    'task_num'   => count($this->tasks),
                    'worker_num' => count($this->workers),
                    'queue_num'  => $queueNum,
                    'start_time' => $this->startTime
                ]));

            }
            foreach ($this->workers as $pid => $workerStatus) {
                $pid = pcntl_wait($status, WNOHANG);
                if ($pid > 0) {
                    unset($this->workers[$pid]);
                    $exit = pcntl_wexitstatus($status);
                    // 重启worker
                    if ($exit == $this->signalSupport['restart']) {
                        static::log('debug', '[' . $pid . '] 退出重启');
                        $this->forkWorker();
                        $this->status = static::MASTER_STATUS_RUN;
                    } else {
                        static::log('debug', '[' . $pid . '] 退出');
                    }
                }
            }
            // 所有worker已退出
            if (empty($this->workers)) {
                $this->clear();
                break;
            }
            sleep(1);
        }
        static::log('debug', 'master进程退出');
    }

    /**
     * 清理残留文件
     * @return void
     */
    private function clear()
    {
        $this->middleware->close();
        @unlink($this->pidFile);
        @unlink($this->taskStatusFile);
    }

    /**
     * 设置中间件
     *
     * @param MiddlewareInterface $middleware 消息队列中间件
     */
    public function setMiddleware(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * 添加定时任务
     *
     * @param  string   $name
     * @param           string /array  $intvalTag
     * @param  callable $callable
     * @param  array    $ticks 进程分片
     */
    public function taskInterval($name, $intvalTag, callable $callable, array $ticks = [])
    {
        if (!empty($ticks)) {
            foreach ($ticks as $tick) {
                $t                        = new Task($name, $intvalTag, $callable, $tick);
                $this->tasks[$t->getId()] = $t;
            }
        } else {
            $t                        = new Task($name, $intvalTag, $callable);
            $this->tasks[$t->getId()] = $t;
        }
    }

    /**
     * 获取唯一实例ID,借鉴workerman
     *
     * @return string
     */
    public static function requireFile()
    {
        $backtrace   = debug_backtrace();
        $requireFile = $backtrace[count($backtrace) - 1]['file'];
        return str_replace('_', '/', $requireFile);
    }

    /**
     * 记录日志
     *
     * @param  string $tag     日志标识
     * @param  string $message 内容
     */
    public static function log($tag, $message)
    {
        $datetime = date('Y-m-d H:i:s');
        $template = $datetime . " PID:%d [%s] %s\n";
        file_put_contents(static::$logFile, sprintf($template, getmypid(), $tag, $message), FILE_APPEND);
    }
}