<?php
/**
 * User: yongli
 * Date: 17/8/27
 * Time: 16:00
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Thrift;

use Config\Services;
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Transport\TSocket;

define('GEN_DIR', APP_PATH . 'ThirdParty/Thrift/gen-php');
$loader = new ThriftClassLoader();
$loader->registerNamespace('Service', GEN_DIR);
$loader->registerDefinition('shared', GEN_DIR);
$loader->registerDefinition('tutorial', GEN_DIR);
$loader->register();

/**
 * Class ThriftService
 *
 * @package YP\Libraries\Thrift
 */
class YP_ThriftService
{
    /**
     * IP
     *
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * 服务端口
     *
     * @var int
     */
    public $port = 9090;

    /**
     * Thrift 处理对象
     *
     * @var object
     */
    protected $processor = null;

    /**
     * 使用的协议,默认TBinaryProtocol,可更改
     *
     * @var string
     */
    public $thriftProtocol = 'TBinaryProtocol';

    /**
     * 使用的传输类,默认是TBufferedTransport，可更改
     *
     * @var string
     */
    public $thriftTransport = 'TBufferedTransport';

    /**
     * 设置类名称
     *
     * @var string
     */
    public static $class = '';

    /**
     * 日志对象
     *
     * @var null
     */
    public $log = null;

    /**
     * 服务文件路径
     *
     * @var
     */
    public $servicePath;

    /**
     * 统计数据上报的地址
     *
     * @var string
     */
    public $statisticAddress = 'udp://127.0.0.1:55656';

    /**
     * ThriftService constructor.
     *
     * @param \Config\ThriftService $config
     */
    public function __construct(\Config\ThriftService $config)
    {
        $this->config($config);
        //        call_user_func('onStart', $this);
        //        call_user_func('onConnect', $this);
    }

    /**
     * 初始化配置
     *
     * @param $config
     */
    protected function config($config)
    {
        if (is_object($config)) {
            $this->host            = $config->host;
            $this->port            = $config->port;
            $this->thriftProtocol  = $config->thriftProtocol;
            $this->thriftTransport = $config->thriftTransport;
            $this->log             = Services::log();
            $this->servicePath     = \Config\ThriftService::$genDir;
        }
    }

    /**
     * 进程启动时做的一些初始化工作
     *
     * @param $class
     *
     * @throws \Exception
     */
    public function onStart($class)
    {
        // 检查类是否设置
        if (! $class) {
            throw new \Exception('ThriftWorker->class not set');
        }
        self::$class = $class;
        // 设置name
        if ($this->name == 'none') {
            $this->name = self::$class;
        }
        // 载入该服务下的所有文件
        $this->includeFile();
        // 检查类是否存在
        $processor_class_name = "\\Services\\" . self::$class . "\\" . self::$class . 'Processor';
        if (! class_exists($processor_class_name)) {
            $this->log->error("Class $processor_class_name not found");

            return;
        }
        // 检查类是否存在
        $handler_class_name = "\\Services\\" . self::$class . "\\" . self::$class . 'Handler';
        if (! class_exists($handler_class_name)) {
            $this->log->error("Class $handler_class_name not found");

            return;
        }
        $handler         = new $handler_class_name();
        $this->processor = new $processor_class_name($handler);
    }

    /**
     * 处理接收到的数据
     */
    public function onConnect()
    {
        $t_socket = new TSocket($this->host, $this->port);
        $t_socket->setSendTimeout(10000);#Sets the send timeout.
        $t_socket->setRecvTimeout(20000);
        //        $t_socket->setHandle($socket);
        $transport_name = '\\Thrift\\Transport\\' . $this->thriftTransport;
        $transport      = new $transport_name($t_socket);
        $protocol_name  = '\\Thrift\\Protocol\\' . $this->thriftProtocol;
        $protocol       = new $protocol_name($transport);
        $transport->open();
        $this->processor->process($protocol, $protocol);
        $transport->close();
        //        $handlerClass   = "\\Services\\" . self::$class . "\\" . self::$class . 'Handler';
        //        $handler        = new $handlerClass();
        //        $processorClass = "\\Services\\" . self::$class . "\\" . self::$class . 'Processor';
        //        $processor      = new $processorClass($handler);
        //        $transport->open();
        //        $processor->process($protocol, $protocol);
        //        $transport->close();
    }

    /**
     * 载入thrift生成的客户端文件
     */
    protected function includeFile()
    {
        // 载入该服务下的所有文件
        foreach (glob($this->servicePath . 'Services/' . $this->class . '/*.php') as $file) {
            require_once $file;
        }
    }
}
