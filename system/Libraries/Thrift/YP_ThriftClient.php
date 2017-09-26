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
use Thrift\ClassLoader\ThriftClassLoader;

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

    public static $loader;

    /**
     * 客户端实例
     * @var array
     */
    private static $instance = [];

    /**
     * 配置
     * @var array
     */
    private static $config = null;

    public function __construct(\Config\ThriftClient $config)
    {
        self::config($config);

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
            self::$config['genPath']    = $config->genPath;
            self::$config['thriftPath'] = $config->thriftPath;
            self::$thriftProtocol       = $config->thriftProtocol;
            self::$thriftTransport      = $config->thriftTransport;
            $address_map[]              = self::$config['host'] . ':' . self::$config['port'];
            AddressManager::config($address_map);
        }

        return self::$config;
    }

    public static function load()
    {
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Services', self::$config['genPath']);
//        $loader->registerDefinition('shared', $GEN_DIR);
//        $loader->registerDefinition('tutorial', $GEN_DIR);
        $loader->register();
        self::$loader['loader'] = $loader;
    }

    /**
     * 获取实例
     *
     * @param      $serviceName 服务名称
     * @param bool $newOne      是否强制获取一个新的实例
     *
     * @return mixed
     * @throws \Exception
     */
    //    public static function instance($serviceName, $newOne = false)
    //    {
    //        if (empty($serviceName)) {
    //            throw new \Exception('ServiceName can not be empty');
    //        }
    //        if ($newOne) {
    //            unset(self::$instance[$serviceName]);
    //        }
    //        if (!isset(self::$instance[$serviceName])) {
    //            self::$instance[$serviceName] = new ThriftInstance($serviceName);
    //        }
    //
    //        return self::$instance[$serviceName];
    //    }
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
     * @return string
     */
    public static function getServiceDir()
    {
        return self::$config['genPath'];
    }

    /**
     * 获取一个实例
     *
     * @return mixed
     * @throws \Exception
     */
    protected function __instance()
    {
        // 获取一个服务端节点地址
        //        $address = YP_AddressManager::getOneAddress($this->serviceName);
        //        list($ip, $port) = explode(':', $address);
        // Transport
        $socket         = new TSocket(self::$config['host'], self::$config['port']);
        $transport_name = YP_ThriftClient::getTransport($this->serviceName);
        $transport      = new $transport_name($socket);
        // Protocol
        $protocol_name = YP_ThriftClient::getProtocol($this->serviceName);
        $protocol      = new $protocol_name($transport);
        try {
            $transport->open();
        } catch (\Exception $e) {
            // 无法连上，则踢掉这个地址
            YP_AddressManager::kickAddress(self::$config['host'] . ':' . self::$config['port']);
            throw $e;
        }
        // 客户端类名称
        $class_name = "\\Services\\" . $this->serviceName . "\\" . $this->serviceName . "Client";
        // 类不存在则尝试加载
        if (!class_exists($class_name)) {
            $service_dir = $this->includeFile();
            if (!class_exists($class_name)) {
                throw new \Exception("Class $class_name not found in directory $service_dir");
            }
        }

        // 初始化一个实例
        return new $class_name($protocol);
    }

    /**
     * 载入thrift生成的客户端文件
     * @throws \Exception
     * @return void
     */
    protected function includeFile()
    {
        // 载入该服务下的所有文件
        $service_dir = YP_ThriftClient::getServiceDir();
        foreach (glob($service_dir . '/*.php') as $php_file) {
            require_once $php_file;
        }

        return $service_dir;
    }
}
