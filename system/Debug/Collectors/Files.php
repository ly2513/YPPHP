<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Debug\Toolbar\Collectors;

/**
 * Files collector
 */
class Files extends BaseCollector {

    /**
     * Whether this collector has data that can
     * be displayed in the Timeline.
     *
     * @var bool
     */
    protected $hasTimeline = FALSE;

    /**
     * Whether this collector needs to display
     * content in a tab or not.
     *
     * @var bool
     */
    protected $hasTabContent = TRUE;

    /**
     * The 'title' of this Collector.
     * Used to name things in the toolbar HTML.
     *
     * @var string
     */
    protected $title = 'Files';

    //--------------------------------------------------------------------
    /**
     * Returns any information that should be shown next to the title.
     *
     * @return string
     */
    public function getTitleDetails(): string
    {
        return '( ' . (int) count(get_included_files()) . ' )';
    }

    //--------------------------------------------------------------------
    /**
     * Builds and returns the HTML needed to fill a tab to display
     * within the Debug Bar
     *
     * @return string
     */
    public function display(): string
    {
        $parser    = \Config\Services::parser(SYSTEM_PATH . 'Debug/Toolbar/Views/');
        $rawFiles  = get_included_files();
        $coreFiles = [];
        $userFiles = [];
        foreach ($rawFiles as $file) {
            $path = $this->cleanPath($file);
            if (strpos($path, 'SYSTEM_PATH') !== FALSE) {
                $coreFiles[] = [
                                'name' => basename($file),
                                'path' => $path,
                               ];
            } else {
                $userFiles[] = [
                                'name' => basename($file),
                                'path' => $path,
                               ];
            }
        }
        sort($userFiles);
        sort($coreFiles);

        return $parser->setData([
                                 'coreFiles' => $coreFiles,
                                 'userFiles' => $userFiles,
                                ])->render('_files.tpl');
    }

    //--------------------------------------------------------------------
}
