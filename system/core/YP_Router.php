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
    protected $params = [];

    /**
     * 前端控制器名称
     *
     * @var string
     */
    protected $indexPage = 'index.php';

    /**
     * Whether dashes in URI's should be converted
     * to underscores when determining method names.
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
     * The locale that was detected in a route.
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

    //--------------------------------------------------------------------
    /**
     * Scans the URI and attempts to match the current URI to the
     * one of the defined routes in the RouteCollection.
     *
     * This is the main entry point when using the Router.
     *
     * @param null $uri
     *
     * @return mixed
     */
    /**
     * @param string|null $uri
     *
     * @return string
     * @throws PageNotFoundException
     * @throws RedirectException
     */
    public function handle(string $uri = null)
    {
        // If we cannot find a URI to match against, then
        // everything runs off of it's default settings.
        if (empty($uri)) {
            return strpos($this->controller,
                '\\') === false ? $this->collection->getDefaultNamespace() . $this->controller : $this->controller;
        }
        if ($this->checkRoutes($uri)) {
            return $this->controller;
        }
        // Still here? Then we can try to match the URI against
        // Controllers/directories, but the application may not
        // want this, like in the case of API's.
        if (!$this->collection->shouldAutoRoute()) {
            throw new \RuntimeException("Can't find a route for '{$uri}'.");
        }
        $this->autoRoute($uri);

        return $this->controller;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the name of the matched controller.
     *
     * @return mixed
     */
    public function controllerName()
    {
        return $this->controller;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the name of the method to run in the
     * chosen container.
     *
     * @return mixed
     */
    public function methodName(): string
    {
        return $this->method;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the 404 Override settings from the Collection.
     * If the override is a string, will split to controller/index array.
     */
    public function get404Override()
    {
        $route = $this->collection->get404Override();
        if (is_string($route)) {
            $routeArray = explode('::', $route);

            return [
                $routeArray[0],             // Controller
                $routeArray[1] ?? 'index'   // Method
            ];
        }
        if (is_callable($route)) {
            return $route;
        }

        return null;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the binds that have been matched and collected
     * during the parsing process as an array, ready to send to
     * call_user_func_array().
     *
     * @return mixed
     */
    public function params(): array
    {
        return $this->params;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the name of the sub-directory the controller is in,
     * if any. Relative to APPPATH.'Controllers'.
     *
     * Only used when auto-routing is turned on.
     *
     * @return string
     */
    public function directory(): string
    {
        return !empty($this->directory) ? $this->directory : '';
    }

    //--------------------------------------------------------------------
    /**
     * Returns the routing information that was matched for this
     * request, if a route was defined.
     *
     * @return array|null
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    //--------------------------------------------------------------------
    /**
     * Sets the value that should be used to match the index.php file. Defaults
     * to index.php but this allows you to modify it in case your are using
     * something like mod_rewrite to remove the page. This allows you to set
     * it a blank.
     *
     * @param $page
     *
     * @return mixed
     */
    /**
     * @param $page
     *
     * @return YP_Router
     */
    public function setIndexPage($page): self
    {
        $this->indexPage = $page;

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Tells the system whether we should translate URI dashes or not
     * in the URI from a dash to an underscore.
     *
     * @param bool|false $val
     *
     * @return $this
     */
    /**
     * @param bool $val
     *
     * @return YP_Router
     */
    public function setTranslateURIDashes($val = false): self
    {
        $this->translateURIDashes = (bool)$val;

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Returns true/false based on whether the current route contained
     * a {locale} placeholder.
     *
     * @return bool
     */
    public function hasLocale()
    {
        return (bool)$this->detectedLocale;
    }

    //--------------------------------------------------------------------
    /**
     * Returns the detected locale, if any, or null.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->detectedLocale;
    }

    //--------------------------------------------------------------------
    /**
     * Compares the uri string against the routes that the
     * RouteCollection class defined for us, attempting to find a match.
     * This method will modify $this->controller, etal as needed.
     *
     * @param string $uri The URI path to compare against the routes
     *
     * @return bool Whether the route was matched or not.
     * @throws \CodeIgniter\Router\RedirectException
     */
    /**
     * 检测路由
     *
     * @param string $uri
     *
     * @return bool
     * @throws \Exception
     */
    protected function checkRoutes(string $uri): bool
    {
        $routes = $this->collection->getRoutes();
        // Don't waste any time
        if (empty($routes)) {
            return false;
        }
        // Loop through the route array looking for wildcards
        foreach ($routes as $key => $val) {
            // Are we dealing with a locale?
            if (strpos($key, '{locale}') !== false) {
                $localeSegment = array_search('{locale}', explode('/', $key));
                // Replace it with a regex so it
                // will actually match.
                $key = str_replace('{locale}', '[^/]+', $key);
            }
            // Does the RegEx match?
            if (preg_match('#^' . $key . '$#', $uri, $matches)) {
                // Store our locale so CodeIgniter object can
                // assign it to the Request.
                if (isset($localeSegment)) {
                    // The following may be inefficient, but doesn't upset NetBeans :-/
                    $temp                 = (explode('/', $uri));
                    $this->detectedLocale = $temp[$localeSegment];
                    unset($localeSegment);
                }
                // Are we using Closures? If so, then we need
                // to collect the params into an array
                // so it can be passed to the controller method later.
                if (!is_string($val) && is_callable($val)) {
                    $this->controller = $val;
                    // Remove the original string from the matches array
                    array_shift($matches);
                    $this->params       = $matches;
                    $this->matchedRoute = [$key, $val];

                    return true;
                } // Are we using the default method for back-references?
                else {
                    // Support resource route when function with subdirectory
                    // ex: $routes->resource('Admin/Admins');
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
                // Is this route supposed to redirect to another?
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

    //--------------------------------------------------------------------
    /**
     * Attempts to match a URI path against Controllers and directories
     * found in APPPATH/Controllers, to find a matching route.
     *
     * @param string $uri
     */
    /**
     * 自动路由
     *
     * @param string $uri
     */
    public function autoRoute(string $uri)
    {
        $segments = explode('/', $uri);
        $segments = $this->validateRequest($segments);
        // If we don't have any segments left - try the default controller;
        // WARNING: Directories get shifted out of the segments array.
        if (empty($segments)) {
            $this->setDefaultController();
        } // If not empty, then the first segment should be the controller
        else {
            $this->controller = ucfirst(array_shift($segments));
        }
        // Use the method name if it exists.
        // If it doesn't, no biggie - the default method name
        // has already been set.
        if (!empty($segments)) {
            $this->method = array_shift($segments);
        }
        if (!empty($segments)) {
            $this->params = $segments;
        }
        // Load the file so that it's available for CodeIgniter.
        $file = APP_PATH . 'Controllers/' . $this->directory . $this->controller . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
        // Ensure the controller stores the fully-qualified class name
        // We have to check for a length over 1, since by default it will be '\'
        if (strpos($this->controller, '\\') === false && strlen($this->collection->getDefaultNamespace()) > 1) {
            $this->controller = str_replace('/', '\\',
                $this->collection->getDefaultNamespace() . $this->directory . $this->controller);
        }
    }

    //--------------------------------------------------------------------
    /**
     * Attempts to validate the URI request and determine the controller path.
     *
     * @param array $segments URI segments
     *
     * @return array URI segments
     */
    /**
     * 对请求的URI进行校验并且
     *
     * @param array $segments
     *
     * @return array
     */
    protected function validateRequest(array $segments)
    {
        $c                  = count($segments);
        $directory_override = isset($this->directory);
        // Loop through our segments and return as soon as a controller
        // is found or when such a directory doesn't exist
        while ($c-- > 0) {
            $test = $this->directory . ucfirst($this->translateURIDashes === true ? str_replace('-', '_',
                    $segments[0]) : $segments[0]);
            if (!file_exists(APP_PATH . 'Controllers/' . $test . '.php') && $directory_override === false && is_dir(APP_PATH . 'Controllers/' . $this->directory . ucfirst($segments[0]))) {
                $this->setDirectory(array_shift($segments), true);
                continue;
            }

            return $segments;
        }

        // This means that all segments were actually directories
        return $segments;
    }

    //--------------------------------------------------------------------
    /**
     * Sets the sub-directory that the controller is in.
     *
     * @param string|null $dir
     * @param bool|false  $append
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

    //--------------------------------------------------------------------
    /**
     * Set request route
     *
     * Takes an array of URI segments as input and sets the class/method
     * to be called.
     *
     * @param    array $segments URI segments
     */
    /**
     * 设置请求路由
     *
     * @param array $segments
     */
    protected function setRequest(array $segments = [])
    {
        // If we don't have any segments - try the default controller;
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
        // $this->method already contains the default method name,
        // so don't overwrite it with emptiness.
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
        // Is the method being specified?
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
