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
 *
 *
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
    public $class = '';

    /**
     * 日志对象
     *
     * @var null
     */
    public $log = null;

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
        $this->host            = $config->host;
        $this->port            = $config->port;
        $this->thriftProtocol  = $config->thriftProtocol;
        $this->thriftTransport = $config->thriftTransport;
        $this->log             = Services::log();

    }

    /**
     * 进程启动时做的一些初始化工作
     *
     * @throws \Exception
     */
    public function onStart()
    {
        // 检查类是否设置
        if (!$this->class) {
            throw new \Exception('ThriftWorker->class not set');
        }
        // 设置name
        if ($this->name == 'none') {
            $this->name = $this->class;
        }
        // 载入该服务下的所有文件
        foreach (glob(THRIFT_ROOT . '/Services/' . $this->class . '/*.php') as $php_file) {
            require_once $php_file;
        }
        // 检查类是否存在
        $processor_class_name = "\\Services\\" . $this->class . "\\" . $this->class . 'Processor';
        if (!class_exists($processor_class_name)) {
            $this->log->error("Class $processor_class_name not found");

            return;
        }
        // 检查类是否存在
        $handler_class_name = "\\Services\\" . $this->class . "\\" . $this->class . 'Handler';
        if (!class_exists($handler_class_name)) {
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
        //        $t_socket->setHandle($socket);
        $transport_name = '\\Thrift\\Transport\\' . $this->thriftTransport;
        $transport      = new $transport_name($t_socket);
        $protocol_name  = '\\Thrift\\Protocol\\' . $this->thriftProtocol;
        $protocol       = new $protocol_name($transport);
        
        $handler        = new CalculatorHandler();
        $processor      = new \tutorial\CalculatorProcessor($handler);
//        $transport      = new \Thrift\Transport\TBufferedTransport(new \Thrift\Transport\TPhpStream(\Thrift\Transport\TPhpStream::MODE_R | \Thrift\Transport\TPhpStream::MODE_W));
//        $protocol       = new \Thrift\Protocol\TBinaryProtocol($transport, true, true);
        $transport->open();
        $processor->process($protocol, $protocol);
        $transport->close();
    }

}




