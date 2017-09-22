<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 15:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

/**
 * Class Redis
 *
 * @package Config
 */
class Redis
{
    /**
     * Redis配置
     *
     * @var
     */
    public static $redis;

    /**
     * Redis constructor.
     */
    public function __construct()
    {
        self::$redis = self::getRedis();
    }

    /**
     * 获得redis配置
     *
     * @return mixed
     */
    protected static function getRedis()
    {
        switch (ENVIRONMENT) {
            case 'prod':
                $config = [
                    'host'       => '127.0.0.1',// 主机
                    'port'       => 6379,       // 端口号
                    'index'      => '0',        // 数据库下标
                    'prefix'     => 'zb:',      // 数据表前缀
                    'persistent' => false,
                ];
                break;
            case 'test':
                $config = [
                    'host'       => '127.0.0.1',// 主机
                    'port'       => 6379,       // 端口号
                    'index'      => '1',        // 数据库下标
                    'prefix'     => 'zb:',      // 数据表前缀
                    'persistent' => false,
                ];
                break;
            default:
                $config = [
                    'host'       => '127.0.0.1',// 主机
                    'port'       => 6379,       // 端口号
                    'index'      => '0',        // 数据库下标
                    'prefix'     => 'zb:',      // 数据表前缀
                    'persistent' => false,
                ];
                break;
        };
        self::$redis = $config;
    }
}