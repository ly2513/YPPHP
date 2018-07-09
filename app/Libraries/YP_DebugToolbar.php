<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 00:13
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Libraries;

use YP\Core\YP_IncomingRequest as IncomingRequest;
use YP\Core\YP_Response as Response;
use YP\Core\YP_FilterInterface as FilterInterface;
use Config\Services;
use Config\App;

/**
 * 调试工具类
 *
 * Class YP_DebugToolbar
 *
 * @package APP\Libraries
 */
class YP_DebugToolbar implements FilterInterface
{
    
    /**
     * 我们这里没有任何事要做
     *
     * @param IncomingRequest $request
     *
     * @return mixed
     */
    public function before(IncomingRequest $request)
    {
    }

    /**
     * 如果调试标志设置（YP_DEBUG）然后收集性能和调试信息并将其显示在工具栏
     *
     * @param IncomingRequest $request
     * @param Response        $response
     *
     * @return mixed
     */
    public function after(IncomingRequest $request, Response $response)
    {
        $format = $response->getHeaderLine('content-type');
        if (! is_cli() && YP_DEBUG && strpos($format, 'html') !== false) {
            global $app;
            $toolbar = Services::toolbar(new App());
            $stats   = $app->getPerformanceStats();

            return $response->appendBody($toolbar->run(
                $stats['startTime'],
                $stats['totalTime'],
                $stats['startMemory'],
                $request,
                $response
            ));
        }
    }
}
