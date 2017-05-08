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
        $data        = [
            'code' => $errCode,
            'data' => $data,
            'msg'  => $msg,
        ];
        echo json_encode($data);
        die();

    }

    /**
     * 设置分页
     *
     * @param     $row          总条数
     * @param     $url          跳转链接
     * @param     $uri_segment  当前页码
     * @param int $per_page     每页显示多少条
     *
     * @return mixed
     */
    function setPageConfig($row, $url, $uri_segment, $per_page = 10)
    {
        $config['base_url']          = $url;
        $config['total_rows']        = $row;
        $config['per_page']          = $per_page;//每页显示多少条
        $config['uri_segment']       = $uri_segment;
        $config['num_links']         = 2;//数量链接
        $config['page_query_string'] = true;
        $config['full_tag_open']     = '<ul>';
        $config['full_tag_close']    = '</ul>';
        $config['first_link']        = '首页';
        $config['first_tag_open']    = '<li class="pre">';
        $config['first_tag_close']   = '</li>';
        $config['last_link']         = '最后一页';
        $config['last_tag_open']     = '<li>';
        $config['last_tag_close']    = '</li>';
        $config['next_link']         = '下一页';
        $config['next_tag_open']     = '<li class="next">';//下一页
        $config['next_tag_close']    = '</li>';
        $config['prev_link']         = '上一页';
        $config['prev_tag_open']     = '<li>';
        $config['prev_tag_close']    = '</li>';
        $config['cur_tag_open']      = '<li class="active"><a>';//当前页
        $config['cur_tag_close']     = '</a></li>';
        $config['num_tag_open']      = '<li class="num">';
        $config['num_tag_close']     = '</li>';
        $config['use_page_numbers']  = true;

        return $config;
    }

}