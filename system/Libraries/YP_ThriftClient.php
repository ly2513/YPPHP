<?php
/**
 * User: yongli
 * Date: 17/8/25
 * Time: 19:18
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use Thrift\ClassLoader\ThriftClassLoader;
use Config\ThriftClient;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\TSocketPool;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TBufferedTransport;

class YP_ThriftClient
{
    /**
     * 服务IP
     *
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * 服务端口
     *
     * @var int
     */
    private $port = 9090;

    private $time_out = 84800;

    public function __construct(ThriftClient $config)
    {
        $this->host     = $config->host;
        $this->port     = $config->port;
        $this->time_out = $config->time_out;
    }

    public function connet()
    {
        $startTime  = $this->getMillisecond();
        $thrift_gen = APP_PATH . 'ThirdParty/Thrift/gen-php';
        $loader     = new ThriftClassLoader();
//        $loader->registerNamespace('Thrift', $thrift_lib);
        $loader->registerDefinition('testname', $thrift_gen);
        $loader->register();
        $socket = new TSocket($this->host, $this->port);
        $socket->setSendTimeout($this->time_out);// 设置发送超时
        $socket->setRecvTimeout($this->time_out);// 设置接收超时
        //$transport = new TBufferedTransport($socket); #传输方式：这个要和服务器使用的一致 [go提供后端服务,迭代10000次2.6 ~ 3s完成]
        $transport = new TFramedTransport($socket); #传输方式：这个要和服务器使用的一致[go提供后端服务,迭代10000次1.9 ~ 2.1s完成，比TBuffer快了点]
        $protocol  = new TBinaryProtocol($transport);  #传输格式：二进制格式
        $client    = new \batu\demo\batuThriftClient($protocol);# 构造客户端
        $transport->open();
        $socket->setDebug(true);
        for ($i = 1; $i < 11; $i++) {
            $item      = [];
            $item["a"] = "batu.demo";
            $item["b"] = "test" + $i;
            $result    = $client->CallBack(time(), "php client", $item); # 对服务器发起rpc调用
            echo "PHPClient Call->" . implode('', $result) . "<br>";
        }
        $s          = new \batu\demo\Article();
        $s->id      = 1;
        $s->title   = '插入一篇测试文章';
        $s->content = '我就是这篇文章内容';
        $s->author  = 'liuxinming';
        $client->put($s);
        $s->id      = 2;
        $s->title   = '插入二篇测试文章';
        $s->content = '我就是这篇文章内容';
        $s->author  = 'liuxinming';
        $client->put($s);
        $endTime = getMillisecond();
        echo "本次调用用时: :" . $endTime . "-" . $startTime . "=" . ($endTime - $startTime) . "毫秒<br>";
        function getMillisecond()
        {
            list($t1, $t2) = explode(' ', microtime());

            return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
        }

        $transport->close();
        //        $transport = new TBufferedTransport($socket);
        //        //$transport = new TFramedTransport($socket);
        //        $protocol = new TBinaryProtocol($transport);
        //        $client   = new \testname\searcher_thriftClient($protocol);#
        //        //print_r($client);exit;
        //        $transport->open();
        //        $socket->setDebug(true);
        //        //echo $tdata['pdata'];exit;
        //        $ret = $client->search($data['head'], 1, $data['query'], $data['pdata']);
        //        //error_code  result_num results
        //        if ($ret->error_code != 0) {
        //            ErrorCode::logErrorMsg(ErrorCode::LEVEL_ERROR, ErrorCode::ERR_THRIFT);
        //        } else {
        //            $transport->close();
        //
        //            return $ret->results;
        //        }
    }

    /**
     * @return float
     */
    public function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

}

