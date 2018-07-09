<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 12:02
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace YP\Core;

/**
 * Class YP_CacheFactory
 *
 * @package YP\Core
 */
class YP_CacheFactory
{
    /**
     * 创建缓存处理程序
     *
     * @param $config
     * @param string|null $handler
     * @param string|null $backup
     *
     * @return mixed
     */
    public static function getHandler($config, string $handler = null, string $backup = null)
    {
        // 判断是否存在相应的处理程序
        if (! isset($config->validHandlers) || ! is_array($config->validHandlers)) {
            throw new \InvalidArgumentException(lang('Cache.cacheInvalidHandlers'));
        }
        if (! isset($config->handler) || ! isset($config->backupHandler)) {
            throw new \InvalidArgumentException(lang('Cache.cacheNoBackup'));
        }
        $handler = ! empty($handler) ? $handler : $config->handler;
        $backup  = ! empty($backup) ? $backup : $config->backupHandler;
        if (! array_key_exists($handler, $config->validHandlers) || ! array_key_exists($backup, $config->validHandlers)) {
            throw new \InvalidArgumentException(lang('Cache.cacheHandlerNotFound'));
        }
        // 实例化一个处理缓存对象
        $adapter = new $config->validHandlers[$handler]($config);
        if (! $adapter->isSupported()) {
            $adapter = new $config->validHandlers[$backup]($config);
            if (! $adapter->isSupported()) {
                $adapter = new $config->validHandler['dummy']();
            }
        }
        // 初始化
        $adapter->initialize();

        return $adapter;
    }
}
