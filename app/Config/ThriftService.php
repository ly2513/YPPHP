<?php
/**
 * User: yongli
 * Date: 17/8/27
 * Time: 22:57
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

class ThriftService
{
    /**
     * Thrift 服务端 IP
     *
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * Thrift 服务端 端口
     *
     * @var int
     */
    public $port = 9090;

    /**
     * 使用的协议,默认TBinaryProtocol,可更改
     *
     * @var string
     */
    public $thriftProtocol = 'TBinaryProtocol';

    /**
     * 使用的传输类,默认是TBufferedTransport，可更改
     *
     * @var string
     */
    public $thriftTransport = 'TBufferedTransport';
    
    /**
     * 编译好的thrift存放路径
     *
     * @var string
     */
    public $genDir = APP_PATH . 'ThirdParty/Thrift/gen-php';
}