<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:49
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Migrations;

/**
 * Interface YP_MigrationRepositoryInterface
 * 
 * @package YP\Libraries\Migrations
 */
interface YP_MigrationRepositoryInterface
{
    /**
     * 给指定的数据包进行迁移
     *
     * @return array
     */
    public function getRan();

    /**
     * 获取最后一批迁移
     *
     * @return array
     */
    public function getLast();

    /**
     * 记录迁移是否已运行
     *
     * @param  string $file
     * @param  int    $batch
     *
     * @return void
     */
    public function log($file, $batch);

    /**
     * 从日志中删除迁移
     *
     * @param  object $migration
     *
     * @return void
     */
    public function delete($migration);

    /**
     * 获取下一个迁移批号
     *
     * @return int
     */
    public function getNextBatchNumber();

    /**
     * 创建迁移存储数据区
     *
     * @return void
     */
    public function createRepository();

    /**
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists();

    /**
     * 设置信息源用来收集数据
     *
     * @param  string $name
     *
     * @return void
     */
    public function setSource($name);
}
