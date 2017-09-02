<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:25
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCommand extends BaseCommand
{
    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('migrate:refresh')->setDescription('重置并重新运行所有迁移.')->setDefinition([
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, '要使用的数据库连接.'),
            new InputOption('force', null, InputOption::VALUE_NONE, '强行在生产环境操作运行.'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, '要执行的迁移文件路径.'),
            new InputOption('seed', null, InputOption::VALUE_NONE, '指示是否应重新运行随机任务.'),
            new InputOption('seeder', null, InputOption::VALUE_OPTIONAL, '随机类名称.'),
        ]);

    }

    /**
     * 命令操作
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $callback      = $this->getDefaultConfirmCallback();
        $shouldConfirm = $callback instanceof Closure ? call_user_func($callback) : $callback;
        $status        = false;
        if ($shouldConfirm) {
            if ($input->getOption('force')) {
                $status = true;
            } else {
                $output->writeln('<comment>' . str_repeat('*',
                        strlen('Application In Production!') + 12) . '</comment>');
                $output->writeln('<comment>*      Application In Production!     *</comment>');
                $output->writeln('<comment>' . str_repeat('*',
                        strlen('Application In Production!') + 12) . '</comment>');
                $output->writeln('');
                $style     = new SymfonyStyle($input, $output);
                $confirmed = $style->confirm('Do you really wish to run this command?');
                unset($style);
                if (!$confirmed) {
                    $output->writeln('<comment> Command Cancelled! </comment>');
                    $status = false;
                } else {
                    $status = true;
                }
            }
        }
        if (!$status) {
            return;
        }
        $database = $input->getOption('database');
        $force    = $input->getOption('force');
        $path     = $input->getOption('path');
        $this->call('migrate:reset', ['--database' => $database, '--force' => $force]);
        // 刷新命令本质上只是一些其他迁移命令的简单集合，只是提供了一个方便的包装器来连续执行它们。
        // 我们还将查看是否需要重新生成数据库。
        $this->call('migrate', [
            '--database' => $database,
            '--force'    => $force,
            '--path'     => $path,
        ]);
        $seed = $this->getOption('seed') || $this->getOption('seeder');
        if ($seed) {
            $class = $this->getOption('seeder') ? : 'DatabaseSeeder';
            $force = $input->getOption('force');
            $this->call('db:seed', [
                '--database' => $database,
                '--class'    => $class,
                '--force'    => $force,
            ]);
        }

    }
}