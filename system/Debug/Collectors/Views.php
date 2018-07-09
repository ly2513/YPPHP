<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace YP\Debug\Toolbar\Collectors;

use Config\Services;
use CodeIgniter\View\RendererInterface;

/**
 * Views collector
 */
class Views extends BaseCollector {

	/**
	 * Whether this collector has data that can
	 * be displayed in the Timeline.
	 *
	 * @var bool
	 */
	protected $hasTimeline = TRUE;

	/**
	 * Whether this collector needs to display
	 * content in a tab or not.
	 *
	 * @var bool
	 */
	protected $hasTabContent = FALSE;

	/**
	 * Whether this collector has data that
	 * should be shown in the Vars tab.
	 *
	 * @var bool
	 */
	protected $hasVarData = TRUE;

	/**
	 * The 'title' of this Collector.
	 * Used to name things in the toolbar HTML.
	 *
	 * @var string
	 */
	protected $title = 'Views';

	/**
	 * Instance of the Renderer service
     *
	 * @var RendererInterface
	 */
	protected $viewer;

	//--------------------------------------------------------------------

	/**
	 * Constructor.
	 */
	public function __construct()
	{
	    $this->viewer = Services::renderer(NULL, TRUE);
	}

	//--------------------------------------------------------------------


	/**
	 * Child classes should implement this to return the timeline data
	 * formatted for correct usage.
	 *
	 * @return mixed
	 */
	protected function formatTimelineData(): array
	{
		$data = [];

		$rows = $this->viewer->getPerformanceData();

		foreach ($rows as $name => $info)
		{
			$data[] = [
                       'name'      => 'View: '.$info['view'],
                       'component' => 'Views',
                       'start'     => $info['start'],
                       'duration'  => $info['end'] - $info['start'],
                      ];
		}

		return $data;
	}

	//--------------------------------------------------------------------

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
		return [
                'View Data' => $this->viewer->getData(),
               ];
	}

	//--------------------------------------------------------------------


}
