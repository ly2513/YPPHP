<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace CodeIgniter\Debug\Toolbar\Collectors;

use YP\Config\Services;

/**
 * Loags collector
 */
class Logs extends BaseCollector
{
    /**
     * Whether this collector has data that can
     * be displayed in the Timeline.
     *
     * @var bool
     */
    protected $hasTimeline = false;

    /**
     * Whether this collector needs to display
     * content in a tab or not.
     *
     * @var bool
     */
    protected $hasTabContent = true;

    /**
     * The 'title' of this Collector.
     * Used to name things in the toolbar HTML.
     *
     * @var string
     */
    protected $title = 'Logs';

    //--------------------------------------------------------------------
    /**
     * Builds and returns the HTML needed to fill a tab to display
     * within the Debug Bar
     *
     * @return string
     */
    public function display(): string
    {
        $parser = \Config\Services::parser(SYSTEM_PATH . 'Debug/Toolbar/Views/');
        $logger = Services::log(true);
        $logs   = $logger->logCache;
        if (empty($logs) || !is_array($logs)) {
            return '<p>Nothing was logged. If you were expecting logged items, ensure that LoggerConfig file has the correct threshold set.</p>';
        }

        return $parser->setData([
            'logs' => $logs
        ])->render('_logs.tpl');
    }

    //--------------------------------------------------------------------
}
