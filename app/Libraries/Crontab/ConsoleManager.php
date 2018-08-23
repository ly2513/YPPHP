<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Libraries\Crontab;

class ConsoleManager
{

    /**
     * 设置任务状态表格头
     * @var array
     */
    static private $_taskHeader = ['id', 'name', 'tag', 'status', 'count', 'last_time', 'next_time'];

    /**
     * 设置扩展检测表格头
     * @var array
     */
    static private $_checkHeader = ['name', 'status', 'desc', 'help'];

    /**
     * 向控制台输出任务状态信息
     *
     * @param       $tasks
     * @param array $expand 扩展头部标题
     *
     * @return string
     */
    public static function taskStatusTable($tasks, $expand = [])
    {
        $expandTable = new \Console_Table();
        $last = end($expand);
        foreach ($expand as $key => $value) {
            $expandTable->addRow([$key, $value]);
            if ($value != $last) {
                $expandTable->addSeparator();
            }
        }
        $table = '';
        $table .= $expandTable->getTable();
        $taskTable = new \Console_Table();
        $taskTable->setHeaders(static::$_taskHeader);
        $status = [
            '0' => '正常',
            '1' => '关闭',
            '2' => '过期关闭',
        ];
        foreach ($tasks as $id => $task) {
            $attr = $task->getAttributes();
            $taskTable->addRow([
                $attr['id'],
                $attr['name'],
                $attr['intvalTag'],
                $status[$attr['status']],
                $attr['count'],
                $attr['lastTime'] ? date('Y-m-d H:i:s', $attr['lastTime']) : '-',
                $attr['nextTime'] ? date('Y-m-d H:i:s', $attr['nextTime']) : '-'
            ]);
        }
        return $table . $taskTable->getTable();
    }

    /**
     * 检查扩展是否开启
     */
    public static function checkExtensions()
    {
        $table = new \Console_Table();
        $table->setHeaders(static::$_checkHeader);
        $extensions = get_loaded_extensions();
        if (version_compare(PHP_VERSION, '5.4', ">=")) {
            $row = ['php>=5.4', '[OK]'];
        } else {
            $row = ['php>=5.4', '[ERR]', '请升级PHP版本'];
        }
        $table->addRow($row);
        $checks = [
            ['name' => 'pcntl', 'remark' => '缺少扩展', 'help' => 'http://php.net/manual/zh/pcntl.installation.php'],
            ['name' => 'posix', 'remark' => '缺少扩展', 'help' => 'http://php.net/manual/zh/posix.installation.php'],
            ['name' => 'sysvmsg', 'remark' => '缺少扩展', 'help' => '自行搜索安装方法,也可以推荐好的文章'],
            ['name' => 'sysvsem', 'remark' => '缺少扩展', 'help' => '自行搜索安装方法,也可以推荐好的文章'],
            ['name' => 'sysvshm', 'remark' => '缺少扩展', 'help' => '自行搜索安装方法,也可以推荐好的文章'],
        ];
        foreach ($checks as $check) {
            if (in_array($check['name'], $extensions)) {
                $row = [$check['name'], '[OK]'];
            } else {
                $row = [$check['name'], '[ERR]', $check['remark'], $check['help']];
            }
            $table->addRow($row);
        }
        return $table->getTable();
    }

}