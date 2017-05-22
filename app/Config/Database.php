<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 16:23
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

/**
 * 数据路配置
 *
 * Class Database
 *
 * @package Config
 */
class Database
{
    /**
     * 数据库数组
     *
     * @var array
     */
    public $db = [];

    public function getDB()
    {
        // 主数据库
        $this->db['default'] = [
            'driver'    => 'mysql',             // 数据库驱动
            'host'      => '127.0.0.1',         // 数据库主机
            'database'  => 'chedai',          // 数据库名称
            'username'  => 'root',              // 用户名
            'password'  => 'root',              // 密码
            'charset'   => 'utf8',              // 字符编码
            'collation' => 'utf8_general_ci',   // 排序规则
            'prefix'    => '',                  // 表的前缀
        ];
        // TODO 后续如果对多个数据库操作,可在此添加数据库信息,eg: $this->db['数据库名称'] = [参数同default];

        // doctrine 配置
        $this->db['doctrine'] = [
            'hostname' => '127.0.0.1',
            'username' => 'root',
            'password' => 'root',
            'database' => 'chedai',
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => false,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'autoinit' => false,
            'stricton' => false,
        ];

        return $this->db;
    }

}