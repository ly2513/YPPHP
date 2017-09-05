<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:25
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use YP\Libraries\Migrations\YP_MigrationCreator;

class MigrateMakeCommand extends BaseCommand
{
    /**
     * 控制台命令定义
     *
     * @var string
     */
    protected $signature = 'migration:make {name : 迁移的名称.}
        {--create= : 要创建的表.}
        {--table= : 要迁移的表.}
        {--path= : 应该创建迁移文件的位置.}';

    /**
     * 迁移创建者实例
     *
     * @var YP_MigrationCreator
     */
    protected $creator;

    /**
     * 创建一个新的迁移安装命令实例
     *
     * MigrateMakeCommand constructor.
     *
     * @param YP_MigrationCreator $creator
     */
    public function __construct(YP_MigrationCreator $creator)
    {
        parent::__construct();
        $this->creator = $creator;
    }

    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        if ($this->signature) {
            list($name, $arguments, $options) = self::parse($this->signature);
            $this->setName($name);
            foreach ($arguments as $argument) {
                $this->getDefinition()->addArgument($argument);
            }
            foreach ($options as $option) {
                $this->getDefinition()->addOption($option);
            }
        }

    }

    /**
     * 命令操作
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // 可以指定在这个模式操作中要修改的表。
        // 还可以指定此表是否需要新创建，以便我们可以创建适当的迁移。
        $name   = trim($input->getArgument('name'));
        $table  = $input->getOption('table');
        $create = $input->getOption('create') ? : false;
        if (!$table && is_string($create)) {
            $table  = $create;
            $create = true;
        }
        // 我们已经准备好将迁移写入磁盘了。
        // 一旦我们写的迁移，我们将转储加载为确保迁移是由类装载器注册整个框架
        $path = $input->getOption('path');
        $path = $path ? ROOT_PATH . $path : parent::getMigrationPath();
        $file = pathinfo($this->creator->create($name, $path, $table, $create), PATHINFO_FILENAME);
        $output->writeln('Created Migration:' . $file);
        $process = (new Process('', $this->workingPath))->setTimeout(null);
        $process->setCommandLine(trim($this->findComposer() . ' dump-autoload '));
        $process->run();
    }

    /**
     * 将给定控制台命令定义解析为数组
     *
     * @param $expression
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function parse($expression)
    {
        if (trim($expression) === '') {
            throw new InvalidArgumentException('Console command definition is empty.');
        }
        preg_match('/[^\s]+/', $expression, $matches);
        if (isset($matches[0])) {
            $name = $matches[0];
        } else {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }
        preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches);
        $tokens = isset($matches[1]) ? $matches[1] : [];
        if (count($tokens)) {
            return array_merge([$name], static::parameters($tokens));
        }

        return [$name, [], []];
    }
}