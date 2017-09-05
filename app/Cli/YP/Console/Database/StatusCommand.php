<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:26
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Database;

use YP\Libraries\Migrations\YP_Migrator;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
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
     * StatusCommand constructor.
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
        $this->setName('migrate:status')->setDescription('显示每个迁移的状态.')->setDefinition([
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, '要使用的数据库连接.'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL, '要使用的迁移路径路径.'),
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
        if (!$this->migrator->repositoryExists()) {
            return $this->error('No migrations found.');
        }
        $this->migrator->setConnection($input->getOption('database'));
        // 设置迁移文件目录
        if (!is_null($path = $input->getOption('path'))) {
            $path = ROOT_PATH . $path;
        } else {
            $path = $this->getMigrationPath();
        }
        $ran        = $this->migrator->getRepository()->getRan();
        $migrations = [];
        foreach ($this->migrator->getMigrationFiles($path) as $migration) {
            $migrations[] = in_array($migration, $ran) ? ['<info>Y</info>', $migration] : [
                '<fg=red>N</fg=red>',
                $migration
            ];
        }
        if (count($migrations) > 0) {
            $this->table(['Ran?', 'Migration'], $migrations, 'default', $output);
        } else {
            $output->writeln('<error>No migrations found</error>');
        }
    }
}