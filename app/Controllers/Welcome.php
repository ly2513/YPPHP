<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 10:54
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Controllers;

use YP\Core\YP_Controller as Controller;

class Welcome extends Controller
{
    public function index()
    {
        p($_SERVER);
    }
    
    
}