<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 15:19
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

/**
 * 队列配置文件
 *
 * Class Queue
 *
 * @package Config
 */
class Queue
{
    /**
     * 队列驱动
     * 可能的值: rabbitmq,redis
     *
     * @var string
     */
    public $handlerDrive = 'rabbitmq';

    /**
     * 进程睡眠时间
     *
     * @var int
     */
    public $sleep = 1;

    /**
     * 指定当前的Worker只负责处理default队列 ,如果设置 * ,就是处理所有队列,也可以使用',',如'list1,list2,list3'。
     *
     * @var string
     */
    public $queue = 'default';

    /**
     * 设定Worker数量
     *
     * @var int
     */
    public $count = 1;

    /**
     * 设置日志目录
     *
     * @var string
     */
    public $logPath = CACHE_PATH . '/Log/';

    /**
     * 设置如果失败将执行的次数
     *
     * @var int
     */
    public $executionTimes = 3;

    /**
     * 研发组邮箱,用英文半角分号隔开
     *
     * @var string
     */
    public $emailGroup = '626375290@qq.com';

    /**
     * 队列任务处理目录
     *
     * @var string
     */
    public $jobPath = APP_PATH . 'ThirdParty/Queue/';

    /**
     * 配置数组
     *
     * @var array
     */
    public static $instance = null;

    /**
     * 处理缓存方式,key为类的别名,value为处理缓存的类
     *
     * @var array
     */
    //    public $validHandlers = [
    //        'rabbitmq' => \App\Libraries\Queue\Rabbitmq::class,
    //        'redis' => \YP\Libraries\Cache\YP_Redis::class,
    //    ];
    /**
     * Queue constructor.
     */
    public function __construct()
    {
        $this->getInstance();
    }

    /**
     * 获得队列配置
     */
    protected function getInstance()
    {
        if ($this->handlerDrive == 'rabbitmq') {
            switch (ENVIRONMENT) {
                case 'prod':
                    $config = [
                        'host'  => '127.0.0.1',  // 主机
                        'port'  => 5672,         // 端口
                        'user'  => 'username',   // 账号
                        'pass'  => 'password',   // 密码
                        'vhost' => '/'
                    ];
                    break;
                case 'test':
                    $config = [
                        'host'  => '127.0.0.1',  // 主机
                        'port'  => 5672,         // 端口
                        'user'  => 'username',   // 账号
                        'pass'  => 'password',   // 密码
                        'vhost' => '/'
                    ];
                    break;
                default:
                    $config = [
                        'host'  => '127.0.0.1',  // 主机
                        'port'  => 5672,         // 端口
                        'user'  => 'username',   // 账号
                        'pass'  => 'password',   // 密码
                        'vhost' => '/'
                    ];
                    break;
            };
        } else {
            // redis 配置
            $config = Redis::$redis;
        }
        self::$instance[$this->handlerDrive] = $config;
    }

}