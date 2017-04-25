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

class Home extends Controller
{

    public function index()
    {
        $this->display('index',['title'=>'你好,Twig模板引擎']);
    }
}