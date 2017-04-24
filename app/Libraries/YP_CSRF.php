<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 00:12
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Libraries;

use YP\Core\YP_Filter as Filter;
use YP\Core\YP_Request as Request;
use Config\Services;

class CSRF extends Filter
{

    /**
     * 做任何处理这个过滤器需要做。默认情况下，它不应该返回任何在正常执行。但是，当发现异常状态时，它应该返回一个实例
     * 如果是这样，脚本执行结束，响应将被发送回客户端，允许错误页面、重定向，等
     *
     * @param Request $request
     */
    public function before(Request $request)
    {
        if (is_cli())
        {
            return;
        }

        $security = Services::security();

        $security->CSRFVerify($request);
    }

    /**
     * 我们这里没有任何事要做
     *
     * @param Request  $request
     * @param Response $response
     */
    public function after(Request $request, Response $response)
    {
    }

}