<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:24
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use Closure;
use YP\Libraries\Migrations\YP_Migrator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends BaseCommand
{

    /**
     * 迁移实例
     *
     * @var YP_Migrator
     */
    protected $migrator;

    /**
     * 创建一个新的迁移命令实例
     *
     * MigrateCommand constructor.
     *
     * @param YP_Migrator $migrator
     */
    public function __construct(YP_Migrator $migrator)
    {
        parent::__construct();
        $this->migrator = $migrator;
    }

    

    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('migrate')->setDescription('运行数据库迁移.')->setDefinition([
            new InputOption('database', 'f', InputOption::VALUE_REQUIRED, '迁移库表名.'),
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
                $output->writeln(str_repeat('*', strlen('Application In Production!') + 12));
                $output->writeln('*      Application In Production!     *');
                $output->writeln(str_repeat('*', strlen('Application In Production!') + 12));
                $output->writeln('');
                $style     = new SymfonyStyle($input, $output);
                $confirmed = $style->confirm('Do you really wish to run this command?');
                unset($style);
                if (!$confirmed) {
                    $output->writeln('Command Cancelled!');
                    $status = false;
                } else {
                    $status = true;
                }
            }
        }
        if (!$status) {
            return;
        }
        // 准备运行的迁移数据库
        $this->migrator->setConnection($input->getOption('database'));
        if (!$this->migrator->repositoryExists()) {
            $options = ['--database' => $input->getOption('database')];
            $this->call('migrate:create', $options);
        }
        // "伪装"选项可用于"模拟"迁移，并捕获如果要对数据库进行真正的迁移，
        // 将引发的SQL查询，这有助于双重检查迁移。
        $pretend = $input->getOption('pretend');
        // 接下来，我们将检查是否已经定义了一个PATH选项。
        // 如果有，我们将使用与这个安装文件夹的根相对的路径，以便迁移可以在应用程序中的任何路径上运行
        if (!is_null($path = $input->getOption('path'))) {
            $path = ROOT_PATH  . $path;
        } else {
            $path = $this->getMigrationPath();
        }
        $this->migrator->run($path, [
            'pretend' => $pretend,
            'step'    => $input->getOption('step'),
        ]);
        // 一旦他们跑了我们会抓住注意输出并将其发送至控制台屏幕，
        // 因为他们本身的功能没有传入类的任何实例输出合同
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }
        // 最后，如果已经给出了“种子”选项，我们将重新运行数据库种子任务来重新填充数据库，
        // 在同时添加迁移和种子时，这是非常方便的，因为它只是这个命令
        if ($this->getOption('seed')) {
            $this->call('db:seed', ['--force' => true]);
        }
        $this->repository->setSource($input->getOption('database'));
        $this->repository->createRepository();
        $output->writeln(sprintf('迁移表创建成功!'));
    }
}