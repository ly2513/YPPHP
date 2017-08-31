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
            new InputOption('thrift-name', 'name', InputOption::VALUE_REQUIRED, 'thrift文件名称.'),
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
        $thriftName = ucfirst($thriftName);
        print_r($thriftName);
//        // 获得配置信息
//        $thrift = new ThriftClient();
//        // 获得thrift 目录
//        $thriftDir = $input->getOption('thrift-dir');
//        $thriftDir = $thriftDir ? $thriftDir : $thrift->genDir;
//        $file      = pathinfo($thriftPath);
//        // 自动创建thrift的编译文件到指定目录
//        is_dir($thrift->genDir) or mkdir($thrift->genDir, 0777, true);
//        $sourceDir = ROOT_PATH . 'gen-php/' . $file['filename'];
//        $command   = 'thrift -gen php ' . $thriftPath . ' && cp -R ' . $sourceDir . ' ' . $thriftDir . ' && rm -rf ' . ROOT_PATH . 'gen-php';
//        // 执行命令
//        system($command, $status);
//        unset($file, $command);
//        if ($status) {
//            $output->writeln(sprintf('创建thrift编译文件失败!'));
//        }
    }

    /**
     * 创建任务
     *
     * @param $jobDir
     * @param $jobName
     * @param $description
     * @param $queueName
     * @param $output
     */
    private function createJob($jobDir, $jobName, $description, $queueName, $output)
    {
        $jobName = $jobName ? $jobName : 'default';
        $jobFile = $jobDir . ucfirst($jobName) . 'Job.php';
        is_file($jobFile) or touch($jobFile);
        $str = <<<EOT
<?php 
/**
 * 
 *
 */
 
 require APPLICATION_ROOT . 'application/third_party/TradingMax/Model/EmailModel.php';
 
class 
EOT;
        $str .= ucfirst($jobName . 'Job') . PHP_EOL;
        $str .= <<<EOT
{
    protected \$email;
    
    /**
     * 运行任务
     *
     */
    public function perform()
    {
        //sleep(120);
        
        \$this->email = new EmailModel;

        \$status = \$this->email->sendEmail('测试队列发送邮件', ['liyong@addnewer.com'], 'TradingMax');
        if(!\$status) {
            echo false;
        }
    }
    
}
EOT;
        file_put_contents($jobFile, $str);
        $description = $description ? $description : 'Describe the function of the queue';
        //
        $args = [
            'time'  => time(),
            'array' => ['test' => $description],
        ];
        try {
            // 队列ID
            $jobId = Resque::enqueue($queueName, $jobName . 'Job', $args, true);
            $output->writeln(sprintf('Create queue job success, the queue job id is "<info>%s</info>"', $jobId));
        } catch (InvalidArgumentException $e) {
            $output->writeln(sprintf('Create queue job error, the error message is "<info>%s</info>"',
                $e->getMessage()));

        } catch (Resque_Exception $e) {
            $output->writeln(sprintf('Create queue job error, the error message is "<info>%s</info>"',
                $e->getMessage()));
        }
    }

}
