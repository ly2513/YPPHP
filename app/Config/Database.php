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
     * 数据库驱动类型
     *
     * @var string
     */
    public $driver = 'mysql';

    /**
     * 数据库主机
     *
     * @var string
     */
    public $host  = '127.0.0.1';

    /**
     * 数据库名称
     *
     * @var string
     */
    public $database = 'rmos_dev';

    /**
     * 用户名
     *
     * @var string
     */
    public $username = 'root';

    /**
     * 密码
     *
     * @var string
     */
    public $password = 'root';

    /**
     * 数据库编码
     *
     * @var string
     */
    public $charset = 'utf8';

    /**
     * 排序规则
     *
     * @var string
     */
    public $collation = 'utf8_general_ci';

    /**
     * 表的前缀
     *
     * @var string
     */
    public $prefix = '';

}