<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Core\YP_RouterCollection as RouterCollection;

/**
 * Class YP_Router 路由类
 *
 * @package YP\Core
 */
class YP_Router
{
    /**
     * 路由收集器对象
     *
     * @var YP_RouterCollection
     */
    protected $collection;

    /**
     * 包含请求控制器类的子目录
     *
     * @var string
     */
    protected $directory;

    /**
     * 控制器类名
     *
     * @var string
     */
    protected $controller;

    /**
     * 方法名称
     *
     * @var string
     */
    protected $method;

    /**
     * An array of binds that were collected
     * so they can be sent to closure routes.
     *
     * @var array
     */
    /**
     * 收集的绑定数组，以便它们可以被发送到关闭路由
     *
     * @var array
     */
    protected $params = [];

    /**
     * 前端控制器名称
     *
     * @var string
     */
    protected $indexPage = 'index.php';

    /**
     * 是否将扩折号转换为下划线
     * 在确定方法名称时，是否应将URI中的破折号转换为下划线
     * TRUE: 替换 ;FALSE: 不替换
     *
     * @var bool
     */
    protected $translateURIDashes = false;

    /**
     * 这路由已匹配当前的请求
     *
     * @var null
     */
    protected $matchedRoute = null;

    /**
     * 在路由中检测到的区域设置
     *
     * @var string
     */
    protected $detectedLocale = null;

    /**
     * Router constructor.
     *
     * @param YP_RouterCollection $routes
     */
    public function __construct(RouterCollection $routes)
    {
        $this->collection = $routes;
        $this->controller = $this->collection->getDefaultController();
        $this->method     = $this->collection->getDefaultMethod();
    }

    /**
     * 扫描URL并试图匹配被路由收集器收集当前URL的路由
     *
     * @param string|null $uri
     *
     * @return string
     * @throws \Exception
     */
    public function handle(string $uri = null)
    {
        // 如果找不到一个匹配的URI，那么一切使用默认配置
        if (empty($uri)) {
            return strpos($this->controller,
                '\\') === false ? $this->collection->getDefaultNamespace() . $this->controller : $this->controller;
        }
        if ($this->checkRoutes($uri)) {
            return $this->controller;
        }
        // 尝试对Controller/目录下进行URI匹配,但是在API的情况下,应用程序不希望这样去匹配URL
        if (!$this->collection->shouldAutoRoute()) {
            throw new \RuntimeException("Can't find a route for '{$uri}'.");
        }
        $this->autoRoute($uri);

        return $this->controller;
    }

    /**
     * 返回已匹配到的控制器名称
     *
     * @return string
     */
    public function controllerName()
    {
        return $this->controller;
    }

    /**
     * 返回当前调用的方法名称
     *
     * @return string
     */
    public function methodName(): string
    {
        return $this->method;
    }

    /**
     * 从收集器中返回404个重写设置。如果重写是一个字符串，将拆分为控制器/方法
     *
     * @return array|\Closure|null|string
     */
    public function get404Override()
    {
        $route = $this->collection->get404Override();
        if (is_string($route)) {
            $routeArray = explode('::', $route);

            return [
                $routeArray[0],             // 控制器
                $routeArray[1] ?? 'index'   // 方法
            ];
        }
        if (is_callable($route)) {
            return $route;
        }

        return null;
    }

    /**
     * 获得所有的URI参数
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * 获得控制器所在的子目录
     * 子目录,是相对于APP_PATH . 'Controller/'目录来说的,也只有开启自动路由的时候才有效
     *
     * @return string
     */
    public function directory(): string
    {
        return !empty($this->directory) ? $this->directory : '';
    }

    /**
     * 返回匹配已定义的请求路由
     *
     * @return null
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * 设置首页,默认匹配是index.php文件,也可以通过mod_rewrite 重写路由修改这个值,也可以设置为空
     *
     * @param $page
     *
     * @return YP_Router
     */
    public function setIndexPage($page): self
    {
        $this->indexPage = $page;

        return $this;
    }

    /**
     * 是否将URL中的扩折号转换成下划线
     *
     * @param bool $val
     *
     * @return YP_Router
     */
    public function setTranslateURIDashes($val = false): self
    {
        $this->translateURIDashes = (bool)$val;

        return $this;
    }

    /**
     * 返回TRUE/FALSE, 根据当前路由是否包含一个{locale}占位符
     *
     * @return bool
     */
    public function hasLocale()
    {
        return (bool)$this->detectedLocale;
    }

