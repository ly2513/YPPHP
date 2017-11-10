<?php
/**
 * User: yongli
 * Date: 17/8/27
 * Time: 16:00
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Thrift;

use Thrift\Transport\TSocket;

$loader = \Config\Services::thriftClassLoader();
$loader->registerNamespace('Services', \Config\ThriftClient::$genPath);
$loader->register();

/**
 * Class YP_ThriftClient
 *
 * @package YP\Libraries\Thrift
 */
class YP_ThriftClient
{
    /**
     * @var string
     */
    private static $thriftProtocol = 'TBinaryProtocol';

    /**
     * @var string
     */
    private static $thriftTransport = 'TBufferedTransport';

    /**
     * 客户端实例
     *
     * @var array
     */
    private static $instance = [];

    /**
     * 配置
     *
     * @var array
     */
    public static $config = [];

    /**
     * YP_ThriftClient constructor.
     *
     * @param \Config\ThriftClient $config
     */
    public function __construct(\Config\ThriftClient $config)
    {
        self::config($config);
        //                self::load();
    }

    /**
     * 设置/获取 配置
     *
     * @param $config
     *
     * @return array
     */
    public static function config($config)
    {
        if (is_object($config)) {
            // 赋值
            self::$config['host']       = $config->host;
            self::$config['port']       = $config->port;
            self::$config['genPath']    = \Config\ThriftClient::$genPath;
            self::$config['thriftPath'] = \Config\ThriftClient::$thriftPath;
            self::$thriftProtocol       = $config->thriftProtocol;
            self::$thriftTransport      = $config->thriftTransport;
            $address_map[]              = self::$config['host'] . ':' . self::$config['port'];
            YP_AddressManager::config($address_map);
        }

        return self::$config;
    }

    /**
     * 定义thrift的编译文件的命名空间
     */
    public static function load()
    {
        $loader = \Config\Services::thriftClassLoader();
        $loader->registerNamespace('Services', self::$config['genPath']);
        $loader->register();
    }

    /**
     * 获取实例
     *
     * @param $serviceName 服务名称
     * @param bool $newOne      是否强制获取一个新的实例
     *
     * @return mixed
     * @throws \Exception
     */
    public static function instance($serviceName, $newOne = false)
    {
        if (empty($serviceName)) {
            throw new \Exception('ServiceName can not be empty');
        }
        if ($newOne) {
            unset(self::$instance[$serviceName]);
        }
        if (! isset(self::$instance[$serviceName])) {
            self::$instance[$serviceName] = new ThriftInstance($serviceName);
        }

        return self::$instance[$serviceName];
    }

    /**
     * 获得通信协议
     *
     * @return string
     */
    public static function getProtocol()
    {
        return "\\Thrift\\Protocol\\" . self::$thriftProtocol;
    }

    /**
     * 获得通信方式
     *
     * @return string
     */
    public static function getTransport()
    {
        return "\\Thrift\\Transport\\" . self::$thriftTransport;
    }

    /**
     * 获得服务目录，用来查找thrift生成的客户端文件
     *
     * @param $serviceName
     *
     * @return string
     */
    public static function getServiceDir($serviceName)
    {
        $service_dir = self::$config['genPath'] . "Services/$serviceName/";

        return $service_dir;
    }
}

/**
 * thrift异步客户端实例
 *
 * @author liangl
 */
class ThriftInstance
{

    /**
     * 服务名
     *
     * @var string
     */
    public $serviceName = '';

    /**
     * thrift实例
     *
     * @var array
     */
    protected $thriftInstance = null;

    /**
     * 初始化工作
     *
     * ThriftInstance constructor.
     *
     * @param $serviceName
     */
    public function __construct($serviceName)
    {
        if (empty($serviceName)) {
            throw new \Exception('serviceName can not be empty', 500);
        }
        $this->serviceName = $serviceName;
    }

    /**
     * 方法调用
     *
     * @param $method_name
     * @param $arguments
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call($method_name, $arguments)
    {
        // 每次都重新创建一个实例
        $this->thriftInstance = $this->instance();
        $callback             = [
                                 $this->thriftInstance,
                                 $method_name,
                                ];
        if (! is_callable($callback)) {
            throw new \Exception($this->serviceName . '->' . $method_name . ' not callable', 1400);
        }
        // 调用客户端方法
        $ret = call_user_func_array($callback, $arguments);
        // 每次都销毁实例
        $this->thriftInstance = null;

        return $ret;
    }

    /**
     * 获取一个实例
     *
     * @return mixed
     * @throws \Exception
     */
    protected function instance()
    {
        // Transport
        $socket         = new TSocket(YP_ThriftClient::$config['host'], YP_ThriftClient::$config['port']);
        $transport_name = YP_ThriftClient::getTransport();
        $transport      = new $transport_name($socket);
        // Protocol
        $protocol_name = YP_ThriftClient::getProtocol();
        $protocol      = new $protocol_name($transport);
        try {
            $transport->open();
        } catch (\Exception $e) {
            // 无法连上，则踢掉这个地址
            YP_AddressManager::kickAddress(YP_ThriftClient::$config['host'] . ':' . YP_ThriftClient::$config['port']);
            //            throw $e;
        }
        // 客户端类名称
        $class_name = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . "Client";
        // 类不存在则尝试加载
        if (! class_exists($class_name)) {
            $service_dir = $this->includeFile();
            if (! class_exists($class_name)) {
                throw new \Exception('Class ' . $class_name . ' not found in directory ' . $service_dir);
            }
        }

        // 初始化一个实例
        return new $class_name($protocol);
    }

    /**
     * 载入thrift生成的客户端文件
     */
    protected function includeFile()
    {
        // 载入该服务下的所有文件
        $serviceDir = YP_ThriftClient::getServiceDir($this->serviceName);
        foreach (glob($serviceDir . '*.php') as $file) {
            require_once $file;
        }

        return $serviceDir;
    }
}
