<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 15:06
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Libraries\Queue;

use RedisQueue\ResQueue;

/**
 * Class Queue
 *
 * @package App\Libraries\Queue
 */
class YP_Queue {

    /**
     * 队列驱动
     * 可能的值: rabbitmq,redis
     *
     * @var string
     */
    private $handlerDrive = 'rabbitmq';

    /**
     * 进程睡眠时间
     *
     * @var int
     */
    private $sleep = 1;

    /**
     * 指定当前的Worker只负责处理default队列 ,如果设置 * ,就是处理所有队列,也可以使用',',如'list1,list2,list3'。
     *
     * @var string
     */
    private $queue = 'default';

    /**
     * 设定Worker数量
     *
     * @var int
     */
    private $count = 1;

    /**
     * 设置日志目录
     *
     * @var string
     */
    private $logPath = CACHE_PATH . 'Log' . DIRECTORY_SEPARATOR;

    /**
     * 设置如果失败将执行的次数
     *
     * @var int
     */
    private $executionTimes = 3;

    /**
     * 研发组邮箱,用英文半角分号隔开
     *
     * @var string
     */
    private $emailGroup = '';

    /**
     * 队列任务处理目录
     *
     * @var string
     */
    private $jobPath = APP_PATH . 'ThirdParty/Queue' . DIRECTORY_SEPARATOR;

    /**
     * 配置数组
     *
     * @var array
     */
    public static $instance = NULL;

    /**
     * Queue constructor.
     *
     * @param \Config\Queue $config
     */
    public function __construct(\Config\Queue $config)
    {
        // 初始化配置
        $this->_initConfig($config);
        $this->setInstance();
        P(self::$instance);
    }

    /**
     * 出队
     */
    public function pull()
    {
        //        ResQueue::
    }

    /**
     * 入队
     */
    public function push()
    {

    }

    /**
     *
     */
    public function setInstance()
    {
        self::$instance = "new App\\Libraries\\Queue\\YP_{$this->handlerDrive}";
    }

    /**
     * 初始化配置
     *
     * @param \Config\Queue $config
     */
    private function _initConfig(\Config\Queue $config)
    {
        $this->handlerDrive   = $config->handlerDrive;
        $this->sleep          = $config->sleep;
        $this->queue          = $config->queue;
        $this->count          = $config->count;
        $this->logPath        = $config->logPath;
        $this->executionTimes = $config->executionTimes;
        $this->emailGroup     = $config->emailGroup;
        $this->jobPath        = $config->jobPath;
    }
}
