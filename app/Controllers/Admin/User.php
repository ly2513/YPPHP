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
use Admin\UserModel as UserModel;
use Config\Services;

class User extends Controller
{

    public function testSession()
    {
        $session = Services::session();
        P($session);
    }

    public function testValidate()
    {
        // 校验规则
        $rules = [
            'age'      => 'required|min_length[2]',
            'username' => 'required|max_length[10]',
            'email'    => 'required|valid_email'
        ];
        // 提示信息
        $message = [
            'age'      => [
                'min_length' => '最小长度为2',
                'required'   => 'age不能为空'
            ],
            'username' => [
                'max_length' => '最大长度为10',
                'required'   => '名称必填',
            ],
            'email'    => [
                'required'    => '邮箱不能为空',
                'valid_email' => '请检查电子邮件字段,无效的邮箱地址'
            ]
        ];
        // 开始校验
        if (!$this->validate($this->request, $rules, $message)) {
            // 校验失败,输出错误信息
            P($this->errors);
        }
        // 获得$_GET数据
        $param = $this->request->getGet();
        // 获得$_POST数据
        $param1 = $this->request->getPost();
        P($param);
        P($param1);
        $userInfo = UserModel::select('id', 'username', 'email', 'photo_url')->get()->toArray();
        //        P($userInfo);
        $this->assign('company', '优品未来');
        $this->assign('title', 'Admin下的User');
        $this->display();
    }

    public function testJsonSchema()
    {
        $this->checkSchema();
        //        P($this->input->json);
        die;
    }
}