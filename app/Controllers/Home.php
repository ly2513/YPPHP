<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:21
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Controllers;

use YP\Core\YP_Controller as Controller;
use YP\Config\Services;

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
        
        P($thrift);
    }

}