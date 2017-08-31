<?php
/**
 * User: yongli
 * Date: 17/8/30
 * Time: 17:43
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Thrift;

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
        $this->setName('thrift:gen')->setDescription('编译thrift文件.')->setDefinition([
            new InputOption('thrift-path', 'p', InputOption::VALUE_REQUIRED, 'thrift文件目录.'),
            new InputOption('thrift-dir', 'd', InputOption::VALUE_NONE, '指定将编译好的thrift文件所存放的目录.'),
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
        $thriftPath = $input->getOption('thrift-path');
        // 获得配置信息
        $thrift = new ThriftClient();
        // 获得thrift 目录
        $thriftDir = $input->getOption('thrift-dir');
        $thriftDir = $thriftDir ? $thriftDir : $thrift->genDir;
        $file      = pathinfo($thriftPath);
        // 自动创建thrift的编译文件到指定目录
        is_dir($thrift->genDir) or mkdir($thrift->genDir, 0777, true);
        $sourceDir = ROOT_PATH . 'gen-php/' . $file['filename'];
        $command   = 'thrift -gen php ' . $thriftPath . ' && cp -R ' . $sourceDir . ' ' . $thriftDir . ' && rm -rf ' . ROOT_PATH . 'gen-php';
        // 执行命令
        system($command, $status);
        unset($file, $command);
        if ($status) {
            $output->writeln(sprintf('创建thrift编译文件失败!'));
        }
    }
}