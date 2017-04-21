<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 17:02
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_RouterCollection
{
    /**
     * 默认命名空间
     *
     * @var string
     */
    protected $defaultNamespace = '\\';

    /**
     * 默认的控制器
     *
     * @var string
     */
    protected $defaultController = 'Home';

    /**
     * 默认的方法
     *
     * @var string
     */
    protected $defaultMethod = 'index';

    /**
     * 路由资源使用的占位符
     *
     * @var string
     */
    protected $defaultPlaceholder = 'any';

    /**
     * 是否将破折号转换为下划线在URI,为true时进行转换
     *
     * @var bool
     */
    protected $translateURIDashes = false;

    /**
     * 是否自动与控制器匹配URL,true:是,false:否
     *
     * @var bool
     */
    protected $autoRoute = true;

    /**
     * 当路由无法匹配时,回调显示
     *
     * @var
     */
    protected $override404;

    /**
     * 定位占位符,用于处理RESTful风格的路由
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
     * 存放所有路由映射关系
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 当前脚本正使用的HTTP方法
     *
     * @var string
     */
    protected $HTTPVerb;

    /**
     * 默认的HTTP(cli)方法列表
     *
     * @var array
     */
    protected $defaultHTTPMethods = ['options', 'get', 'head', 'post', 'put', 'delete', 'trace', 'connect', 'cli'];

    /**
     * 当前组的名称
     *
     * @var null
     */
    protected $group = null;

    /**
     * 当前的子域
     * @var null
     */
    protected $currentSubDomain = null;

    /**
     * 当前操作
     *
     * @var null
     */
    protected $currentOptions = null;

    /**
     * YP_RouterCollection constructor.
     */
    public function __construct()
    {
        // 当前脚本正使用的HTTP方法
        $this->HTTPVerb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';
    }

    /**
     * 添加占位符
     *
     * @param string      $placeholder 占位符
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
     * 没有指定其他命名空间时, 设置默认命名空间以供控制器使用时。
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
     * 设置默认的方法
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
     * 告诉系统是否将转换为URI字符串
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
     * 如果TRUE，当没有匹配到路由时,系统将尝试匹配的URI,在APP_PATH/控制器目录下对所有的文件夹/文件进行控制器匹配
     * 如果FALSE, 不做自动路由匹配。
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
     * 当没有匹配到路由时,设置被调用的类、方法,它可以是一个闭包或者类/方法,名称完全可以像User::index定义的路由
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
     * 返回404个重写设置，它可以为NULL，闭包或控制器/字符串。
     *
     * @return mixed
     */
    public function get404Override()
    {
        return $this->override404;
    }

    /**
     * 设置系统中要使用的默认约束。通常使用“resources”方法。
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
     * 返回默认的带有命名空间的控制器
     *
     * @return string
     */
    public function getDefaultController(): string
    {
        return $this->defaultController;
    }

    /**
     * 返回默认的方法
     *
     * @return string
     */
    public function getDefaultMethod(): string
    {
        return $this->defaultMethod;
    }

    /**
     * 返回在路由配置文件中设置的默认命名空间。
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    /**
     * 返回当前 translateURIDashses 的设置
     * @return bool
     */
    public function shouldTranslateURIDashes(): bool
    {
        return $this->translateURIDashes;
    }

    /**
     * 返回标志,是否自动路由
     *
     * @return bool
     */
    public function shouldAutoRoute(): bool
    {
        return $this->autoRoute;
    }

    /**
     * 返回可用的路由
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
     * 返回当前使用的HTTP方法
     *
     * @return string
     */
    public function getHTTPVerb(): string
    {
        return $this->HTTPVerb;
    }

    /**
     * 快速添加多个路由
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
     * Example: $routes->add('news', 'Posts::index');
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
     * @param string $from   对抗模式
     * @param string $to     要重定向到的路由名或URI
     * @param int    $status 根据HTTP状态码重定向
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
     * 确定路由是否是重定向路由
     *
     * @param string $from
     *
     * @return bool
     */
    public function isRedirect(string $from): bool
    {
        foreach ($this->routes as $name => $route) {
            // 指定路由
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
            // Named route?
            if ($name == $from || key($route['route']) == $from) {
                return $route['redirect'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * 方便将项目分组到管理区域
     *
     * Example:
     *     // Creates route: admin/users
     *     $route->group('admin', function() {
     *            $route->resources('users');
     *     });
     *
     * @param       $name 路由分组/前缀的名称
     * @param array ...$params
     */
    public function group($name, ...$params)
    {
        $oldGroup   = $this->group;
        $oldOptions = $this->currentOptions;
        // 为了注册路由，我们将设置一个标志，使我们的路由器会看到是组名
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

    /**
     * 创建控制器的基于HTTP谓词的路由集合
     *
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
     * @param string     $name    路由中控制器的名称
     * @param array|null $options 自定义路由的可能方法列表
     *
     * @return YP_RouterCollection
     */
    public function resource(string $name, array $options = null): self
    {
        //  为了自定义路由资源,需要用一个新的名称来存储路由值
        $new_name = ucfirst($name);
        // 如果指定了一个新的控制器，那么我们用$name来存储新的控制器的值
        if (isset($options['controller'])) {
            $new_name = ucfirst(filter_var($options['controller'], FILTER_SANITIZE_STRING));
        }
        // 为了允许的定制ID的值,我们需要一个地方来储存它们
        $id = isset($this->placeholders[$this->defaultPlaceholder]) ? $this->placeholders[$this->defaultPlaceholder] : '(:segment)';
        if (isset($options['placeholder'])) {
            $id = $options['placeholder'];
        }
        // 确保我们捕获返回的资源
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

    /**
     * 指定与多个HTTP Verbs匹配的单个路由
     * Example:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
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

    /**
     * 指定捕获GET方法的路由
     *
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

    /**
     * 指定捕获POST方法的路由
     *
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

    /**
     * 指定捕获PUT方法的路由
     *
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

    /**
     * 指定捕获DELETE方法的路由
     *
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

    /**
     * 指定捕获HEAD方法的路由
     *
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

    /**
     * 指定捕获PATCH方法的路由
     *
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

    /**
     * 指定捕获OPTIONS方法的路由
     *
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

    /**
     * 指定捕获命令行请求的路由
     *
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
    /**
     * 
     * 
     * @param string   $env
     * @param \Closure $callback
     *
     * @return YP_RouterCollection
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

    /**
     *
     *
     * @param string     $from
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
            if (!$this->checkSubDomains($options['subdomain'])) {
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
    private function checkSubDomains($subdomains)
    {
        if (is_null($this->currentSubDomain)) {
            $this->currentSubDomain = $this->determineCurrentSubdomain();
        }
        if (!is_array($subdomains)) {
            $subdomains = [$subdomains];
        }
        // Routes can be limited to any sub-domain. In that case, though,
        // it does require a sub-domain to be present.
        if (!empty($this->currentSubDomain) && in_array('*', $subdomains)) {
            return true;
        }
        foreach ($subdomains as $subdomain) {
            if ($subdomain == $this->currentSubDomain) {
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
