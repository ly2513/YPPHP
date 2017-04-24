<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Libraries;


use YP\Core\YP_Request as Request;
use YP\Core\YP_Filter as Filter;
use Config\Services;

class YP_DebugToolbar extends Filter
{

    /**
     * 我们这里没有任何事要做
     *
     * @param Request $request
     */
    public function before(Request $request)
    {

    }
    
    /**
     * 如果调试标志设置（YP_DEBUG）然后收集性能和调试信息并将其显示在工具栏
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function after(Request $request, Response $response)
    {
        $format = $response->getHeaderLine('content-type');

        if ( ! is_cli() && YP_DEBUG && strpos($format, 'html') !== false)
        {
            global $app;

            $toolbar = Services::toolbar(new App());
            $stats   = $app->getPerformanceStats();

            return $response->appendBody(
                $toolbar->run(
                    $stats['startTime'],
                    $stats['totalTime'],
                    $stats['startMemory'],
                    $request,
                    $response
                )
            );
        }
    }

}