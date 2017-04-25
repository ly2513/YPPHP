<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 16:52
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Core\YP_IncomingRequest as IncomingRequest;
use YP\Core\YP_Response as Response;

interface YP_FilterInterface
{

    /**
     * 这过滤器必须能处理任何需要过滤的
     * 默认情况下，在正常执行期间,它不应该返回任何东西。当一个异常被发现时，它应该返回一个YP\Core\YP_IncomingRequest实例
     * 如果是这样做，脚本执行结束，响应将被发送回客户端，允许错误页面、重定向，等
     *
     * @param YP_IncomingRequest $request
     *
     * @return mixed
     */
    public function before(IncomingRequest $request);

    /**
     * 允许After过滤器在需要时检查和修改响应对象
     * 此方法不允许任何方法停止执行其他After过滤器，没有抛出异常或错误
     *
     * @param YP_IncomingRequest $request
     * @param YP_Response        $response
     *
     * @return mixed
     */
    public function after(IncomingRequest $request, Response $response);
}