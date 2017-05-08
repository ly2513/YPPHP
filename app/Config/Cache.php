<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 11:46
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;


class Cache
{
    /**
     * 缓存处理类型
     * 可能的值: file,redis,dummy
     *
     * @var string
     */
    public $handler = 'file';
//    public $handler = 'redis';

    /**
     * 备份处理
     *
     * @var string
     */
    public $backupHandler = 'dummy';

    /**
     * 缓存目录
     *
     * @var string
     */
    public $path = CACHE_PATH . 'cache/';

    /**
     * 缓存包括查询字符串
     *
     * 可能的值说明
     * TRUE: 启用,将所有查询参数考虑在内。请注意，这可能会导致大量的缓存文件生成相同的页面一遍又一遍
     * FALSE: 禁用
     * array('a'): 启用，但只考虑指定的查询参数列表。
     *
     * @var bool
     */
    public $cacheQueryString = false;

    /**
     * 缓存前缀
     *
     * @var string
     */
    public $prefix = '';

    /**
     * redis配置
     *
     * @var array
     */
    public $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'index'    => '0',
        'persistent' => false,
    ];

    /**
     * 处理缓存方式,key为类的别名,value为处理缓存的类
     *
     * @var array
     */
    public $validHandlers = [
        'dummy'     => \YP\Libraries\Cache\YP_Dummy::class,
        'file'      => \YP\Libraries\Cache\YP_File::class,
        'redis'     => \YP\Libraries\Cache\YP_Redis::class,
    ];
}