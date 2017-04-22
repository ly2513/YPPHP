<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 12:00
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP;

use YP\Core\YP_RouterCollection as RouterCollection;

class YP
{
    /**
     * YP框架版本号
     */
    const YP_VERSION = '1.0';

    /**
     * 开始运行时间
     *
     * @var null
     */
    protected $startTime = null;

    /**
     * 应用运行的内存
     *
     * @var int
     */
    protected $startMemory;

    /**
     * 总的运行时间
     *
     * @var int
     */
    protected $totalTime;

    /**
     * 配置信息
     *
     * @var
     */
    protected $config;

    protected $benchmark;

    protected $request;

    protected $response;

    protected $router;

    /**
     * 当前加载的控制器
     *
     * @var string
     */
    protected $controller;

    /**
     * 当前调用的方法
     *
     * @var string
     */
    protected $method;

    /**
     * 缓存时间
     *
     * @var int
     */
    protected static $cacheTTL = 0;

    protected $path;

    /**
     * YP constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->startTime = microtime(true) * 1000;
        // 系统分配给PHP的内存
        $this->startMemory = memory_get_usage(true);
        // 应用配置
        $this->config = $config;
    }

    /**
     * Handles some basic app and environment setup.
     */
    public function initialize()
    {
        // 设置服务器时区
        date_default_timezone_set($this->config->appTimezone ?? 'UTC');
        // 设置异常处理
        Config\Services::exceptions($this->config, true)->initialize();
        // 定义环境常量
        $this->detectEnvironment();
        // 加载环境配置信息
        $this->bootstrapEnvironment();
        // $this->loadEnvironment();
        if (YP_DEBUG) {
            //            require_once BASEPATH . 'ThirdParty/Kint/Kint.class.php';
        }
    }

    /**
     * 启动应用程序
     *
     * @param RouterCollection|null $routes
     */
    public function run(RouterCollection $routes = null)
    {
        $this->startBenchmark();
        $this->getRequestObject();
        $this->getResponseObject();
        $this->forceSecureAccess();
        // Check for a cached page. Execution will stop
        // if the page has been cached.
        //        $cacheConfig = new Cache();
        //        $this->displayCache($cacheConfig);
        $this->spoofRequestMethod();
        try {
            // $this->handleRequest($routes, $cacheConfig);
            $this->handleRequest($routes);
        } catch (\Exception $e) {
            $logger = Config\Services::log();
            $logger->info('REDIRECTED ROUTE at ' . $e->getMessage());
            // If the route is a 'redirect' route, it throws
            // the exception with the $to as the message
            $this->response->redirect($e->getMessage(), 'auto', $e->getCode());
            $this->callExit(EXIT_SUCCESS);
        } // Catch Response::redirect()
        catch (\Exception $e) {
            $this->callExit(EXIT_SUCCESS);
        } catch (\RuntimeException $e) {
            $this->display404errors($e);
        }
    }

    /**
     * 计时器用于显示总脚本执行时间,并将在调试工具栏的页面中显示
     */
    protected function startBenchmark()
    {
        $this->startTime = microtime(true);
        $this->benchmark = Config\Services::timer();
        $this->benchmark->start('total_execution', $this->startTime);
        $this->benchmark->start('bootstrap');
    }

    /**
     * Get our Request object, (either IncomingRequest or CLIRequest)
     * and set the server protocol based on the information provided
     * by the server.
     */
    /**
     *
     */
    protected function getRequestObject()
    {
        if (is_cli()) {
            $this->request = Config\Services::clirequest($this->config);
        } else {
            $this->request = Config\Services::request($this->config);
            $this->request->setProtocolVersion($_SERVER['SERVER_PROTOCOL']);
        }
    }

    /**
     * Get our Response object, and set some default values, including
     * the HTTP protocol version and a default successful response.
     */
    protected function getResponseObject()
    {
        $this->response = Config\Services::response($this->config);
        if (!is_cli()) {
            $this->response->setProtocolVersion($this->request->getProtocolVersion());
        }
        // Assume success until proven otherwise.
        $this->response->setStatusCode(200);
    }

    protected function forceSecureAccess($duration = 31536000)
    {
        if ($this->config->forceGlobalSecureRequests !== true) {
            return;
        }
        force_https($duration, $this->request, $this->response);
    }

