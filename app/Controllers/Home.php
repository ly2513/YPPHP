<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:21
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers;

use YP\Core\YP_Controller as Controller;
use YP\Config\Services;
use YP\Libraries\Thrift\YP_ThriftClient;

/**
 * 框架默认控制器
 *
 * Class Home
 *
 * @package App\Controllers
 */
class Home extends Controller
{
    /**
     * 框架首页信息
     */
    public function index()
    {
        //        $XHPROF_ROOT  =  dirname(ROOT_PATH) . '/xhprof/xhprof_lib/utils/';
        $time         = microtime(true) * 1000;
        $elapsed_time = number_format(($time - START_TIME), 0);
        $data         = [
            'view_path'       => 'app/Views/' . $this->controller . '/' . $this->method . $this->extension,
            'controller_path' => 'app/Controller/' . $this->controller . '.php',
            'evn'             => ENVIRONMENT,
            'elapsed_time'    => $elapsed_time,
            'version'         => VERSION,
            'doc_url'         => 'https://ly2513.gitbooks.io/youpin/content/'
        ];
        //        $xhprof_data = xhprof_disable();
        //        include_once $XHPROF_ROOT . "xhprof_lib.php";
        //        include_once $XHPROF_ROOT . "xhprof_runs.php";
        //        $xhprof_runs = new \XHProfRuns_Default();
        //        $run_id      = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
        $this->display($data);
    }

    public function testThrift()
    {
        Services::thriftClient();
        $client = YP_ThriftClient::instance('HelloWorld');
        var_export($client->sayHello("TOM"));
        // ****************** 测试代码 ***************
        //        ThriftClient::config([
        //            'HelloWorld' => [
        //                'addresses'        => [
        //                    '127.0.0.1:9090',
        //                    //'127.0.0.2:9191', //设置的一个故障地址，用来测试客户端故障节点踢出功能
        //                ],
        //                'thrift_protocol'  => 'TBinaryProtocol',        // 不设置默认为TBinaryProtocol
        //                'thrift_transport' => 'TBufferedTransport',  // 不设置默认为TBufferedTransport
        //                'service_dir'      => __DIR__ . '/../Services/'   // 不设置默认是__DIR__.'/../Services/',即上一级目录下的Services目录
        //            ],
        //            'UserInfo'   => [
        //                'addresses' => [
        //                    '127.0.0.1:9090'
        //                ],
        //            ],
        //        ]);
        //        $client = ThriftClient::instance('HelloWorld');
        //        // 同步
        //        echo "sync send and recv sayHello(\"TOM\")\n";
        //        var_export($client->sayHello("TOM"));
        //        // 异步
        //        echo "\nasync send request asend_sayHello(\"JERRY\") asend_sayHello(\"KID\")\n";
        //        $client->asend_sayHello("JERRY");
        //        $client->asend_sayHello("KID");
        //        // 这里是其它业务逻辑
        //        echo "sleep 1 second now\n";
        //        sleep(1);
        //        echo "\nasync recv response arecv_sayHello(\"KID\") arecv_sayHello(\"JERRY\")\n";
        //        var_export($client->arecv_sayHello("KID"));
        //        var_export($client->arecv_sayHello("JERRY"));
        //        echo "\n";
    }

    public function testAddress()
    {
        AddressManager::config([
            'HelloWorld'        => [
                '127.0.0.1:9090',
                '127.0.0.2:9090',
                '127.0.0.3:9090',
            ],
            'HelloWorldService' => ['127.0.0.4:9090'],
        ]);
        echo "\n剔除address 127.0.0.1:9090 127.0.0.2:9090，放入故障address列表\n";
        AddressManager::kickAddress('127.0.0.1:9090');
        AddressManager::kickAddress('127.0.0.2:9090');
        echo "\n打印故障address列表\n";
        var_export(AddressManager::getBadAddressList());
        echo "\n获取HelloWorld服务的一个可用address\n";
        var_export(AddressManager::getOneAddress('HelloWorld'));
        echo "\n恢复address 127.0.0.2:9090\n";
        var_export(AddressManager::recoverAddress('127.0.0.2:9090'));
        echo "\n打印故障address列表\n";
        var_export(AddressManager::getBadAddressList());
        echo "\n配置有更改，md5会改变，则故障address列表自动清空\n";
        AddressManager::config([
            'HelloWorld' => [
                '127.0.0.2:9090',
                '127.0.0.3:9090',
            ],
        ]);
        echo "\n打印故障address列表\n";
        var_export(AddressManager::getBadAddressList());
    }

    public function testLog()
    {
        $log = \Config\Services::log();
        $s   = $log->error('we are success');
        P($s);
    }

    public function getVal()
    {
        $a = 'worked';
        xdebug_debug_zval($a);
        print_r($a);
    }

    public function testEmail()
    {
    }

    public function testQueue()
    {
        $queue = \Config\Services::queue();
        P($queue);
    }

    public function funDdd()
    {
        //        foreach (xrange(1, 1000000) as $num) {
        //            echo $num, "\n";
        //        }
        //        $range = xrange(1, 1000000);
        //        var_dump($range); // object(Generator)#1
        //        var_dump($range instanceof Iterator); // bool(true)
        $gen = gen();
        var_dump($gen->current());    // string(6) "yield1"
        var_dump($gen->send('ret1')); // string(4) "ret1"   (the first var_dump in gen)
        // string(6) "yield2" (the var_dump of the ->send() return value)
        var_dump($gen->send('ret2')); // string(4) "ret2"   (again from within gen)
    }
}
