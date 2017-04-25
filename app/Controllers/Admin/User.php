<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 14:03
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Controllers\Admin;

use YP\Core\YP_Controller as Controller;

class User extends Controller
{
    public function add()
    {
        $this->assign('company', '优品未来');
        $this->assign('title', 'Admin下的User');
        $this->display();

    }
}