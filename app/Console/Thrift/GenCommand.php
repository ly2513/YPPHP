<?php
/**
 * User: yongli
 * Date: 17/9/29
 * Time: 14:50
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Console\Thrift;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Config\ThriftClient;

class GenCommand extends Command
{
    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('thrift:gen')->setDescription('编译thrift文件.')->setDefinition(
            [
             new InputOption('thrift-path', 'p', InputOption::VALUE_REQUIRED, 'thrift文件目录.'),
             new InputOption('thrift-dir', 'd', InputOption::VALUE_NONE, '指定将编译好的thrift文件所存放的目录.'),
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
        $thriftPath = $input->getOption('thrift-path');
        if (! $thriftPath) {
            $output->writeln(sprintf('请输入需要编译的文件!'));

            return false;
        }
        // 获得thrift 目录
        $thriftDir = $input->getOption('thrift-dir');
        $thriftDir = $thriftDir ? $thriftDir : ThriftClient::$genPath;
        $file      = pathinfo($thriftPath);
        // 自动创建thrift的编译文件到指定目录
        is_dir($thriftDir) or mkdir($thriftDir, 0777, true);
        $sourceDir = ROOT_PATH . 'gen-php/';
        $command   = 'thrift -gen php ' . $thriftPath . ' && cp -R ' . $sourceDir . ' ' . $thriftDir . ' && rm -rf ' . ROOT_PATH . 'gen-php';
        // 执行命令
        system($command, $status);
        unset($file, $command);
        if ($status) {
            $output->writeln(sprintf('创建thrift编译文件失败!'));
        }

        return true;
    }
}