    /**
     * 返回已检测到的locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->detectedLocale;
    }

    /**
     * 当前的URL字符串与路由收集器中的已定义的路由进行比较匹配
     * 一旦匹配成功,该方法将会修改$this->controller
     *
     * @param string $uri 被匹配的URL字符串
     *
     * @return bool   是否匹配成功
     * @throws \Exception
     */
    protected function checkRoutes(string $uri): bool
    {
        // 获取路由收集器中的所有路由
        $routes = $this->collection->getRoutes();
        if (empty($routes)) {
            return false;
        }
        // 通过遍历路由进行相应的通配符匹配
        foreach ($routes as $key => $val) {
            if (strpos($key, '{locale}') !== false) {
                // 搜索占位符
                $localeSegment = array_search('{locale}', explode('/', $key));
                // 使用正则表达式进行匹配
                $key = str_replace('{locale}', '[^/]+', $key);
            }
            if (preg_match('#^' . $key . '$#', $uri, $matches)) {
                if (isset($localeSegment)) {
                    // 将URL进行拆分
                    $temp                 = (explode('/', $uri));
                    $this->detectedLocale = $temp[$localeSegment];
                    unset($localeSegment);
                }
                // 如果使用闭包,需要将收集params数组传递给控制器的方法
                if (!is_string($val) && is_callable($val)) {
                    $this->controller = $val;
                    // 从匹配的数组中删除原始字符串
                    array_shift($matches);
                    $this->params       = $matches;
                    $this->matchedRoute = [$key, $val];

                    return true;
                } else { // 使用默认的方法来引用
                    // 支持子目录功能资源路由,如$routes->resource('Admin/Admins');
                    if (strpos($val, '$') !== false && strpos($key, '(') !== false && strpos($key, '/') !== false) {
                        $replacekey = str_replace('/(.*)', '', $key);
                        $val        = preg_replace('#^' . $key . '$#', $val, $uri);
                        $val        = str_replace($replacekey, str_replace("/", "\\", $replacekey), $val);
                    } elseif (strpos($val, '$') !== false && strpos($key, '(') !== false) {
                        $val = preg_replace('#^' . $key . '$#', $val, $uri);
                    } elseif (strpos($key, '/') !== false) {
                        $val = str_replace('/', '\\', $val);
                    }
                }
                // 支持重定向路由,如果是重定向路由,将重定向另一个路由
                if ($this->collection->isRedirect($key)) {
                    throw new \Exception($val, $this->collection->getRedirectCode($key));
                }
                $this->setRequest(explode('/', $val));
                $this->matchedRoute = [$key, $val];

                return true;
            }
        }

        return false;
    }

    /**
     * 自动路由
     * 为了试图在目录APP_PATH . 'Controller/'下匹配一个URL路径,
     *
     * @param string $uri
     */
    public function autoRoute(string $uri)
    {
        $segments = explode('/', $uri);
        $segments = $this->validateRequest($segments);
        // 如果$segments为空,将设置默认控制器
        if (empty($segments)) {
            $this->setDefaultController();
        } else {
            // 如果不为空,$segment的第一个值应该是控制器
            $this->controller = ucfirst(array_shift($segments));
        }
        // 使用的方法名称存在,如果不存在,就是用默认的方法名称
        if (!empty($segments)) {
            $this->method = array_shift($segments);
        }
        if (!empty($segments)) {
            $this->params = $segments;
        }
        // 加载控制器文件
        $file = APP_PATH . 'Controllers/' . $this->directory . $this->controller . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
        // 必须检测长度是否超过1,由于默认情况下是'\',确保控制器存储的是限定的类名
        if (strpos($this->controller, '\\') === false && strlen($this->collection->getDefaultNamespace()) > 1) {
            $this->controller = str_replace('/', '\\',
                $this->collection->getDefaultNamespace() . $this->directory . $this->controller);
        }
    }

    /**
     * 试图验证URI请求并确定控制器路径
     *
     * @param array $segments
     *
     * @return array
     */
    protected function validateRequest(array $segments)
    {
        // 参数的个数
        $c = count($segments);
        // 判读是否有子目录
        $directory_override = isset($this->directory);
        // 循环遍历$segments,将返回找到控制器或者目录不存在
        while ($c-- > 0) {
            $test = $this->directory . ucfirst($this->translateURIDashes === true ? str_replace('-', '_',
                    $segments[0]) : $segments[0]);
            if (!file_exists(APP_PATH . 'Controllers/' . $test . '.php') && $directory_override === false && is_dir(APP_PATH . 'Controllers/' . $this->directory . ucfirst($segments[0]))) {
                $this->setDirectory(array_shift($segments), true);
                continue;
            }

            return $segments;
        }

        // 这意味着所有的$segments实际上是目录
        return $segments;
    }

    /**
     * 设置控制器所在的子目录
     *
     * @param string|null $dir
     * @param bool        $append
     */
    protected function setDirectory(string $dir = null, $append = false)
    {
        $dir = ucfirst($dir);
        if ($append !== true || empty($this->directory)) {
            $this->directory = str_replace('.', '', trim($dir, '/')) . '/';
        } else {
            $this->directory .= str_replace('.', '', trim($dir, '/')) . '/';
        }
    }

    /**
     * 设置请求路由
     *
     * @param array $segments
     */
    protected function setRequest(array $segments = [])
    {
        // 如果不存在,就使用默认控制器
        if (empty($segments)) {
            $this->setDefaultController();

            return;
        }
        if ($this->translateURIDashes === true) {
            $segments[0] = str_replace('-', '_', $segments[0]);
            if (isset($segments[1])) {
                $segments[1] = str_replace('-', '_', $segments[1]);
            }
        }
        list($controller, $method) = array_pad(explode('::', $segments[0]), 2, null);
        $this->controller = $controller;
        // $this->method 已经设置了默认值,所以不要用空值来覆盖默认值
        if (!empty($method)) {
            $this->method = $method;
        }
        array_shift($segments);
        $this->params = $segments;
    }

    /**
     * 基于路由收集器的的信息设置默认控制器
     */
    protected function setDefaultController()
    {
        if (empty($this->controller)) {
            throw new \RuntimeException('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
        }
        // 是否指定了方法
        if (sscanf($this->controller, '%[^/]/%s', $class, $this->method) !== 2) {
            $this->method = 'index';
        }
        if (!file_exists(APP_PATH . 'Controllers/' . $this->directory . ucfirst($class) . '.php')) {
            return;
        }
        $this->controller = ucfirst($class);
        log_message('info', 'Used the default controller.');
    }
}
