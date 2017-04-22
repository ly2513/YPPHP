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
     * The file locator provides utility methods for looking for non-classes
     * within namespaced folders, as well as convenience methods for
     * loading 'helpers', and 'libraries'.
     */
    /**
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
     * The Logger class is a PSR-3 compatible Logging class that supports
     * multiple handlers that process the actual logging.
     */
    /**
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
     * The Request class models an HTTP request.
     */
    /**
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return \CodeIgniter\HTTP\IncomingRequest|mixed
     */
    /**
     *
     *
     * @param \Config\App|null $config
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
     * The Response class models an HTTP response.
     */
    /**
     * @param \Config\App|null $config
     * @param bool             $getShared
     *
     * @return mixed|\YP\Core\YP_Response
     */
    public static function response(\Config\App $config = null, $getShared = true)
    {
        if ($getShared)
        {
            return self::getSharedInstance('response', $config);
        }

        if (! is_object($config))
        {
            $config = new \Config\App();
        }

        return new \YP\Core\YP_Response($config);
    }

    /**
     * The Timer class provides a simple way to Benchmark portions of your
     * application.
     */
    /**
     * @param bool $getShared
     *
     * @return mixed|\YP\Debug\Timer
     */
    public static function timer($getShared = true)
    {
        if ($getShared)
        {
            return self::getSharedInstance('timer');
        }

        return new \YP\Debug\Timer();
    }

    /**
     * The Routes service is a class that allows for easily building
     * a collection of routes.
     */
    public static function routes($getShared = true)
    {
        if ($getShared)
        {
            return self::getSharedInstance('routes');
        }

        return new \YP\Core\YP_RouterCollection();
    }

    /**
     * The Router class uses a RouteCollection's array of routes, and determines
     * the correct Controller and Method to execute.
     */
    /**
     * @param \YP\Core\YP_RouterCollection|null $routes
     * @param bool                              $getShared
     *
     * @return mixed|\YP\Core\YP_Router
     */
    public static function router(\YP\Core\YP_RouterCollection $routes = null, $getShared = true)
    {
        if ($getShared)
        {
            return self::getSharedInstance('router', $routes);
        }

        if (empty($routes))
        {
            $routes = self::routes(true);
        }

        return new \YP\Core\YP_Router($routes);
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
            // Make sure $getShared is false
            array_push($params, false);
            static::$instances[$key] = static::$key(...$params);
        }

        return static::$instances[$key];
    }

}