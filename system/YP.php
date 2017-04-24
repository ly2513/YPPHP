<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 12:00
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP;

use Config\Cache;
use YP\Core\YP_Uri as Uri;
use YP\Core\YP_Hooks as Hooks;
use YP\Core\YP_Request as Request;
use YP\Core\YP_Response as Response;
use YP\Cli\YP_CliRequest as CliRequest;
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

    /**
     *
     *
     * @var
     */
    protected $benchmark;

    /**
     * 请求对象
     *
     * @var
     */
    protected $request;

    /**
     * 响应对象
     *
     * @var
     */
    protected $response;

    /**
     * 路由对象
     *
     * @var
     */
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

    /**
     * @var
     */
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
        // 记录开始时间
        $this->startBenchmark();
        // 获得请求对象
        $this->getRequestObject();
        // 获得响应对象
        $this->getResponseObject();
        // 是否安全访问站点
        $this->forceSecureAccess();
        // 检查缓存页,如果页面已被缓存，执行将停止
        $cacheConfig = new Cache();
        $this->displayCache($cacheConfig);
        $this->spoofRequestMethod();
        try {
            // 处理请求
            $this->handleRequest($routes, $cacheConfig);
        } catch (\Exception $e) {
            // 日志记录异常错误
            $logger = Config\Services::log();
            $logger->info('REDIRECTED ROUTE at ' . $e->getMessage());
//            P($this->response);
            // 如果该路由是重定向路由，则以$to作为消息抛出异常
            $this->response->redirect($e->getMessage(), 'auto', $e->getCode());
            $this->callExit(EXIT_SUCCESS);
        } catch (\Exception $e) {// 捕获响应的重定向错误
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
     * 获取对象请求,基于服务器提供的信息服务器协议。
     */
    protected function getRequestObject()
    {
        if (is_cli()) {
            $this->request = Config\Services::cliRequest($this->config);
        } else {
            $this->request = Config\Services::request($this->config);
            $this->request->setProtocolVersion($_SERVER['SERVER_PROTOCOL']);
        }
    }

    /**
     * 获取响应对象，并设置一些默认值，包括HTTP协议版本和默认的成功响应
     */
    protected function getResponseObject()
    {
        $this->response = Config\Services::response($this->config);
        if (!is_cli()) {
            $this->response->setProtocolVersion($this->request->getProtocolVersion());
        }
        // 设置状响应态
        $this->response->setStatusCode(200);
    }

    /**
     * 强制安全站点访问？如果配置'forceGlobalSecureRequests”的值为true，
     * 将执行所有请求该网站是通过HTTPS。将用户重定向到当前页面与HTTPS，以及为那些支持它的浏览器设置HTTP严格的传输安全头，
     *
     * @param int $duration 时间,严格安全传输应该多久执行这个网址
     */
    protected function forceSecureAccess($duration = 31536000)
    {
        if ($this->config->forceGlobalSecureRequests !== true) {
            return;
        }
        force_https($duration, $this->request, $this->response);
    }

    /**
     * 处理请求逻辑并触发控制器
     *
     * @param RouterCollection|null $routes
     * @param                       $cacheConfig
     */
    protected function handleRequest(RouterCollection $routes = null, $cacheConfig)
    {
        $this->tryToRouteIt($routes);
        // 运行 "before" 过滤器
        $filters = Config\Services::filters();
        $uri     = $this->request instanceof CliRequest ? $this->request->getPath() : $this->request->uri->getPath();
        $filters->run($uri, 'before');
        $returned = $this->startController();
        // 关闭已经运行在startController()的控制器
        if (!is_callable($this->controller)) {
            $controller = $this->createController();
            // 是否有'post_controller_constructor'钩子
            Hooks::trigger('post_controller_constructor');
            $returned = $this->runController($controller);
        } else {
            $this->benchmark->stop('controller_constructor');
            $this->benchmark->stop('controller');
        }
        // 如果返回的是一个字符串，那么控制器输出的东西，可能是一个视图，而不是直接输出。可以单独发送所以可能用于输出。
        $this->gatherOutput($cacheConfig, $returned);
        // 运行 "after" 过滤器
        $response = $filters->run($uri, 'after');
        if ($response instanceof Response) {
            $this->response = $response;
        }
        // 将当前URI保存为会话中的前一个URI，以便更安全
        $this->storePreviousURL($this->request->uri ?? $uri);
        unset($uri);
        $this->sendResponse();
        // 是否有'post-system'钩子
        Hooks::trigger('post_system');
    }

    /**
     * 用不同的方法去修改请求对象
     *
     * 在命令行下失效
     */
    public function spoofRequestMethod()
    {
        if (is_cli()) {
            return;
        }
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
     * 可以根据当前环境加载不同的配置,设置环境变量也会影响日志记录和错误报告
     * 环境变量的值为:dev、test、prod
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
     * 加载当前常量的服务器配置
     */
    protected function loadEnvironment()
    {
        // 通过.env 文件,加载环境配置
        require SYSTEM_PATH . 'Config/DotEnv.php';
        $env = new DotEnv(ROOT_PATH);
        $env->load();
    }

    /**
     * 执行一个路由去匹配当前Uri中的路由,如果当前是重定向路由,将处理这重定向路由
     *
     * @param RouteCollection|null $routes
     */
    protected function tryToRouteIt(RouteCollection $routes = null)
    {
        if (empty($routes) || !$routes instanceof RouteCollection) {
            require APP_PATH . 'Config/Routes.php';
        }
        // $routes 已在Config/Routes.php定义
        $this->router = Config\Services::router($routes);
        $path         = $this->determinePath();
        $this->benchmark->stop('bootstrap');
        $this->benchmark->start('routing');
        ob_start();
        $this->controller = $this->router->handle($path);
        $this->method     = $this->router->methodName();
        // 如果在本地路由被匹配到,将设置当前请求
        if ($this->router->hasLocale()) {
            $this->request->setLocale($this->router->getLocale());
        }
        $this->benchmark->stop('routing');
    }

    /**
     * 根据用户的输入(setPath)，或CLI / incomingrequest路径,路由到确定的路由。
     *
     * @return mixed
     */
    protected function determinePath()
    {
        if (!empty($this->path)) {
            return $this->path;
        }

        return is_cli() ? $this->request->getPath() : $this->request->uri->getPath();
    }

    /**
     * 现在一切都已安装，此方法试图运行控制器方法，使应用运行起来。如果不能，将显示适当的页面没有发现错误。
     *
     * @return mixed
     */
    protected function startController()
    {
        $this->benchmark->start('controller');
        $this->benchmark->start('controller_constructor');
        // Is it routed to a Closure?
        if (is_object($this->controller) && (get_class($this->controller) == 'Closure')) {
            $controller = $this->controller;

            return $controller(...$this->router->params());
        } else {
            // 没有指定控制器
            if (empty($this->controller)) {
                throw new \RuntimeException('Controller is empty.');
            } else {
                // 尝试自动加载当前这个类
                if (!class_exists($this->controller, true) || $this->method[0] === '_') {
                    throw new \RuntimeException('Controller or its method is not found.');
                } else if (!method_exists($this->controller, '_remap') && !is_callable([
                        $this->controller,
                        $this->method
                    ], false)
                ) {
                    throw new \RuntimeException('Controller method is not found.');
                }
            }
        }
    }

    /**
     * 实例化一个当前控制器类对象
     *
     * @return mixed
     */
    protected function createController()
    {
        $class = new $this->controller($this->request, $this->response);
        $this->benchmark->stop('controller_constructor');

        return $class;
    }

    /**
     * 确定给定的URI是否已缓存响应
     *
     * @param $config 配置
     *
     * @throws \Exception
     */
    public function displayCache($config)
    {
        if ($cachedResponse = cache()->get($this->generateCacheName($config))) {
            $cachedResponse = unserialize($cachedResponse);
            if (!is_array($cachedResponse) || !isset($cachedResponse['output']) || !isset($cachedResponse['headers'])) {
                throw new \Exception("Error unserializing page cache");
            }
            $headers = $cachedResponse['headers'];
            $output  = $cachedResponse['output'];
            //  清除所有的某人头部设置
            foreach ($this->response->getHeaders() as $key => $val) {
                $this->response->removeHeader($key);
            }
            // 设置缓存头
            foreach ($headers as $name => $value) {
                $this->response->setHeader($name, $value);
            }
            $output = $this->displayPerformanceMetrics($output);
            $this->response->setBody($output)->send();
            $this->callExit(EXIT_SUCCESS);
        };
    }

    /**
     * 生成缓存名称用于我们全页缓存
     *
     * @param $config
     *
     * @return string
     */
    protected function generateCacheName($config): string
    {
        if (is_cli()) {
            return md5($this->request->getPath());
        }
        $uri = $this->request->uri;
        if ($config->cacheQueryString) {
            $name = Uri::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath(), $uri->getQuery());
        } else {
            $name = Uri::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());
        }

        return md5($name);
    }

}