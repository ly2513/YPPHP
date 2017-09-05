<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Migrations;

use Illuminate\Database\ConnectionResolverInterface as Resolver;

class YP_DatabaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * 数据库连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * 迁移表的名称
     *
     * @var string
     */
    protected $table;

    /**
     * 要使用的数据库连接的名称
     *
     * @var string
     */
    protected $connection;

    /**
     * 创建一个新的数据库迁移存储库实例
     *
     * DatabaseMigrationRepository constructor.
     *
     * @param Resolver $resolver
     * @param          $table
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table    = $table;
        $this->resolver = $resolver;
    }

    /**
     * 获取一个正在进行迁移
     *
     * @return array
     */
    public function getRan()
    {
        return $this->table()->orderBy('batch', 'asc')->orderBy('migration', 'asc')->pluck('migration');
    }

    /**
     * 获取最后一批迁移批
     *
     * @return array
     */
    public function getLast()
    {
        $query = $this->table()->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('migration', 'desc')->get();
    }

    /**
     * 记录迁移是否已运行
     *
     * @param  string $file
     * @param  int    $batch
     *
     * @return void
     */
    public function log($file, $batch)
    {
        $record = ['migration' => $file, 'batch' => $batch];
        $this->table()->insert($record);
    }

    /**
     * 从日志中删除迁移
     *
     * @param  object $migration
     *
     * @return void
     */
    public function delete($migration)
    {
        $this->table()->where('migration', $migration->migration)->delete();
    }

    /**
     * 获取下一个迁移批号
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * 获取最后一个迁移批号
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        return $this->table()->max('batch');
    }

    /**
     * 创建迁移存储数据区
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();
        $schema->create($this->table, function ($table) {
            // 迁移表负责跟踪哪些迁移实际上已经为应用程序运行了。
            // 我们将创建表来保存迁移文件的路径以及批处理ID
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * 获取迁移表的查询生成器
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table);
    }

    /**
     * 获取一个连接解析实例
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * 获取解析一个数据库连接实例
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * 设置信息源以收集数据
     *
     * @param  string $name
     *
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}
