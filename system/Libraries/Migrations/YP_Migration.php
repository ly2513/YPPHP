<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:50
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
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
