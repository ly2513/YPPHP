<?php
/**
 * User: yongli
 * Date: 17/9/29
 * Time: 14:51
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Console\Thrift;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thrift\ClassLoader\ThriftClassLoader;
use Config\ThriftService;
use YP\Libraries\Thrift\YP_ThriftService;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Service', ThriftService::$genDir);
$loader->register();

class WorkCommand extends Command
{
    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('thrift:work')->setDescription('启用一个thrift服务.')->setDefinition(
            [
             new InputOption('thrift-name', 'p', InputOption::VALUE_REQUIRED, 'thrift服务名称.'),
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
        $serviceName = $input->getOption('thrift-name');
        if (! $serviceName) {
            $output->writeln(sprintf('请输入thrift服务名称!'));

            return false;
        }

        return true;
    }
}
