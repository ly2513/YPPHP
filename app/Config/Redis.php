<?php
/**
 * User: yongli
 * Date: 17/12/5
 * Time: 16:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

/**
 * Redis
 * Class Redis
 *
 * @package Config
 */
class Redis
{
    /**
     * redis 配置
     *
     * @var array
     */
    public static $redis = [];

    /**
     * Redis constructor.
     */
    public function __construct()
    {
        $this->getRedis();
    }

    /**
     * 获取redis配置
     */
    protected function getRedis()
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
                    'auth'       => 'un12345!QWEASD901'
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
