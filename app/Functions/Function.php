<?php
/**
 * User: yongli
 * Date: 17/4/28
 * Time: 16:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
use Config\Services;

if (!function_exists('callBack')) {
    /**
     * 接口返回函数
     *
     * @param int    $errCode
     * @param array  $data
     * @param string $msg
     */
    function callBack($errCode = 0, $data = [], $msg = '')
    {
        $errorResult = Services::error()->getAllError();
        $msg         = $msg ??  $errorResult[$errCode];
        $data = [
            'code' => $errCode,
            'data' => $data,
            'msg'  => $msg,
        ];
        echo json_encode($data);
        die();

    }

}