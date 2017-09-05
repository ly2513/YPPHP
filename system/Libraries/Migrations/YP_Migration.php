<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:50
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Migrations;

/**
 * Class YP_Migration
 *
 * @package YP\Libraries\Migrations
 */
abstract class YP_Migration
{
    /**
     * 数据库连接名称
     *
     * @var string
     */
    protected $connection;

    /**
     * 获取迁移连接名
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }
}