    /**
     * Handles the main request logic and fires the controller.
     *
     * @param \CodeIgniter\Router\RouteCollectionInterface $routes
     * @param                                              $cacheConfig
     */
    /**
     * @param RouterCollection|null $routes
     */
    protected function handleRequest(RouterCollection $routes = null)
    {
        $this->tryToRouteIt($routes);
        // Run "before" filters
        $filters = Config\Services::filters();
        $uri     = $this->request instanceof CLIRequest ? $this->request->getPath() : $this->request->uri->getPath();
        $filters->run($uri, 'before');
        $returned = $this->startController();
        // Closure controller has run in startController().
        if (!is_callable($this->controller)) {
            $controller = $this->createController();
            // Is there a "post_controller_constructor" hook?
            Events::trigger('post_controller_constructor');
            $returned = $this->runController($controller);
            P($returned);
        } else {
            $this->benchmark->stop('controller_constructor');
            $this->benchmark->stop('controller');
        }
        // If $returned is a string, then the controller output something,
        // probably a view, instead of echoing it directly. Send it along
        // so it can be used with the output.
        //        $this->gatherOutput($cacheConfig, $returned);
        // Run "after" filters
        $response = $filters->run($uri, 'after');
        if ($response instanceof Response) {
            $this->response = $response;
        }
        // Save our current URI as the previous URI in the session
        // for safer, more accurate use with `previous_url()` helper function.
        $this->storePreviousURL($this->request->uri ?? $uri);
        unset($uri);
        $this->sendResponse();
        //--------------------------------------------------------------------
        // Is there a post-system hook?
        //--------------------------------------------------------------------
        //        Events::trigger('post_system');
    }

    /**
     * Modifies the Request Object to use a different method if a POST
     * variable called _method is found.
     *
     * Does not work on CLI commands.
     */
    public function spoofRequestMethod()
    {
        if (is_cli()) {
            return;
        }
        // Only works with POSTED forms
        if ($this->request->getMethod() !== 'post') {
            return;
        }
        $method = $this->request->getPost('_method');
        if (empty($method)) {
            return;
        }
        $this->request = $this->request->setMethod($method);
    }

    /**
     * You can load different configurations depending on your
     * current environment. Setting the environment also influences
     * things like logging and error reporting.
     *
     * This can be set to anything, but default usage is:
     *
     *     development
     *     testing
     *     production
     */
    protected function detectEnvironment()
    {
        // running under Continuous Integration server?
        if (getenv('CI') !== false) {
            define('ENVIRONMENT', 'test');
        } else {
            define('ENVIRONMENT', isset($_SERVER['YP_ENV']) ? $_SERVER['YP_ENV'] : 'dev');
        }
    }

    /**
     * 加载环境配置信息
     */
    protected function bootstrapEnvironment()
    {
        // 加载环境配置信息
        if (file_exists(APP_PATH . 'Config/Boot/' . ENVIRONMENT . '.php')) {
            require_once APP_PATH . 'Config/Boot/' . ENVIRONMENT . '.php';
        } else {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';
            exit(1); // EXIT_ERROR
        }
    }

    /**
     * Loads any custom server config values from the .env file.
     */
    protected function loadEnvironment()
    {
        // Load environment settings from .env files
        // into $_SERVER and $_ENV
        require SYSTEM_PATH . 'Config/DotEnv.php';
        $env = new DotEnv(ROOT_PATH);
        $env->load();
    }

    /**
     * Try to Route It - As it sounds like, works with the router to
     * match a route against the current URI. If the route is a
     * "redirect route", will also handle the redirect.
     *
     * @param RouteCollectionInterface $routes  An collection interface to use in place
     *                                          of the config file.
     */
    /**
     * @param RouteCollection|null $routes
     */
    protected function tryToRouteIt(RouteCollection $routes = null)
    {
        if (empty($routes) || !$routes instanceof RouteCollection) {
            require APP_PATH . 'Config/Routes.php';
        }
        // $routes is defined in Config/Routes.php
        $this->router = Config\Services::router($routes);
        $path = $this->determinePath();
        $this->benchmark->stop('bootstrap');
        $this->benchmark->start('routing');
        ob_start();
        $this->controller = $this->router->handle($path);
        $this->method     = $this->router->methodName();
        // If a {locale} segment was matched in the final route,
        // then we need to set the correct locale on our Request.
        if ($this->router->hasLocale()) {
            $this->request->setLocale($this->router->getLocale());
        }
        $this->benchmark->stop('routing');
    }

}