<?php
/**
 * User: yongli
 * Date: 17/4/28
 * Time: 16:44
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

/**
 * Class Error  API错误码
 *
 * @package Config
 */
class Error
{

    protected $errorCode = [];

    public function getAllError()
    {
        $this->errorCode = [
                            0     => '', // 成功
                            1     => '登陆超时',
                            2     => '非法请求',
                            3     => '无权访问',
                            4     => '参数错误',
                            5     => '无权访问此接口',
                            10001 => '用户不存在/或被禁用',
                            10002 => '用户名或密码不能为空',
                           ];

        return $this->errorCode;
    }
}
