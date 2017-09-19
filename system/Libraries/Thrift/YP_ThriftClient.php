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

define('THRIFT_CLIENT', APP_PATH . 'ThirdParty/Thrift/');

/**
 *
 * 通用客户端,支持故障ip自动踢出及探测节点是否已经存活
 *
 * <b>使用示例:</b>
 * <pre>
 * <code>
 * use YP_ThriftClient;
 *
 * // 传入配置，一般在某统一入口文件中调用一次该配置接口即可
 * YP_ThriftClient::config(array(
 * 'HelloWorld' => array(
 * 'addresses' => array(
 * '127.0.0.1:9090',
 * '127.0.0.2:9191',
 * ),
 * 'thrift_protocol' => 'TBinaryProtocol',//不配置默认是TBinaryProtocol，对应服务端HelloWorld.conf配置中的thrift_protocol
 * 'thrift_transport' => 'TBufferedTransport',//不配置默认是TBufferedTransport，对应服务端HelloWorld.conf配置中的thrift_transport
 * ),
 * 'UserInfo' => array(
 * 'addresses' => array(
 * '127.0.0.1:9393'
 * ),
 * ),
 * )
 * );
 * // =========  以上在WEB入口文件中调用一次即可  ===========
 *
 *
 * // =========  以下是开发过程中的调用示例  ==========
 *
 * // 初始化一个HelloWorld的实例
 * $client = YP_ThriftClient::instance('HelloWorld');
 *
 * // --------同步调用实例----------
 * var_export($client->sayHello("TOM"));
 *
 * // --------异步调用示例-----------
 * // 异步调用 之 发送请求给服务端（注意：异步发送请求格式统一为 asend_XXX($arg),既在原有方法名前面增加'asend_'前缀）
 * $client->asend_sayHello("JERRY");
 * $client->asend_sayHello("KID");
 *
 * // 这里是其它业务逻辑
 * sleep(1);
 *
 * // 异步调用 之 接收服务端的回应（注意：异步接收请求格式统一为 arecv_XXX($arg),既在原有方法名前面增加'arecv_'前缀）
 * var_export($client->arecv_sayHello("KID"));
 * var_export($client->arecv_sayHello("JERRY"));
 *
 * <code>
 * </pre>
 *
 *
 */
class YP_ThriftClient
{
    /**
     * 协议
     *
     * @var string
     */
    private static $thriftProtocol = 'TBinaryProtocol';

    /**
     * 传输方式
     *
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
    private static $config = [];

    /**
     * socket对象
     *
     * @var null|TSocket
     */
    private static $socket = null;

    /**
     * 传输对象
     *
     * @var null|TBufferedTransport
     */
    private static $transport = null;

    /**
     * @var null
     */

    /**
     * 协议对象
     *
     * @var null|TBinaryProtocol
     */
    private static $protocol = null;

    /**
     * 故障节点共享内存fd
     *
     * @var resource
     */
    private static $badAddressShmFd = null;

    /**
     * 故障的节点列表
     *
     * @var array
     */
    private static $badAddressList = null;

    /**
     * YP_ThriftClient constructor.
     *
     * @param \Config\ThriftClient $config
     */
    public function __construct(\Config\ThriftClient $config)
    {
        self::$config['host']            = $config->host;
        self::$config['port']            = $config->port;
        self::$config['genDir']          = $config->genDir;
        self::$config['time_out']        = $config->time_out;
        self::$config['thriftProtocol']  = $config->thriftProtocol;
        self::$config['thriftTransport'] = $config->thriftTransport;
        self::$thriftProtocol            = $config->thriftProtocol;
        self::$thriftTransport           = $config->thriftTransport;
        self::$socket                    = new TSocket(self::$config['host'], self::$config['port']);
        self::$socket->setSendTimeout(self::$config['time_out']);
        self::$socket->setRecvTimeout(self::$config['time_out']);
        self::$transport = new self::getProtocol()(self::$socket);
        self::$protocol  = new self::getTransport()(self::$transport);
    }

