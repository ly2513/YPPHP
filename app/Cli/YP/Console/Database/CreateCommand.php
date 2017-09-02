<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:24
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use YP\Libraries\Migrations\YP_MigrationRepositoryInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BaseCommand
{
    /**
     * 存储库实例
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface|YP_MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * 创建一个新的迁移安装命令实例
     *
     * CreateCommand constructor.
     *
     * @param YP_MigrationRepositoryInterface $repository
     */
    public function __construct(YP_MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('migrate:create')->setDescription('创建一个迁移存储库.')->setDefinition([
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
        $this->repository->setSource($input->getOption('database'));

        $this->repository->createRepository();

        $output->writeln(sprintf('迁移表创建成功!'));
    }
}