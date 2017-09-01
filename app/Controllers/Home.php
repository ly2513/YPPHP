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
use YP\Libraries\Thrift\AddressManager;

class Home extends Controller
{

    /**
     * 网站信息
     */
    public function index()
    {
        $time         = microtime(true) * 1000;
        $elapsed_time = number_format(($time - START_TIME), 0);
        $this->assign('title', '你好,Twig模板引擎');
        $this->assign('view_path', 'app/Views/Home/' . $this->method . $this->extension);
        $this->assign('controller_path', 'app/Controller/Home.php');
        $this->assign('evn', ENVIRONMENT);
        $this->assign('elapsed_time', $elapsed_time);
        $this->assign('version', VERSION);
        $this->display();
    }

    public function testThrift()
    {
        $thrift = Services::thrift_client();
        $thrift->connet();
        P($thrift);
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
            'HelloWorldService' => [
                '127.0.0.4:9090'
            ],
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

    public function testLog(){
        $log = \Config\Services::log();
        $s = $log->error('we are success');
        P($s);
    }

}