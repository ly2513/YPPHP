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
 * Base Toolbar collector
 */
class BaseCollector
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
    protected $hasTabContent = false;

    /**
     * Whether this collector has data that
     * should be shown in the Vars tab.
     *
     * @var bool
     */
    protected $hasVarData = false;

    /**
     * The 'title' of this Collector.
     * Used to name things in the toolbar HTML.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Gets the Collector's title.
     *
     * @param bool $safe
     *
     * @return string
     */
    /**
     * 获得收集器的名称
     *
     * @param bool $safe
     *
     * @return string
     */
    public function getTitle($safe = false): string
    {
        if ($safe) {
            return str_replace(' ', '-', strtolower($this->title));
        }

        return $this->title;
    }

    /**
     * Returns any information that should be shown next to the title.
     *
     * @return string
     */
    public function getTitleDetails(): string
    {
        return '';
    }

    /**
     * Does this collector need it's own tab?
     *
     * @return bool
     */
    public function hasTabContent(): bool
    {
        return (bool)$this->hasTabContent;
    }

    /**
     * Does this collector have information for the timeline?
     *
     * @return bool
     */
    public function hasTimelineData(): bool
    {
        return (bool)$this->hasTimeline;
    }

    /**
     * Grabs the data for the timeline, properly formatted,
     * or returns an empty array.
     *
     * @return array
     */
    public function timelineData(): array
    {
        if (!$this->hasTimeline) {
            return [];
        }

        return $this->formatTimelineData();
    }

    /**
     * Does this Collector have data that should be shown in the
     * 'Vars' tab?
     *
     * @return mixed
     */
    public function hasVarData()
    {
        return (bool)$this->hasVarData;
    }

    /**
     * Gets a collection of data that should be shown in the 'Vars' tab.
     * The format is an array of sections, each with their own array
     * of key/value pairs:
     *
     *  $data = [
     *      'section 1' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *      'section 2' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *  ];
     *
     * @return null
     */
    public function getVarData()
    {
        return null;
    }

    /**
     * Child classes should implement this to return the timeline data
     * formatted for correct usage.
     *
     * Timeline data should be formatted into arrays that look like:
     *
     *  [
     *      'name'      => 'Database::Query',
     *      'component' => 'Database',
     *      'start'     => 10       // milliseconds
     *      'duration'  => 15       // milliseconds
     *  ]
     *
     * @return mixed
     */
    protected function formatTimelineData(): array
    {
        return [];
    }

    /**
     * Builds and returns the HTML needed to fill a tab to display
     * within the Debug Bar
     *
     * @return string
     */
    public function display(): string
    {
        return '';
    }

    /**
     * Clean Path
     *
     * This makes nicer looking paths for the error output.
     *
     * @param    string $file
     *
     * @return    string
     */
    public function cleanPath($file)
    {
        if (strpos($file, APPPATH) === 0) {
            $file = 'APP_PATH/' . substr($file, strlen(APP_PATH));
        } elseif (strpos($file, BASEPATH) === 0) {
            $file = 'SYSTEM_PATH/' . substr($file, strlen(SYSTEM_PATH));
        } elseif (strpos($file, FCPATH) === 0) {
            $file = 'FRONT_PATH/' . substr($file, strlen(FRONT_PATH));
        }

        return $file;
    }

}
