<?php
/**
 * User: yongli
 * Date: 17/9/1
 * Time: 22:51
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Migrations;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class MigrationCreator
 *
 * @package YP\Libraries\Migrations
 */
class YP_MigrationCreator
{

    /**
     * 注册后创建钩子
     *
     * @var arrayc
     */
    protected $postCreate = [];

    /**
     * 在给定路径上创建一个新的迁移
     *
     * @param  string $name  迁移名称
     * @param  string $path  路径
     * @param  string $table 表名
     * @param  bool   $create
     *
     * @return string
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesNotAlreadyExist($name);
        $path = $this->getPath($name, $path);
        // 首先，我们将获得迁移的模板文件，它作为迁移的模板。一旦我们有这些，保存文件，并运行后创建事件。
        $temp = $this->getTemp($table, $create);
        $this->put($path, $this->populateTemp($name, $temp, $table));
        $this->firePostCreateHooks();

        return $path;
    }

    /**
     * 确保使用给定的迁移名称不存在
     *
     * @param  string $name
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureMigrationDoesNotAlreadyExist($name)
    {
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A $className migration already exists.");
        }
    }

    /**
     * 获取迁移的模板文件
     *
     * @param  string $table
     * @param  bool   $create
     *
     * @return string
     */
    protected function getTemp($table, $create)
    {
        if (is_null($table)) {
            return $this->get($this->getTempPath() . '/blank.temp');
        } else {
            // 我们也有创建新表和修改现有表的模板，以节省开发人员在创建新表或修改现有表时的一些输入。
            // 我们将在这里找到合适的模板。
            $temp = $create ? 'create.temp' : 'update.temp';

            return $this->get($this->getTempPath() . "/{$temp}");
        }
    }

    /**
     * 在迁移模板中替换站位符
     *
     * @param $name
     * @param $temp
     * @param $table
     *
     * @return mixed
     */
    protected function populateTemp($name, $temp, $table)
    {
        $temp = str_replace('DummyClass', $this->getClassName($name), $temp);
        // 在这里，我们将用开发人员指定的表替换表位占用符合，这对于快速从控制台创建一个表创建或更新迁移非常有用，而不是手动输入名称。
        if (!is_null($table)) {
            $temp = str_replace('DummyTable', $table, $temp);
        }

        return $temp;
    }

    /**
     * 获得迁移名的类名
     *
     * @param $name
     *
     * @return mixed
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * 注册后创建钩子
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * 注册后创建钩子
     *
     * @param Closure $callback
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * 获取迁移的完整路径名
     *
     * @param $name
     * @param $path
     *
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_' . $name . '.php';
    }

    /**
     * 获取迁移的日期前缀
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * 获取迁移模板的路径
     *
     * @return string
     */
    public function getTempPath()
    {
        return __DIR__ . '/Temp';
    }

    /**
     * 写入文件的内容
     *
     * @param  string $path
     * @param  string $contents
     * @param  bool   $lock
     *
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * 获取文件的内容
     *
     * @param      $path
     * @param bool $lock
     *
     * @return string
     * @throws Exception
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }
        throw new Exception("File does not exist at path {$path}");
    }
}
