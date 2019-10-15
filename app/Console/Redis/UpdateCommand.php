<?php
/**
 * User: yongli
 * Date: 17/9/25
 * Time: 10:22
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Console\Redis;

use App\Controllers\Shell\UpdateRedis;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    /**
     * 命令配置
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('redis:update')->setDescription('更新Redis中的数据.');
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
        $option = new UpdateRedis();
        $option->updateRedis();

        return true;
    }
}