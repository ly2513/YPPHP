<?php
/**
 * User: yongli
 * Date: 17/8/29
 * Time: 22:55
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Thrift;

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
        $this->setName('thrift:create')->setDescription('生成thrift文件.')->setDefinition([
            new InputOption('thrift-name', 'f', InputOption::VALUE_REQUIRED, 'thrift文件名称.'),
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
        $thriftName = $input->getOption('thrift-name');
        $thriftName = ucfirst($thriftName) . '.thrift';
        $path       = APP_PATH . 'ThirdParty/Thrift/Thrift/';
        is_dir($path) or mkdir($path, 0777, true);
        is_file($path . $thriftName) or touch($path . $thriftName);
        if(!is_file($path . $thriftName)){
            $output->writeln(sprintf('创建thrift文件失败!'));
        }
    }

}
