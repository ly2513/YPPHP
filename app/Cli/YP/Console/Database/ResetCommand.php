<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:25
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use YP\Libraries\Migrations\YP_Migrator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ResetCommand
 *
 * @package YP\Console\Database
 */
class ResetCommand extends Command
{
    /**
     * 迁移实例
     *
     * @var YP_Migrator
     */
    protected $migrator;

    /**
     * 创建一个新的迁移回滚命令实例
     *
     * ResetCommand constructor.
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
        $this->setName('migrate:reset')->setDescription('回滚所有的迁移操作.')->setDefinition([
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, '要使用的数据库连接.'),
            new InputOption('force', null, InputOption::VALUE_NONE, '强行在生产环境操作运行.'),
            new InputOption('pretend', null, InputOption::VALUE_NONE, '运行的SQL查询.'),
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
        $this->migrator->setConnection($input->getOption('database'));
        if (!$this->migrator->repositoryExists()) {
            $output->writeln('<comment>Migration table not found.</comment>');

            return;
        }
        $pretend = $input->getOption('pretend');
        $this->migrator->reset($pretend);
        // 一旦他们跑了我们会抓住注意输出并将其发送至控制台屏幕，
        // 因为他们本身的功能没有传入类的任何实例输出合同
        foreach ($this->migrator->getNotes() as $note) {
            $output->writeln($note);
        }
    }
}