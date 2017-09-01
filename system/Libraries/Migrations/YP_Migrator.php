<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:52
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Migrations;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use YP_MigrationRepositoryInterface;

class YP_Migrator
{
    /**
     * 迁移存储库实现
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * 连接解析器实例
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * 默认连接的名称
     *
     * @var string
     */
    protected $connection;

    /**
     * 当前操作的注释
     *
     * @var array
     */
    protected $notes = [];

    /**
     * 创建一个新的迁移实例
     *
     * YP_Migrator constructor.
     *
     * @param YP_MigrationRepositoryInterface $repository
     * @param Resolver                        $resolver
     */
    public function __construct(YP_MigrationRepositoryInterface $repository, Resolver $resolver)
    {
        $this->resolver   = $resolver;
        $this->repository = $repository;
    }

    /**
     * 在给定路径上运行未完成的迁移
     *
     * @param  string $path
     * @param  array  $options
     *
     * @return void
     */
    public function run($path, array $options = [])
    {
        $this->notes = [];
        $files       = $this->getMigrationFiles($path);
        // 一旦我们捕获了路径的所有迁移文件，我们将将它们与已经为该包运行的迁移进行比较，
        // 然后针对数据库连接运行每个未完成的迁移。
        $ran        = $this->repository->getRan();
        $migrations = array_diff($files, $ran);
        $this->requireFiles($path, $migrations);
        $this->runMigrationList($migrations, $options);
    }

    /**
     * 运行一系列迁移
     *
     * @param       $migrations
     * @param array $options
     */
    public function runMigrationList($migrations, array $options = [])
    {
        // 首先，我们只需要确保有任何迁移可以运行。如果没有，我们只需将它记录给开发人员，
        // 这样他们就知道所有的迁移都是在这个数据库系统上运行的
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }
        $batch   = $this->repository->getNextBatchNumber();
        $pretend = Arr::get($options, 'pretend', false);
        $step    = Arr::get($options, 'step', false);
        // 一旦我们有了一系列的迁移，我们将通过它们旋转并运行迁移“up”，从而对数据库进行更改。
        // 然后，我们将记录迁移的运行情况，以便下次执行时不再重复
        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);
            // 如果我们正在进行迁移，那么我们将为正在运行的每个迁移增加批处理值。
            // 通过这种方式，我们可以运行“工件迁移：回滚”并每次撤销它们
            if ($step) {
                $batch++;
            }
        }
    }

    /**
     * 运行迁移实例
     *
     * @param $file
     * @param $batch
     * @param $pretend
     */
    protected function runUp($file, $batch, $pretend)
    {
        // 首先，我们将从迁移命令“迁移”或“向下”迁移一个“真正”的迁移类实例，
        // 或者我们可以模拟操作。
        $migration = $this->resolve($file);
        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }
        $migration->up();
        // 一旦我们运行了一个迁移类，我们将记录它在应用程序中的运行。
        // 迁移存储库保存迁移顺序
        $this->repository->log($file, $batch);
        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * 回滚最后一个迁移操作
     *
     * @param bool $pretend
     *
     * @return int
     */
    public function rollback($pretend = false)
    {
        $this->notes = [];
        // 我们希望在上一次迁移操作中执行最后一批迁移。然后，我们将反转这些迁移，
        // 并运行它们中的每一个“向下”，以逆转运行的最后一个迁移“操作”。
        $migrations = $this->repository->getLast();
        $count      = count($migrations);
        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            // 我们需要扭转这些迁移，使它们“反向”，与它们在“up”上运行的相反。
            // 它允许我们回溯迁移，并适当地逆转运行的整个数据库模式操作
            foreach ($migrations as $migration) {
                $this->runDown((object)$migration, $pretend);
            }
        }

        return $count;
    }

    /**
     * 将所有当前应用的迁移回滚
     *
     * @param  bool $pretend
     *
     * @return int
     */
    public function reset($pretend = false)
    {
        $this->notes = [];
        $migrations  = array_reverse($this->repository->getRan());
        $count       = count($migrations);
        if ($count === 0) {
            $this->note('<info>Nothing to rollback.</info>');
        } else {
            foreach ($migrations as $migration) {
                $this->runDown((object)['migration' => $migration], $pretend);
            }
        }

        return $count;
    }

    /**
     * 运行“向下”一个迁移实例
     *
     * @param $migration
     * @param $pretend
     */
    protected function runDown($migration, $pretend)
    {
        $file = $migration->migration;
        // 首先，我们将获得迁移的文件名，这样我们就可以解析出迁移的实例。
        // 一旦我们获得一个实例，我们就可以执行迁移的一个假装执行，或者我们可以运行真正的迁移。
        $instance = $this->resolve($file);
        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }
        $instance->down();
        // 一旦我们成功地运行了迁移“向下”，我们将从迁移存储库中删除它，
        // 这样它将被认为没有被应用程序运行，然后可以通过任何后续操作来触发
        $this->repository->delete($migration);
        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * 在给定路径中获取所有迁移文件
     *
     * @param  string $path
     *
     * @return array
     */
    public function getMigrationFiles($path)
    {
        // 获取所有的迁移文件
        $files = glob($path . '/*_*.php', 0);
        // 一旦我们有了文件的数组目录中我们会删除扩展走这是我们需要寻找的迁徙时没有运行在数据库文件的基名称
        if ($files === false) {
            return [];
        }
        $files = array_map(function ($file) {
            return str_replace('.php', '', basename($file));
        }, $files);
        // 一旦我们拥有了所有格式化的文件名，我们将对它们进行排序，因为它们都是以时间戳开始的，
        // 这应该使我们按照应用程序开发人员实际创建的顺序进行迁移
        sort($files);

        return $files;
    }

    /**
     * 在给定路径中的所有迁移文件中都需要
     *
     * @param  string $path
     * @param  array  $files
     *
     * @return void
     */
    public function requireFiles($path, array $files)
    {
        foreach ($files as $file) {
            require_once $path . '/' . $file . '.php';
        }
    }

    /**
     * 假装在迁移
     *
     * @param  object $migration
     * @param  string $method
     *
     * @return void
     */
    protected function pretendToRun($migration, $method)
    {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);
            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * 获取为迁移运行的所有查询
     *
     * @param $migration
     * @param $method
     *
     * @return array
     */
    protected function getQueries($migration, $method)
    {
        $connection = $migration->getConnection();
        // 现在我们拥有了连接，我们就可以解决它，假装运行数据库的查询，返回原始的SQL语句数组，这些语句将对数据库系统进行迁移
        $db = $this->resolveConnection($connection);

        return $db->pretend(function () use ($migration, $method) {
            $migration->$method();
        });
    }

    /**
     * 从文件解析迁移实例
     *
     * @param  string $file
     *
     * @return object
     */
    public function resolve($file)
    {
        $file  = implode('_', array_slice(explode('_', $file), 4));
        $class = Str::studly($file);

        return new $class;
    }

    /**
     * 记录一个迁移事件
     *
     * @param $message
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * 获取最后操作的记录
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * 解析数据库连接实例
     *
     * @param $connection
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function resolveConnection($connection)
    {
        return $this->resolver->connection($connection);
    }

    /**
     * 设置默认连接名
     *
     * @param $name
     */
    public function setConnection($name)
    {
        if (!is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }
        $this->repository->setSource($name);
        $this->connection = $name;
    }

    /**
     * 获取迁移存储库实例
     *
     * @return \Illuminate\Database\Migrations\MigrationRepositoryInterface|YP_MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * 确定迁移存储库是否存在
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }
}
