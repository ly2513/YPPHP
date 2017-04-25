<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 17:15
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Debug;

use YP\Config\Config;

/**
 * Class YP_Toolbar 调试工具栏类
 *
 * @package YP\Debug
 */
class YP_Toolbar
{
    /**
     * 收集器
     *
     * @var array
     */
    protected $collectors = [];

    /**
     * 程序开始时间
     *
     * @var
     */
    protected $startTime;

    /**
     * YP_Toolbar constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        foreach ($config->toolbarCollectors as $collector) {
            if (!class_exists($collector)) {
                // @todo Log this!
                continue;
            }
            $this->collectors[] = new $collector();
        }
    }

    /**
     * 运行工具栏
     *
     * @param $startTime
     * @param $totalTime
     * @param $startMemory
     * @param $request
     * @param $response
     *
     * @return string
     */
    public function run($startTime, $totalTime, $startMemory, $request, $response): string
    {
        $this->startTime = $startTime;
        // Data items used within the view.
        $collectors      = $this->collectors;
        $totalTime       = $totalTime * 1000;
        $totalMemory     = number_format((memory_get_peak_usage() - $startMemory) / 1048576, 3);
        $segmentDuration = $this->roundTo($totalTime / 7, 5);
        $segmentCount    = (int)ceil($totalTime / $segmentDuration);
        $varData         = $this->collectVarData();
        ob_start();
        include(__DIR__ . '/Views/toolbar.tpl.php');
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    //--------------------------------------------------------------------
    /**
     * Called within the view to display the timeline itself.
     *
     * @param int $segmentCount
     * @param int $segmentDuration
     *
     * @return string
     */
    protected function renderTimeline(int $segmentCount, int $segmentDuration): string
    {
        $displayTime = $segmentCount * $segmentDuration;
        $rows        = $this->collectTimelineData();
        $output      = '';
        foreach ($rows as $row) {
            $output .= "<tr>";
            $output .= "<td>{$row['name']}</td>";
            $output .= "<td>{$row['component']}</td>";
            $output .= "<td style='text-align: right'>" . number_format($row['duration'] * 1000, 2) . " ms</td>";
            $output .= "<td colspan='{$segmentCount}' style='overflow: hidden'>";
            $offset = ((($row['start'] - $this->startTime) * 1000) / $displayTime) * 100;
            $length = (($row['duration'] * 1000) / $displayTime) * 100;
            $output .= "<span class='timer' style='left: {$offset}%; width: {$length}%;' title='" . number_format($length,
                    2) . "%'></span>";
            $output .= "</td>";
            $output .= "</tr>";
        }

        return $output;
    }

    /**
     * 根据时间线先后顺序进行收集器的排序,然后将其返回
     *
     * @return array
     */
    protected function collectTimelineData(): array
    {
        $data = [];
        // 收集
        foreach ($this->collectors as $collector) {
            if (!$collector->hasTimelineData()) {
                continue;
            }
            $data = array_merge($data, $collector->timelineData());
        }

        // Sort it
        return $data;
    }

    /**
     * 返回所有应在“变量”选项卡中显示模块的数据阵列
     *
     * @return array
     */
    protected function collectVarData(): array
    {
        $data = [];
        foreach ($this->collectors as $collector) {
            if (!$collector->hasVarData()) {
                continue;
            }
            $data = array_merge($data, $collector->getVarData());
        }

        return $data;
    }

    /**
     * 四舍五入取增量值
     *
     * @param     $number
     * @param int $increments
     *
     * @return float
     */
    protected function roundTo($number, $increments = 5)
    {
        $increments = 1 / $increments;

        return (ceil($number * $increments) / $increments);
    }
}