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
     * 当前使用的方法
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
        // 获取请求方法
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
            // 判断路由是否被收集
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
     * @param string     $name    路由中控制器名称
     * @param array|null $options 可能的值为controller/placeholder
     *
     * @return YP_RouterCollection
     */
    public function resource(string $name, array $options = null): self
    {
        // 为了允许资源的路由被定制，我们需要用新的名字来存储
        $new_name = ucfirst($name);
        // 如果指定一个新的控制器, 将存储这控制器的名称
        if (isset($options['controller'])) {
            $new_name = ucfirst(filter_var($options['controller'], FILTER_SANITIZE_STRING));
        }
        // 为了允许定制ID的值,需要存储这些
        $id = isset($this->placeholders[$this->defaultPlaceholder]) ? $this->placeholders[$this->defaultPlaceholder] : '(:segment)';
        if (isset($options['placeholder'])) {
            $id = $options['placeholder'];
        }
        // 确保 捕获的参数来回引用
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
        // 网站安全
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
     * 指定与多个HTTP谓词匹配的单个路由
     *
     * 例如:
     * $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
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

    /**'
     * 仅用于获取GET请求的路由
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
     * 仅用于获取POST请求的路由
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
     * 仅用于获取PUT请求的路由
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
     * 仅用于获取DELETE请求的路由
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
     * 仅用于获取HEAD请求的路由
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
     * 仅用于获取PATCH请求的路由
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
     * 仅用于获取OPTIONS请求的路由
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
     * 仅用于获取命令行请求的路由
     *
     * @param string     $from
     * @param            $to
     * @param array|null $options
     *
     * @return YP_RouterCollection
     */
    public function cli(string $from, $to, array $options = null): self
    {
        if ($this->HTTPVerb == 'cli')
        {
            $this->create($from, $to, $options);
        }

        return $this;
    }

    /**
     * 将路由限制到指定的环境中，否则它们将无法运行
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

    /**
     * 寻找目标路由
     * 如果存在这样的路由: 'path/(:any)/(:any)' => 'Controller::method/$1/$2'
     * 通过这方法可以知道控制器和方法
     * reverseRoute('Controller::method', $param1, $param2);
     *
     *
     * @param string $search
     * @param array  ...$params
     *
     * @return bool|string
     */
    public function reverseRoute(string $search, ...$params)
    {
        // 首先去已收藏的路由数组中查找
        if (array_key_exists($search, $this->routes)) {
            return $this->fillRouteParams(key($this->routes[$search]['route']), $params);
        }
        // 如果不是已收藏的路由,在全局的路由逐一匹配
        foreach ($this->routes as $route) {
            $from = key($route['route']);
            $to   = $route['route'][$from];
            // 去除命名空间的反斜杠,确保更精确查找匹配目标路由
            $to     = ltrim($to, '\\');
            $search = ltrim($search, '\\');
            // 一旦匹配上,$search的值将出现在$to参数的开始位置
            if (strpos($to, $search) !== 0) {
                continue;
            }
            // 确定$params的数量匹配指定的引用参数的数量一样
            if (substr_count($to, '$') != count($params)) {
                continue;
            }

            return $this->fillRouteParams($from, $params);
        }

        // 说明没找到目标路由
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
        // 在原路由中找到所有的返回引用
        preg_match_all('/\(([^)]+)\)/', $from, $matches);
        if (empty($matches[0])) {
            return '/' . ltrim($from, '/');
        }
        // 建立我们的结果字符串，在适当的地方插入$params。
        foreach ($matches[0] as $index => $pattern) {
            // 确保插入参数匹配预期的参数类型
            if (preg_match("|{$pattern}|", $params[$index])) {
                $from = str_replace($pattern, $params[$index], $from);
            } else {
                throw new \LogicException('A parameter does not match the expected type.');
            }
        }

        return '/' . ltrim($from, '/');
    }

    /**
     * 做实际路由的重载。必须指定此路由将用于的请求方法,如果有一个以上方法,可以通过“|”的分离。
     *
     * @param string     $from
     * @param            $to
     * @param array|null $options
     */
    protected function create(string $from, $to, array $options = null)
    {
        $prefix = is_null($this->group) ? '' : $this->group . '/';
        $from   = filter_var($prefix . $from, FILTER_SANITIZE_STRING);
        // 如果使用路由组中的'/'添加路由,需去除 '/',否则无法匹配
        if ($from != '/') {
            $from = trim($from, '/');
        }
        if (is_null($options)) {
            $options = $this->currentOptions;
        }
        // 主机名限制
        if (isset($options['hostname']) && !empty($options['hostname'])) {
            if (strtolower($_SERVER['HTTP_HOST']) != strtolower($options['hostname'])) {
                return;
            }
        } else if (isset($options['subdomain']) && !empty($options['subdomain'])) {
            // 限制子域名
            // 如果不匹配当前子域，那么没必要添加路由
            if (!$this->checkSubDomains($options['subdomain'])) {
                return;
            }
        }
        if (isset($options['offset']) && is_string($to)) {
            // 得到一个常量字符串
            $to = preg_replace('/(\$\d+)/', '$X', $to);
            for ($i = (int)$options['offset'] + 1; $i < (int)$options['offset'] + 7; $i++) {
                $to = preg_replace_callback('/\$X/', function ($m) use ($i) {
                    return '$' . $i;
                }, $to, 1);
            }
        }
        // 用实际的来替代正则表达式,路由器没必要知道这些
        foreach ($this->placeholders as $tag => $pattern) {
            $from = str_ireplace(':' . $tag, $pattern, $from);
        }
        // 如果没有找到相应的命名空间，请添加默认命名空间
        if (is_string($to) && strpos($to, '\\') === false) {
            $namespace = $options['namespace'] ?? $this->defaultNamespace;
            $to        = trim($namespace, '\\') . '\\' . $to;
        }
        // 始终确保脱离命名空间以至于不能指向\YP\Routes\Controller::method
        if (is_string($to)) {
            $to = '\\' . ltrim($to, '\\');
        }
        $name                = $options['as'] ?? $from;
        $this->routes[$name] = [
            'route' => [$from => $to]
        ];
        // 是否是个重定向路由
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes[$name]['redirect'] = $options['redirect'];
        }
    }

    /**
     * 通过当前页面传递的子域比较多个子域
     *
     * @param $sub_domains
     *
     * @return bool
     */
    private function checkSubDomains($sub_domains)
    {
        if (is_null($this->currentSubdomain)) {
            $this->currentSubdomain = $this->determineCurrentSubDomain();
        }
        if (!is_array($sub_domains)) {
            $sub_domains = [$sub_domains];
        }
        // 路由可以被限制到任何子域。在这种情况下，确实需要一个子域存在。
        if (!empty($this->currentSubdomain) && in_array('*', $sub_domains)) {
            return true;
        }
        foreach ($sub_domains as $sub_domain) {
            if ($sub_domain == $this->currentSubdomain) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取子域名,该方法只是简单做了处理
     *
     * @return bool|mixed
     */
    private function determineCurrentSubDomain()
    {
        $parsedUrl = parse_url($_SERVER['HTTP_HOST']);
        $host      = explode('.', $parsedUrl['host']);
        if ($host[0] == 'www') {
            unset($host[0]);
        }
        // 去除任何域名
        unset($host[count($host)]);
        // Account for .co.uk, .co.nz, etc. domains
        if (end($host) == 'co') {
            $host = array_slice($host, 0, -1);
        }
        // 如果最左边只有一个参数,那么就没有子域名了
        if (count($host) == 1) {
            // 返回FALSE,表示没有子域名
            return false;
        }

        return array_shift($host);
    }
}
