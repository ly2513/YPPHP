<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:26
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use Config\Migrations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;

/**
 * Class BaseCommand
 *
 * @package YP\Console\Database
 */
class BaseCommand extends Command
{
    /**
     * 获得迁移文件的存储目录
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $migration = new Migrations();

        return $migration->migratePath;;
    }

    /**
     * 执行另一个命令
     *
     * @param       $command
     * @param array $arguments
     *
     * @return int
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    public function call($command, array $arguments = [])
    {
        $instance = $this->getApplication()->find($command);
        $arguments['command'] = $command;

        return $instance->run(new ArrayInput($arguments), $this->output);
    }

    /**
     * 获取默认确认回调
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return ENVIRONMENT == 'production';
        };
    }

    
    /**
     * 格式化输入到文本表
     *
     * @param array  $headers
     * @param        $rows
     * @param string $style
     * @param        $output
     */
    public function table(array $headers, $rows, $style = 'default', $output)
    {
        $table = new Table($output);
        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }
        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }
}