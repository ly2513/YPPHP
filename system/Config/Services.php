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
        if ($getShared)
        {
            return self::getSharedInstance('exceptions', $config);
        }

        if (empty($config))
        {
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
        if ($getShared)
        {
            return self::getSharedInstance('locator');
        }

        return new \YP\FileLocator(new \Config\Autoload());
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