<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:47
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

define('EVENT_PRIORITY_LOW', 200);
define('EVENT_PRIORITY_NORMAL', 100);
define('EVENT_PRIORITY_HIGH', 10);

/**
 * Class YP_Hooks
 *
 * @package YP\Core
 */
class YP_Hooks
{
    /**
     * 监听数组
     *
     * @var array
     */
    protected static $listeners = [];

    /**
     * 该状态值,表示: 已经从配置文件读取所有定义的事件。
     *
     * @var bool
     */
    protected static $haveReadFromFile = false;

    /**
     * 包含加载事件的文件的路径
     *
     * @var
     */
    protected static $eventsFile;

    /**
     * 初始化钩子,确保已经准备了一个钩子文件
     *
     * @param string|null $file
     */
    public static function initialize(string $file = null)
    {
        // 不要复写任何东西
        if (!empty(self::$eventsFile)) {
            return;
        }
        // 钩子事件默认路径
        if (empty($file)) {
            $file = APP_PATH . 'Config/Events.php';
        }
        self::$eventsFile = $file;
    }

    /**
     * 注册事件发生的动作,该动作可以是任意类型的可调用
     * 例如:
     *  Hooks::on('create', 'myFunction');               // 函数
     *  Hooks::on('create', ['myClass', 'myMethod']);    // 类方法
     *  Hooks::on('create', [$myInstance, 'myMethod']);  // 现有的实例方法
     *  Hooks::on('create', function() {});              // 闭包
     *
     *
     * @param          $event_name
     * @param callable $callback
     * @param int      $priority
     */
    public static function on($event_name, callable $callback, $priority = EVENT_PRIORITY_NORMAL)
    {
        if (!isset(self::$listeners[$event_name])) {
            self::$listeners[$event_name] = [
                true,
                [$priority],
                [$callback],
            ];
        } else {
            self::$listeners[$event_name][0]   = false; // 未排序
            self::$listeners[$event_name][1][] = $priority;
            self::$listeners[$event_name][2][] = $callback;
        }
    }

    /**
     * 触发钩子事件发生
     * 通过所有的绑定方法运行,直到：1、所有订户已完成 2、方法返回false，在该点执行的用户停止。
     *
     * @param       $event_name
     * @param array ...$arguments
     *
     * @return bool
     */
    public static function trigger($event_name, ...$arguments): bool
    {
        // 从配置文件Config/Hooks中读取所有的钩子事件
        if (!self::$haveReadFromFile) {
            self::initialize();
            if (is_file(self::$eventsFile)) {
                include self::$eventsFile;
            }
            self::$haveReadFromFile = true;
        }
        $listeners = self::listeners($event_name);
        foreach ($listeners as $listener) {
            $result = $listener(...$arguments);
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 返回单个事件的侦听器数组。按优先级排序。
     * 如果找不到监听器，返回FALSE，如果它被删除,返回TRUE。
     *
     * @param $event_name
     *
     * @return array
     */
    public static function listeners($event_name): array
    {
        if (!isset(self::$listeners[$event_name])) {
            return [];
        }
        // 未排序
        if (!self::$listeners[$event_name][0]) {
            // 进行排序
            array_multisort(self::$listeners[$event_name][1], SORT_NUMERIC, self::$listeners[$event_name][2]);
            // 标记已排序
            self::$listeners[$event_name][0] = true;
        }

        return self::$listeners[$event_name][2];
    }

    /**
     * 从事件中移除单个侦听器。
     * 如果找不到监听器，返回FALSE，如果已删除,则返回TRUE
     *
     * @param          $event_name
     * @param callable $listener
     *
     * @return bool
     */
    public static function removeListener($event_name, callable $listener): bool
    {
        if (!isset(self::$listeners[$event_name])) {
            return false;
        }
        foreach (self::$listeners[$event_name][2] as $index => $check) {
            if ($check === $listener) {
                unset(self::$listeners[$event_name][1][$index]);
                unset(self::$listeners[$event_name][2][$index]);

                return true;
            }
        }

        return false;
    }

    /**
     * 移除所有监听器
     * 如果event_name指定，只为那事件侦听器将被删除，否则所有的事件，所有的听众都会删除。
     *
     * @param null $event_name
     */
    public static function removeAllListeners($event_name = null)
    {
        if (!is_null($event_name)) {
            unset(self::$listeners[$event_name]);
        } else {
            self::$listeners = [];
        }
    }

    /**
     * 设置要读取路由的文件的路径
     *
     * @param string $path
     */
    public function setFile(string $path)
    {
        self::$eventsFile = $path;
    }
}