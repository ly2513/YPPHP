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
        $this->detectEnvironment();
        $this->bootstrapEnvironment();
        $this->loadEnvironment();
        if (YP_DEBUG) {
            require_once BASEPATH . 'ThirdParty/Kint/Kint.class.php';
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
        $cacheConfig = new Cache();
        $this->displayCache($cacheConfig);

        $this->spoofRequestMethod();

        try {
            $this->handleRequest($routes, $cacheConfig);
        }
        catch (\Exception $e)
        {
            $logger = Config\Services::log();
            $logger->info('REDIRECTED ROUTE at '.$e->getMessage());

            // If the route is a 'redirect' route, it throws
            // the exception with the $to as the message
            $this->response->redirect($e->getMessage(), 'auto', $e->getCode());
            $this->callExit(EXIT_SUCCESS);
        }
            // Catch Response::redirect()
        catch (\Exception $e)
        {
            $this->callExit(EXIT_SUCCESS);
        }
        catch (\RuntimeException $e)
        {
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
        if (is_cli())
        {
            $this->request = Config\Services::clirequest($this->config);
        }
        else
        {
            $this->request = Config\Services::request($this->config);
            $this->request->setProtocolVersion($_SERVER['SERVER_PROTOCOL']);
        }
    }




}