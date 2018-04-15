<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Debug\Toolbar\Collectors;

use Config\Services;

/**
 * Routes collector
 */
class Routes extends BaseCollector {

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
	protected $title = 'Routes';

	//--------------------------------------------------------------------

	/**
	 * Builds and returns the HTML needed to fill a tab to display
	 * within the Debug Bar
	 *
	 * @return string
	 */
	public function display(): string
	{
		$parser = Services::parser(SYSTEM_PATH . 'Debug/Toolbar/Views/');

		$rawRoutes = Services::routes(TRUE);
		$router    = Services::router(NULL, TRUE);

		/*
		 * Matched Route
		 */
		$route = $router->getMatchedRoute();

		// Get our parameters
		$method    = is_callable($router->controllerName()) ? new \ReflectionFunction($router->controllerName()) : new \ReflectionMethod($router->controllerName(), $router->methodName());
		$rawParams = $method->getParameters();

		$params = [];
		foreach ($rawParams as $key => $param)
		{
			$params[] = [
                         'name'  => $param->getName(),
                         'value' => $router->params()[$key] ?:
					"&lt;empty&gt;&nbsp| default: ". var_export($param->getDefaultValue(), TRUE),
                        ];
		}

		$matchedRoute = [
                         [
                          'directory'  => $router->directory(),
                          'controller' => $router->controllerName(),
                          'method'     => $router->methodName(),
                          'paramCount' => count($router->params()),
                          'truePCount' => count($params),
                          'params'     => $params ?? [],
                         ],
                        ];

		/*
		 * Defined Routes
		 */
		$rawRoutes = $rawRoutes->getRoutes();
		$routes    = [];

		foreach ($rawRoutes as $from => $to)
		{
			$routes[] = [
                         'from' => $from,
                         'to'   => $to,
                        ];
		}

		return $parser->setData([
                                 'matchedRoute' => $matchedRoute,
                                 'routes'       => $routes,
                                ])
			->render('_routes.tpl');
	}

	//--------------------------------------------------------------------
}
