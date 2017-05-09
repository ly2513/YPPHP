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

/**
 * 并行查询 Post
 *
 * @param      $url_array
 * @param  int $wait_usec
 *
 * @return array|bool
 */
function multiCurlPost($url_array, $wait_usec = 0)
{
    if (!is_array($url_array)) {
        return false;
    }
    $wait_usec = intval($wait_usec);
    $data      = [];
    $handle    = [];
    $running   = 0;
    $mh        = curl_multi_init(); // multi curl handler
    $i         = 0;
    foreach ($url_array as $url_info) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_info['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return don't print
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $url_info['data']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($url_info['data'])
            ]);
        curl_multi_add_handle($mh, $ch); // 把 curl resource 放进 multi curl handler 里
        $handle[$i++] = $ch;
    }
    /* 执行 */
    do {
        curl_multi_exec($mh, $running);
        if ($wait_usec > 0) { /* 每个 connect 要间隔多久 */
            usleep($wait_usec); // 250000 = 0.25 sec
        }
    } while ($running > 0);
    /* 读取资料 */
    foreach ($handle as $i => $ch) {
        $content  = curl_multi_getcontent($ch);
        $data[$i] = (curl_errno($ch) == 0) ? $content : false;
    }
    /* 移除 handle*/
    foreach ($handle as $ch) {
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);

    return $data;
}