    /**
     * 设置/获取 配置
     *
     * @return array
     */
    public static function config()
    {
        if (!empty(self::$config)) {
            // 注册address到AddressManager
            $address_map[] = self::$config['host'] . ':' . self::config['port'];
            YP_AddressManager::config($address_map);
        }

        return self::$config;
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
    public static function instance($serviceName, $newOne = false)
    {
        if (empty($serviceName)) {
            throw new \Exception('ServiceName can not be empty');
        }
        if ($newOne) {
            unset(self::$instance[$serviceName]);
        }
        if (!isset(self::$instance[$serviceName])) {
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
        $config = self::config();
        if (!empty($config['thrift_protocol'])) {
            self::$thriftProtocol = $config['thrift_protocol'];
        }

        return "\\Thrift\\Protocol\\" . self::$thriftProtocol;
    }

    /**
     * 获得通信方式
     *
     * @return string
     */
    public static function getTransport()
    {
        $config = self::config();
        if (!empty($config['thrift_transport'])) {
            self::$thriftTransport = $config['thrift_transport'];
        }

        return "\\Thrift\\Transport\\" . self::$thriftTransport;
    }

    /**
     * 获得服务目录，用来查找thrift生成的客户端文件
     *
     * @param string $service_name
     *
     * @return string
     */
    public static function getServiceDir($service_name)
    {
        $config = self::config();
        if (!empty($config['genDir'])) {
            $service_dir = $config['genDir'] . '/' . $service_name;
        } else {
            $service_dir = THRIFT_CLIENT . $service_name;
        }

        return $service_dir;
    }
}

/**
 * thrift异步客户端实例
 *
 * Class ThriftInstance
 *
 * @package YP\Libraries\Thrift\Client
 */
class ThriftInstance
{
    /**
     * 异步发送前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步接收后缀
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    /**
     * 服务名
     * @var string
     */
    public $serviceName = '';

    /**
     * thrift实例
     * @var array
     */
    protected $thriftInstance = null;

    /**
     * thrift异步实例['asend_method1'=>thriftInstance1, 'asend_method2'=>thriftInstance2, ..]
     * @var array
     */
    protected $thriftAsyncInstances = [];

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
        // 异步发送
        if (0 === strpos($method_name, self::ASYNC_SEND_PREFIX)) {
            $real_method_name = substr($method_name, strlen(self::ASYNC_SEND_PREFIX));
            $arguments_key    = serialize($arguments);
            $method_name_key  = $method_name . $arguments_key;
            // 判断是否已经有这个方法的异步发送请求
            if (isset($this->thriftAsyncInstances[$method_name_key])) {
                // 删除实例，避免在daemon环境下一直出错
                unset($this->thriftAsyncInstances[$method_name_key]);
                throw new \Exception($this->serviceName . "->$method_name(" . implode(',',
                        $arguments) . ") already has been called, you can't call again before you call " . self::ASYNC_RECV_PREFIX . $real_method_name,
                    500);
            }
            // 创建实例发送请求
            $instance = $this->__instance();
            $callback = [$instance, 'send_' . $real_method_name];
            if (!is_callable($callback)) {
                throw new \Exception($this->serviceName . '->' . $method_name . ' not callable', 400);
            }
            $ret = call_user_func_array($callback, $arguments);
            // 保存客户单实例
            $this->thriftAsyncInstances[$method_name_key] = $instance;

            return $ret;
        }
        // 异步接收
        if (0 === strpos($method_name, self::ASYNC_RECV_PREFIX)) {
            $real_method_name = substr($method_name, strlen(self::ASYNC_RECV_PREFIX));
            $send_method_name = self::ASYNC_SEND_PREFIX . $real_method_name;
            $arguments_key    = serialize($arguments);
            $method_name_key  = $send_method_name . $arguments_key;
            // 判断是否有发送过这个方法的异步请求
            if (!isset($this->thriftAsyncInstances[$method_name_key])) {
                throw new \Exception($this->serviceName . "->$send_method_name(" . implode(',',
                        $arguments) . ") have not previously been called", 500);
            }
            $instance = $this->thriftAsyncInstances[$method_name_key];
            // 先删除客户端实例
            unset($this->thriftAsyncInstances[$method_name_key]);
            $callback = [$instance, 'recv_' . $real_method_name];
            if (!is_callable($callback)) {
                throw new \Exception($this->serviceName . '->' . $method_name . ' not callable', 400);
            }
            // 接收请求
            $ret = call_user_func_array($callback, []);

            return $ret;
        }
        // 同步调用
        $success = true;
        // 每次都重新创建一个实例
        $this->thriftInstance = $this->__instance();
        $callback             = [$this->thriftInstance, $method_name];
        if (!is_callable($callback)) {
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
    protected function __instance()
    {
        // 获取一个服务端节点地址
        $address = YP_AddressManager::getOneAddress($this->serviceName);
        list($ip, $port) = explode(':', $address);
        // Transport
        $socket         = new TSocket($ip, $port);
        $transport_name = YP_ThriftClient::getTransport($this->serviceName);
        $transport      = new $transport_name($socket);
        // Protocol
        $protocol_name = YP_ThriftClient::getProtocol($this->serviceName);
        $protocol      = new $protocol_name($transport);
        try {
            $transport->open();
        } catch (\Exception $e) {
            // 无法连上，则踢掉这个地址
            YP_AddressManager::kickAddress($address);
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
        $service_dir = YP_ThriftClient::getServiceDir($this->serviceName);
        foreach (glob($service_dir . '/*.php') as $php_file) {
            require_once $php_file;
        }

        return $service_dir;
    }
}

