<?php
/**
 * User: yongli
 * Date: 17/9/29
 * Time: 14:50
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Console\Thrift;

use Config\ThriftClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('thrift:create')->setDescription('生成thrift文件.')->setDefinition(
            [
             new InputOption('thrift-name', 'f', InputOption::VALUE_REQUIRED, 'thrift文件名称.'),
            ]
        );
    }

    /**
     * 命令操作
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $thriftName = $input->getOption('thrift-name');
        if (! $thriftName) {
            $output->writeln(sprintf('请输入thrift文件名称!'));

            return false;
        }
        $thriftName = ucfirst($thriftName) . '.thrift';
        $path       = ThriftClient::$thriftPath;
        is_dir($path) or mkdir($path, 0777, true);
        is_file($path . $thriftName) or touch($path . $thriftName);
        if (! is_file($path . $thriftName)) {
            $output->writeln(sprintf('创建thrift文件失败!'));
        }

        return true;
    }
}
