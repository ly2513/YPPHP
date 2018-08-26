<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Console\Crontab;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Libraries\Crontab\CronManager;

class CrontabCommand extends Command
{
    /**
     * 定时任务日志目录
     *
     * @var string
     */
    private $logPath = CACHE_PATH . 'Logs/Crontab/';

    /**
     * 命令配置
     */
    protected function configure()
    {
        $this->setName('crontab:create')->setDescription('Create a crontab.')->setDefinition([
            new InputOption('param', 'p', InputOption::VALUE_REQUIRED, 'Create a crontab name.'),
        ]);
    }

    /**
     * 命令操作
     *
     * @param InputInterface  $input  命令的输入
     * @param OutputInterface $output 命令的输出
     *
     * @return bool
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new CronManager();
        // 守护进程方式启动
        $manager->daemon = true;
        $manager->argv = $input->getOption('param');
        is_dir($this->logPath) OR mkdir($this->logPath, 0755, true);
        $log = $this->logPath . date('Y-m-d', time()) . '.log';
        is_file($log) || touch($log);
        // 设置输出重定向,守护进程模式才生效
        $manager->output = $log;

        // crontab格式解析
        $manager->taskInterval('每个小时的1,3,5分钟时运行一次', '1,3,5 * * * *', function(){
            echo "每个小时的1,3,5分钟时运行一次\n";
        });

        $manager->taskInterval('每1分钟运行一次', '*/1 * * * *', function(){
            echo "每1分钟运行一次\n";
        });

        $manager->taskInterval('每天凌晨运行', '0 0 * * *', function(){
            echo "每天凌晨运行\n";
        });

        $manager->taskInterval('每秒运行一次', 's@1', function(){
            echo "每秒运行一次\n";
        });

        $manager->taskInterval('每分钟运行一次', 'i@1', function(){
            echo "每分钟运行一次\n";
        });

        $manager->taskInterval('每小时钟运行一次', 'h@1', function(){
            echo "每小时运行一次\n";
        });

        $manager->taskInterval('指定每天00:00点运行', 'at@00:00', function(){
            echo "指定每天00:00点运行\n";
        });

        $manager->run();
    }
}