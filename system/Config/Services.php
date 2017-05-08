<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 11:35
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;

/**
 * 框架各类加载服务类
 *
 * Class Services
 *
 * @package YP\Config
 */
class Services
{
    /**
     * 服务类加载
     *
     * @var array
     */
    static protected $instances = [];

    /**
     * 自动加载服务
     *
     * @param bool $getShared
     *
     * @return \Yp\Autoload
     */
    public static function autoload($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('autoload');
        }

        return new \Yp\Autoload();
    }

    /**
     * 加载异常处理类
     *
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Core\YP_Exceptions
     */
    public static function exceptions(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('exceptions', $config);
        }
        if (empty($config)) {
            $config = new \Config\App();
        }

        return new \YP\Core\YP_Exceptions($config);
    }

    /**
     * 文件定位为寻找非类的命名空间文件夹内的实用方法，加载“help”和“Libraries”等
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\FileLocator
     */
    public static function locator($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('locator');
        }

        return new \YP\FileLocator(new \Config\Autoload());
    }

    /**
     * 加载日志类
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\Core\YP_Log
     */
    public static function log($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('log');
        }

        return new \YP\Core\YP_Log(new \Config\Log());
    }

    /**
     * 加载http请求类
     *
     * @param \Config\App|null $config 配置
     * @param bool             $getShared
     *
     * @return mixed|\YP\Core\YP_IncomingRequest
     */
    public static function request(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('request', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\App();
        }

        return new \YP\Core\YP_IncomingRequest($config, new \YP\Core\YP_Uri());
    }

    /**
     * 加载http响应类
     *
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Core\YP_Response
     */
    public static function response(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('response', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\App();
        }

        return new \YP\Core\YP_Response($config);
    }

    /**
     * 加载定时器类
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\Debug\Timer
     */
    public static function timer($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('timer');
        }

        return new \YP\Debug\YP_Timer();
    }

    /**
     * 加载路由收集类
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\Core\YP_RouterCollection
     */
    public static function routes($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('routes');
        }

        return new \YP\Core\YP_RouterCollection();
    }

    /**
     * 加载路由类
     *
     * @param \YP\Core\YP_RouterCollection|null $routes
     * @param bool                              $getShared
     *
     * @return mixed|\YP\Core\YP_Router
     */
    public static function router(\YP\Core\YP_RouterCollection $routes = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('router', $routes);
        }
        if (empty($routes)) {
            $routes = self::routes(true);
        }

        return new \YP\Core\YP_Router($routes);
    }

    /**
     * 过滤器允许您在执行控制器之前和/或之后运行任务。在过滤器之前，
     * 请求可以被修改，并且基于请求执行的操作，而在过滤器被发送到客户端之前，它可以对响应本身进行修改或修改
     *
     * @param null $config
     * @param bool $getShared
     *
     * @return mixed|\YP\Core\YP_Filter
     */
    public static function filters($config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('filters', $config);
        }
        if (empty($config)) {
            $config = new \Config\Filters();
        }

        return new \YP\Core\YP_Filter($config, self::request(), self::response());
    }

    /**
     * 实例化一个缓存操作对象
     *
     * @param \Config\Cache|null $config
     * @param bool               $getShared
     *
     * @return mixed
     */
    public static function cache(\Config\Cache $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('cache', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\Cache();
        }

        return \YP\Core\YP_CacheFactory::getHandler($config);
    }

    /**
     * 返回数据配置信息
     *
     * @param \Config\Database|null $config
     * @param bool                  $getShared
     *
     * @return \Config\Database|mixed
     */
    public static function database(\Config\Database $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('database', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\Database();
        }

        return $config;
    }

    /**
     * 负责加载语言字符串翻译
     *
     * @param string|null $locale
     * @param bool        $getShared
     *
     * @return mixed|\YP\Core\YP_Language
     */
    public static function language(string $locale = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('language', $locale);
        }
        $locale = !empty($locale) ? $locale : self::request()->getLocale();

        return new \YP\Core\YP_Language($locale);
    }

    /**
     * 实例化命令行的请求对象
     *
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Cli\YP_CLIRequest
     */
    public static function cliRequest(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('cliRequest', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\App();
        }

        return new \YP\Cli\YP_CLIRequest($config, new \YP\Core\YP_Uri());
    }
    
    /**
     * URI类提供了一种模式和操作的URI
     *
     * @param null $uri
     * @param bool $getShared
     *
     * @return mixed|\YP\Core\YP_Uri
     */
    public static function uri($uri = null, $getShared = true)
    {
        if ($getShared)
        {
            return self::getSharedInstance('uri', $uri);
        }

        return new \YP\Core\YP_Uri($uri);
    }

    /**
     * 加载工具栏
     *
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Debug\Toolbar
     */
    public static function toolbar(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('toolbar', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\App();
        }

        return new \YP\Debug\YP_Toolbar($config);
    }

    /**
     * 加载Twig模板引擎
     *
     * @param \Config\Twig|null $config
     * @param bool              $getShared
     *
     * @return mixed|\YP\Libraries\YP_Twig
     */
    public static function twig(\Config\Twig $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('twig', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\Twig();
        }

        return new \YP\Libraries\YP_Twig($config);
    }

    /**
     * 加载输入处理类
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\Libraries\YP_Input
     */
    public static function input($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('input');
        }

        return new \YP\Libraries\YP_Input();
    }

    /**
     * 加载jsonSchema类
     *
     * @param bool $getShared
     *
     * @return mixed|\YP\Libraries\YP_JsonSchema
     */
    public static function schema($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('schema');
        }

        return new \YP\Libraries\YP_JsonSchema();
    }

    /**
     * 加载错误信息
     *
     * @param bool $getShared
     *
     * @return \Config\Error|mixed
     */
    protected static function error($getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('error');
        }

        return new \Config\Error();
    }

    /**
     * 加载自动验证
     *
     * @param \Config\Validation|null $config
     * @param bool                    $getShared
     *
     * @return mixed|\YP\Libraries\YP_Validation
     */
    public static function validation(\Config\Validation $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('validation', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\Validation();
        }

        return new \YP\Libraries\YP_Validation($config);
    }

    /**
     * 加载session库
     *
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Core\YP_Session
     */
    public static function session(\Config\App $config = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('session', $config);
        }
        if (!is_object($config)) {
            $config = new \Config\App();
        }
        $logger     = self::log(true);
        $driverName = $config->sessionDriver;
        $driver     = new $driverName($config);
        $driver->setLogger($logger);
        $session = new \YP\Core\YP_Session($driver, $config);
        $session->setLogger($logger);

        return $session;
    }

    /**
     * negotiator
     * The Negotiate class provides the content negotiation features for
     * working the request to determine correct language, encoding, charset,
     * and more.
     *
     * @param \YP\Core\YP_IncomingRequest|null $request
     * @param bool                             $getShared
     *
     * @return mixed|\YP\Core\YP_Negotiate
     */
    public static function negotiator(\YP\Core\YP_IncomingRequest $request = null, $getShared = true)
    {
        if ($getShared) {
            return self::getSharedInstance('negotiator', $request);
        }
        if (is_null($request)) {
            $request = self::request();
        }

        return new \YP\Core\YP_Negotiate($request);
    }

    /**
     * 获得已加载的类的映射数组
     * 将已加载的类的类名作为key存放到$instances中
     *
     * @param string $key       类名
     * @param array  ...$params 多参数
     *
     * @return mixed
     */
    protected static function getSharedInstance(string $key, ...$params)
    {
        if (!isset(static::$instances[$key])) {
            // 确保$getShared为false;
            array_push($params, false);
            static::$instances[$key] = static::$key(...$params);
        }

        return static::$instances[$key];
    }

    /**
     * Provides the ability to perform case-insensitive calling of service
     * names.
     *
     * @param string $name
     * @param array  $arguments
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $name = strtolower($name);
        if (method_exists(__CLASS__, $name)) {
            return Services::$name(...$arguments);
        }
    }

    public static function getObject()
    {
        return static::$instances;
    }

}