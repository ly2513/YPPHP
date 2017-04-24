<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 23:53
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_RouterCollection
{
    /**
     * 设置默认的命名空间
     *
     * @var string
     */
    protected $defaultNamespace = '\\';

    /**
     * 默认控制器
     *
     * @var string
     */
    protected $defaultController = 'Home';

    /**
     * 默认方法
     *
     * @var string
     */
    protected $defaultMethod = 'index';

    /**
     * 当未指定其他占位符时，在路由“资源”时使用占位符
     *
     * @var string
     */
    protected $defaultPlaceholder = 'any';

    /**
     * 在URI中是否将破折号转换为下划线,这里不使用
     *
     * @var bool
     */
    protected $translateURIDashes = false;

    /**
     * 自动路由
     *
     * @var bool
     */
    protected $autoRoute = true;

    /**
     * 当路由无法匹配时将显示的可调用404
     *
     * @var
     */
    protected $override404;

    /**
     * 定义匹配的正则占位符
     *
     * @var array
     */
    protected $placeholders = [
        'any'      => '.*',
        'segment'  => '[^/]+',
        'num'      => '[0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'hash'     => '[^/]+',
    ];

    /**
     * 路由规则映射数组
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 当前脚本被调用的方法
     *
     * @var
     */
    protected $HTTPVerb;

    /**
     * 允许运用的http方法
     *
     * @var array
     */
    protected $defaultHTTPMethods = ['options', 'get', 'head', 'post', 'put', 'delete', 'trace', 'connect', 'cli'];

    /**
     * 分组的名称
     *
     * @var null
     */
    protected $group = null;

    /**
     * 当前子域
     *
     * @var null
     */
    protected $currentSubdomain = null;

    /**
     * 在创建期间,存储已用操作
     *
     * @var null
     */
    protected $currentOptions = null;

    /**
     * YP_RouterCollection constructor.
     */
    public function __construct()
    {
        // Get HTTP verb
        $this->HTTPVerb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';
    }

    /**
     * 添加路由规则
     *
     * @param string      $placeholder
     * @param string|null $pattern
     *
     * @return YP_RouterCollection
     */
    public function addPlaceholder(string $placeholder, string $pattern = null): self
    {
        if (!is_array($placeholder)) {
            $placeholder = [$placeholder => $pattern];
        }
        $this->placeholders = array_merge($this->placeholders, $placeholder);

        return $this;
    }

    /**
     * 设置默认的命名空间
     *
     * @param string $value
     *
     * @return YP_RouterCollection
     */
    public function setDefaultNamespace(string $value): self
    {
        $this->defaultNamespace = filter_var($value, FILTER_SANITIZE_STRING);
        $this->defaultNamespace = rtrim($this->defaultNamespace, '\\') . '\\';

        return $this;
    }

    /**
     * 设置默认控制器
     *
     * @param string $value
     *
     * @return YP_RouterCollection
     */
    public function setDefaultController(string $value): self
    {
        $this->defaultController = filter_var($value, FILTER_SANITIZE_STRING);

        return $this;
    }

    /**
     * 设置默认方法
     *
     * @param string $value
     *
     * @return YP_RouterCollection
     */
    public function setDefaultMethod(string $value): self
    {
        $this->defaultMethod = filter_var($value, FILTER_SANITIZE_STRING);

        return $this;
    }

    /**
     * 将URI中的扩折号转换为下划线
     *
     * @param bool $value
     *
     * @return YP_RouterCollection
     */
    public function setTranslateURIDashes(bool $value): self
    {
        $this->translateURIDashes = $value;

        return $this;
    }

    /**
     * 如果为TRUE，如果匹配不到定义的路由,系统将尝试匹配每一段对APP_PATH/Controlers/文件夹/文件/的URI匹配，
     * 如果FALSE，将停止搜索，没有自动路由。
     *
     * @param bool $value
     *
     * @return YP_RouterCollection
     */
    public function setAutoRoute(bool $value): self
    {
        $this->autoRoute = $value;

        return $this;
    }

    /**
     * 设置404路由
     *
     * @param null $callable
     *
     * @return YP_RouterCollection
     */
    public function set404Override($callable = null): self
    {
        $this->override404 = $callable;

        return $this;
    }

    /**
     * 获得404路由
     *
     * @return string|\Closure|null
     */
    public function get404Override()
    {
        return $this->override404;
    }

    /**
     * 设置系统中要使用的默认约束。通常用于“资源”方法。
     *
     * @param string $placeholder
     *
     * @return YP_RouterCollection
     */
    public function setDefaultConstraint(string $placeholder): self
    {
        if (array_key_exists($placeholder, $this->placeholders)) {
            $this->defaultPlaceholder = $placeholder;
        }

        return $this;
    }

    /**
     * 获取默认控制器
     *
     * @return string
     */
    public function getDefaultController(): string
    {
        return $this->defaultController;
    }

    /**
     * 获取默认方法
     *
     * @return string
     */
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    /**
     * 获取默认命名空间
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    /**
     * 返回当前translateuridashses设置的值
     *
     * @return bool
     */
    public function shouldTranslateURIDashes(): bool
    {
        return $this->translateURIDashes;
    }

    /**
     * 获得是否自动路由的标识
     *
     * @return bool
     */
    public function shouldAutoRoute(): bool
    {
        return $this->autoRoute;
    }

    /**
     * 获得所有可用的路由
     *
     * @return array
     */
    public function getRoutes(): array
    {
        $routes = [];
        foreach ($this->routes as $r) {
            $key          = key($r['route']);
            $routes[$key] = $r['route'][$key];
        }

        return $routes;
    }

    /**
     * 返回当前使用的HTTP谓词
     *
     * @return string
     */
    public function getHTTPVerb(): string
    {
        return $this->HTTPVerb;
    }

    /**
     * 在一个时间内添加多条路由的快捷方式
     *
     * @param array      $routes
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function map(array $routes = [], array $options = null): self
    {
        foreach ($routes as $from => $to) {
            $this->add($from, $to, $options);
        }

        return $this;
    }

    /**
     * 将单个路由添加到集合中
     * 例如: $routes->add('news', 'Posts::index');
     *
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function add(string $from, $to, array $options = null): self
    {
        $this->create($from, $to, $options);

        return $this;
    }

    /**
     * 添加一个路由临时重定向到另一个路由
     *
     * @param string $from
     * @param string $to
     * @param int    $status
     *
     * @return $this
     */
    public function addRedirect(string $from, string $to, int $status = 302)
    {
        // 如果这是一个指定的路由,使用命名路由的模式
        if (array_key_exists($to, $this->routes)) {
            $to = $this->routes[$to]['route'];
        }
        $this->create($from, $to, ['redirect' => $status]);

        return $this;
    }

    /**
     * 确定路由是否是为重定向路由。
     *
     * @param string $from
     *
     * @return bool
     */
    public function isRedirect(string $from): bool
    {
        foreach ($this->routes as $name => $route) {
            // Named route?
            if ($name == $from || key($route['route']) == $from) {
                return isset($route['redirect']) && is_numeric($route['redirect']);
            }
        }

        return false;
    }

    /**
     * 从重定向路由捕获HTTP状态代码
     *
     * @param string $from
     *
     * @return int
     */
    public function getRedirectCode(string $from): int
    {
        foreach ($this->routes as $name => $route) {
            // 指定的路由
            if ($name == $from || key($route['route']) == $from) {
                return $route['redirect'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * 将一系列路由划分为组,进行分区管理
     *
     * 例如:
     *     // 创建一个路由: admin/users
     *     $route->group('admin', function() {
     *            $route->resources('users');
     *     });
     *
     * @param       $name     组的路由名称
     * @param array ...$params
     */
    public function group($name, ...$params)
    {
        $oldGroup   = $this->group;
        $oldOptions = $this->currentOptions;
        // 要注册路由，我们将设置一个标志，这样我们的路由器就可以看到这个组名了
        $this->group = ltrim($oldGroup . '/' . $name, '/');
        $callback    = array_pop($params);
        if (count($params) && is_array($params[0])) {
            $this->currentOptions = array_shift($params);
        }
        if (is_callable($callback)) {
            $callback($this);
        }
        $this->group          = $oldGroup;
        $this->currentOptions = $oldOptions;
    }

    //--------------------------------------------------------------------
    //--------------------------------------------------------------------
    // HTTP Verb-based routing
    //--------------------------------------------------------------------
    // Routing works here because, as the routes Config file is read in,
    // the various HTTP verb-based routes will only be added to the in-memory
    // routes if it is a call that should respond to that verb.
    //
    // The options array is typically used to pass in an 'as' or var, but may
    // be expanded in the future. See the docblock for 'add' method above for
    // current list of globally available options.
    //
    /**
     * Creates a collections of HTTP-verb based routes for a controller.
     *
     * Possible Options:
     *      'controller'    - Customize the name of the controller used in the 'to' route
     *      'placeholder'   - The regex used by the Router. Defaults to '(:any)'
     *
     * Example:
     *      $route->resources('photos');
     *
     *      // Generates the following routes:
     *      HTTP Verb | Path        | Action        | Used for...
     *      ----------+-------------+---------------+-----------------
     *      GET         /photos             listAll         display a list of photos
     *      GET         /photos/{id}        show            display a specific photo
     *      POST        /photos             create          create a new photo
     *      PUT         /photos/{id}        update          update an existing photo
     *      DELETE      /photos/{id}        delete          delete an existing photo
     *
     *  If 'websafe' option is present, the following paths are also available:
     *
     *      POST        /photos/{id}        update
     *      DELETE      /photos/{id}/delete delete
     *
     * @param  string $name    The name of the controller to route to.
     * @param  array  $options An list of possible ways to customize the routing.
     *
     * @return RouteCollectionInterface
     */
    /**
     * 创建控制器的基于HTTP谓词的路由集合。
     *
     * 例如:
     *      $route->resources('photos');
     *
     *      // Generates the following routes:
     *      HTTP Verb | Path        | Action        | Used for...
     *      ----------+-------------+---------------+-----------------
     *      GET         /photos             listAll         display a list of photos
     *      GET         /photos/{id}        show            display a specific photo
     *      POST        /photos             create          create a new photo
     *      PUT         /photos/{id}        update          update an existing photo
     *      DELETE      /photos/{id}        delete          delete an existing photo
     *
     * @param string     $name   路由中控制器名称
     * @param array|null $options 可能的值为controller/placeholder
     *
     * @return YP_RouterCollection
     */
    public function resource(string $name, array $options = null): self
    {
        // In order to allow customization of the route the
        // resources are sent to, we need to have a new name
        // to store the values in.
        $new_name = ucfirst($name);
        // If a new controller is specified, then we replace the
        // $name value with the name of the new controller.
        if (isset($options['controller'])) {
            $new_name = ucfirst(filter_var($options['controller'], FILTER_SANITIZE_STRING));
        }
        // In order to allow customization of allowed id values
        // we need someplace to store them.
        $id = isset($this->placeholders[$this->defaultPlaceholder]) ? $this->placeholders[$this->defaultPlaceholder] : '(:segment)';
        if (isset($options['placeholder'])) {
            $id = $options['placeholder'];
        }
        // Make sure we capture back-references
        $id      = '(' . trim($id, '()') . ')';
        $methods = isset($options['only']) ? is_string($options['only']) ? explode(',',
            $options['only']) : $options['only'] : ['listAll', 'show', 'create', 'update', 'delete'];
        if (in_array('listAll', $methods)) {
            $this->get($name, $new_name . '::listAll', $options);
        }
        if (in_array('show', $methods)) {
            $this->get($name . '/' . $id, $new_name . '::show/$1', $options);
        }
        if (in_array('create', $methods)) {
            $this->post($name, $new_name . '::create', $options);
        }
        if (in_array('update', $methods)) {
            $this->put($name . '/' . $id, $new_name . '::update/$1', $options);
        }
        if (in_array('delete', $methods)) {
            $this->delete($name . '/' . $id, $new_name . '::delete/$1', $options);
        }
        // Web Safe?
        if (isset($options['websafe'])) {
            if (in_array('update', $methods)) {
                $this->post($name . '/' . $id, $new_name . '::update/$1', $options);
            }
            if (in_array('delete', $methods)) {
                $this->post($name . '/' . $id . '/delete', $new_name . '::delete/$1', $options);
            }
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a single route to match for multiple HTTP Verbs.
     *
     * Example:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array $verbs
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param array      $verbs
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function match(array $verbs = [], string $from, $to, array $options = null): self
    {
        foreach ($verbs as $verb) {
            $verb = strtolower($verb);
            $this->{$verb}($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to GET requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function get(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'get') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to POST requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function post(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'post') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to PUT requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function put(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'put') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to DELETE requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function delete(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'delete') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to HEAD requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function head(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'head') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to PATCH requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function patch(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'patch') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to OPTIONS requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function options(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'options') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Specifies a route that is only available to command-line requests.
     *
     * @param       $from
     * @param       $to
     * @param array $options
     *
     * @return \CodeIgniter\Router\RouteCollectionInterface
     */
    /**
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function cli(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'cli') {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Limits the routes to a specified ENVIRONMENT or they won't run.
     *
     * @param          $env
     * @param callable $callback
     *
     * @return $this
     */
    public function environment(string $env, \Closure $callback): self
    {
        if (ENVIRONMENT == $env) {
            call_user_func($callback, $this);
        }

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Attempts to look up a route based on it's destination.
     *
     * If a route exists:
     *
     *      'path/(:any)/(:any)' => 'Controller::method/$1/$2'
     *
     * This method allows you to know the Controller and method
     * and get the route that leads to it.
     *
     *      // Equals 'path/$param1/$param2'
     *      reverseRoute('Controller::method', $param1, $param2);
     *
     * @param string $search
     * @param        ...$params
     *
     * @return string|false
     */
    public function reverseRoute(string $search, ...$params)
    {
        // Named routes get higher priority.
        if (array_key_exists($search, $this->routes)) {
            return $this->fillRouteParams(key($this->routes[$search]['route']), $params);
        }
        // If it's not a named route, then loop over
        // all routes to find a match.
        foreach ($this->routes as $route) {
            $from = key($route['route']);
            $to   = $route['route'][$from];
            // Lose any namespace slash at beginning of strings
            // to ensure more consistent match.
            $to     = ltrim($to, '\\');
            $search = ltrim($search, '\\');
            // If there's any chance of a match, then it will
            // be with $search at the beginning of the $to string.
            if (strpos($to, $search) !== 0) {
                continue;
            }
            // Ensure that the number of $params given here
            // matches the number of back-references in the route
            if (substr_count($to, '$') != count($params)) {
                continue;
            }

            return $this->fillRouteParams($from, $params);
        }

        // If we're still here, then we did not find a match.
        return false;
    }

    //--------------------------------------------------------------------
    /**
     * Given a
     *
     * @param array      $from
     * @param array|null $params
     *
     * @return string
     */
    protected function fillRouteParams(string $from, array $params = null): string
    {
        // Find all of our back-references in the original route
        preg_match_all('/\(([^)]+)\)/', $from, $matches);
        if (empty($matches[0])) {
            return '/' . ltrim($from, '/');
        }
        // Build our resulting string, inserting the $params in
        // the appropriate places.
        foreach ($matches[0] as $index => $pattern) {
            // Ensure that the param we're inserting matches
            // the expected param type.
            if (preg_match("|{$pattern}|", $params[$index])) {
                $from = str_replace($pattern, $params[$index], $from);
            } else {
                throw new \LogicException('A parameter does not match the expected type.');
            }
        }

        return '/' . ltrim($from, '/');
    }

    //--------------------------------------------------------------------
    /**
     * Does the heavy lifting of creating an actual route. You must specify
     * the request method(s) that this route will work for. They can be separated
     * by a pipe character "|" if there is more than one.
     *
     * @param  string $from
     * @param  array  $to
     * @param array   $options
     */
    protected function create(string $from, $to, array $options = null)
    {
        $prefix = is_null($this->group) ? '' : $this->group . '/';
        $from   = filter_var($prefix . $from, FILTER_SANITIZE_STRING);
        // While we want to add a route within a group of '/',
        // it doens't work with matching, so remove them...
        if ($from != '/') {
            $from = trim($from, '/');
        }
        if (is_null($options)) {
            $options = $this->currentOptions;
        }
        // Hostname limiting?
        if (isset($options['hostname']) && !empty($options['hostname'])) {
            // @todo determine if there's a way to whitelist hosts?
            if (strtolower($_SERVER['HTTP_HOST']) != strtolower($options['hostname'])) {
                return;
            }
        } // Limiting to subdomains?
        else if (isset($options['subdomain']) && !empty($options['subdomain'])) {
            // If we don't match the current subdomain, then
            // we don't need to add the route.
            if (!$this->checkSubdomains($options['subdomain'])) {
                return;
            }
        }
        // Are we offsetting the binds?
        // If so, take care of them here in one
        // fell swoop.
        if (isset($options['offset']) && is_string($to)) {
            // Get a constant string to work with.
            $to = preg_replace('/(\$\d+)/', '$X', $to);
            for ($i = (int)$options['offset'] + 1; $i < (int)$options['offset'] + 7; $i++) {
                $to = preg_replace_callback('/\$X/', function ($m) use ($i) {
                    return '$' . $i;
                }, $to, 1);
            }
        }
        // Replace our regex pattern placeholders with the actual thing
        // so that the Router doesn't need to know about any of this.
        foreach ($this->placeholders as $tag => $pattern) {
            $from = str_ireplace(':' . $tag, $pattern, $from);
        }
        // If no namespace found, add the default namespace
        if (is_string($to) && strpos($to, '\\') === false) {
            $namespace = $options['namespace'] ?? $this->defaultNamespace;
            $to        = trim($namespace, '\\') . '\\' . $to;
        }
        // Always ensure that we escape our namespace so we're not pointing to
        // \CodeIgniter\Routes\Controller::method.
        if (is_string($to)) {
            $to = '\\' . ltrim($to, '\\');
        }
        $name                = $options['as'] ?? $from;
        $this->routes[$name] = [
            'route' => [$from => $to]
        ];
        // Is this a redirect?
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes[$name]['redirect'] = $options['redirect'];
        }
    }

    //--------------------------------------------------------------------
    /**
     * Compares the subdomain(s) passed in against the current subdomain
     * on this page request.
     *
     * @param $subdomains
     *
     * @return bool
     */
    private function checkSubdomains($subdomains)
    {
        if (is_null($this->currentSubdomain)) {
            $this->currentSubdomain = $this->determineCurrentSubdomain();
        }
        if (!is_array($subdomains)) {
            $subdomains = [$subdomains];
        }
        // Routes can be limited to any sub-domain. In that case, though,
        // it does require a sub-domain to be present.
        if (!empty($this->currentSubdomain) && in_array('*', $subdomains)) {
            return true;
        }
        foreach ($subdomains as $subdomain) {
            if ($subdomain == $this->currentSubdomain) {
                return true;
            }
        }

        return false;
    }

    //--------------------------------------------------------------------
    /**
     * Examines the HTTP_HOST to get a best match for the subdomain. It
     * won't be perfect, but should work for our needs.
     *
     * It's especially not perfect since it's possible to register a domain
     * with a period (.) as part of the domain name.
     */
    private function determineCurrentSubdomain()
    {
        $parsedUrl = parse_url($_SERVER['HTTP_HOST']);
        $host      = explode('.', $parsedUrl['host']);
        if ($host[0] == 'www') {
            unset($host[0]);
        }
        // Get rid of any domains, which will be the last
        unset($host[count($host)]);
        // Account for .co.uk, .co.nz, etc. domains
        if (end($host) == 'co') {
            $host = array_slice($host, 0, -1);
        }
        // If we only have 1 part left, then we don't have a sub-domain.
        if (count($host) == 1) {
            // Set it to false so we don't make it back here again.
            return false;
        }

        return array_shift($host);
    }
}
