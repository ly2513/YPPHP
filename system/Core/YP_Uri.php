<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_Uri
{

    /**
     * 分隔正则常量
     */
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * 允许的路径中含有的字符
     */
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * 当前URI字符串
     *
     * @var string
     */
    protected $uriString;

    /**
     * 存放URI部分参数
     *
     * @var array
     */
    protected $segments = [];

    /**
     * The URI Scheme.
     *
     * @var
     */
    protected $scheme = 'http';

    /**
     * URI的用户信息
     *
     * @var
     */
    protected $user;

    /**
     * URI的用户密码
     *
     * @var
     */
    protected $password;

    /**
     * URI的主机
     *
     * @var
     */
    protected $host;

    /**
     * URI的端口
     *
     * @var
     */
    protected $port;

    /**
     * URI的路径.
     *
     * @var
     */
    protected $path;

    /**
     * The name of any fragment.
     *
     * @var
     */
    protected $fragment = '';

    /**
     * 查询的字符串
     *
     * @var array
     */
    protected $query = [];

    /**
     * 默认的一些端口
     *
     * @var array
     */
    protected $defaultPorts = [
        'http'  => 80,
        'https' => 443,
        'ftp'   => 21,
        'sftp'  => 22,
    ];

    /**
     * 是否显示密码
     *
     * @var bool
     */
    protected $showPassword = false;

    /**
     * YP_Uri constructor.
     *
     * @param string|null $uri
     */
    public function __construct(string $uri = null)
    {
        if (!is_null($uri)) {
            $this->setURI($uri);
        }
    }

    /**
     * 设置复写当前URI的信息
     *
     * @param string|null $uri
     *
     * @return YP_Uri
     */
    public function setURI(string $uri = null): self
    {
        if (!is_null($uri)) {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException("Unable to parse URI: {$uri}");
            }
            $this->applyParts($parts);
        }

        return $this;
    }

    /**
     * 检索URI的方案组件
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * 检索URI的方案组件
     *
     * @param bool $ignorePort
     *
     * @return string
     */
    public function getAuthority(bool $ignorePort = false): string
    {
        if (empty($this->host)) {
            return '';
        }
        $authority = $this->host;
        if (!empty($this->getUserInfo())) {
            $authority = $this->getUserInfo() . '@' . $authority;
        }
        if (!empty($this->port) && !$ignorePort) {
            // 获得当前的端口
            if ($this->port != $this->defaultPorts[$this->scheme]) {
                $authority .= ':' . $this->port;
            }
        }
        $this->showPassword = false;

        return $authority;
    }

    /**
     * 获取URI中的用户信息
     *
     * @return string
     */
    public function getUserInfo()
    {
        $userInfo = $this->user;
        if ($this->showPassword === true && !empty($this->password)) {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * 是否显示密码,是个bool值,TRUE:显示;FALSE:不显示
     *
     * @param bool $val
     *
     * @return YP_Uri
     */
    public function showPassword(bool $val = true):self
    {
        $this->showPassword = $val;

        return $this;
    }

    /**
     * 获取主机
     *
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 获取端口号
     *
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 获得URI的路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return (is_null($this->path)) ? '' : $this->path;
    }

    /**
     * 检索查询字符串
     *
     * @param array $options
     *
     * @return string
     */
    public function getQuery(array $options = []): string
    {
        $vars = $this->query;
        if (array_key_exists('except', $options)) {
            foreach ($options['except'] as $var) {
                unset($vars[$var]);
            }
        } elseif (array_key_exists('only', $options)) {
            $temp = [];
            foreach ($options['only'] as $var) {
                if (array_key_exists($var, $vars)) {
                    $temp[$var] = $vars[$var];
                }
            }
            $vars = $temp;
        }

        return empty($vars) ? '' : http_build_query($vars);
    }

    /**
     * 检索URI
     *
     * @return string
     */
    public function getFragment(): string
    {
        return is_null($this->fragment) ? '' : $this->fragment;
    }

    /**
     * 获取路径数组
     *
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * 返回URI路径的特定段的值
     *
     * @param int $number
     *
     * @return string
     */
    public function getSegment(int $number): string
    {
        $number -= 1;
        if ($number > count($this->segments)) {
            throw new \InvalidArgumentException('Request URI segment is our of range.');
        }

        return $this->segments[$number];
    }

    /**
     * 返回参数的总数
     *
     * @return int
     */
    public function getTotalSegments(): int
    {
        return count($this->segments);
    }

    /**
     * 获得URI字符串
     *
     * @return string
     */
    public function __toString()
    {
        return self::createURIString($this->getScheme(), $this->getAuthority(), $this->getPath(),
            // Absolute URIs should use a "/" for an empty path
            $this->getQuery(), $this->getFragment());
    }

    /**
     * 构建URI字符串
     *
     * @param null $scheme
     * @param null $authority
     * @param null $path
     * @param null $query
     * @param null $fragment
     *
     * @return string
     */
    public static function createURIString(
        $scheme = null,
        $authority = null,
        $path = null,
        $query = null,
        $fragment = null
    ) {
        $uri = '';
        if (!empty($scheme)) {
            $uri .= $scheme . '://';
        }
        if (!empty($authority)) {
            $uri .= $authority;
        }
        if ($path) {
            $uri .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : $path;
        }
        if ($query) {
            $uri .= '?' . $query;
        }
        if ($fragment) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * 解析给定的字符串
     *
     * @param string $str
     *
     * @return $this
     */
    public function setAuthority(string $str)
    {
        $parts = parse_url($str);
        if (empty($parts['host']) && !empty($parts['path'])) {
            $parts['host'] = $parts['path'];
            unset($parts['path']);
        }
        $this->applyParts($parts);

        return $this;
    }

    /**
     * 设置URI的Scheme
     *
     * @param string $str
     *
     * @return YP_Uri
     */
    public function setScheme(string $str):self
    {
        $str          = strtolower($str);
        $str          = preg_replace('#:(//)?$#', '', $str);
        $this->scheme = $str;

        return $this;
    }

    /**
     * 设置用户信息
     *
     * @param string $user
     * @param string $pass
     *
     * @return YP_Uri
     */
    public function setUserInfo(string $user, string $pass):self
    {
        $this->user     = trim($user);
        $this->password = trim($pass);

        return $this;
    }

    /**
     * 设置主机
     *
     * @param string $str
     *
     * @return YP_Uri
     */
    public function setHost(string $str): self
    {
        $this->host = trim($str);

        return $this;
    }

    /**
     * 设置端口
     *
     * @param $port
     *
     * @return $this
     */
    public function setPort($port)
    {
        if (is_null($port)) {
            return $this;
        }
        if ($port <= 0 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid port given.');
        }
        $this->port = $port;

        return $this;
    }

    /**
     * 设置URI路径
     *
     * @param string $path
     *
     * @return YP_Uri
     */
    public function setPath(string $path):self
    {
        $this->path     = $this->filterPath($path);
        $this->segments = explode('/', $this->path);

        return $this;
    }

    /**
     * 设置查询
     *
     * @param string $query
     *
     * @return YP_Uri
     */
    public function setQuery(string $query): self
    {
        if (strpos($query, '#') !== false) {
            throw new \InvalidArgumentException('Query strings may not include URI fragments.');
        }
        if (!empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }
        $temp  = explode('&', $query);
        $parts = [];
        foreach ($temp as $index => $part) {
            list($key, $value) = $this->splitQueryPart($part);
            if (is_null($value)) {
                $parts[$this->filterQuery($key)] = null;
                continue;
            }
            $parts[$this->filterQuery($key)] = $this->filterQuery($value);
        }
        $this->query = $parts;

        return $this;
    }

    /**
     * 将查询的值拆分成key/value
     *
     * @param string $part
     *
     * @return array|null
     */
    protected function splitQueryPart(string $part)
    {
        $parts = explode('=', $part, 2);
        if (count($parts) === 1) {
            $parts = null;
        }

        return $parts;
    }

    /**
     * 确保查询的字符串符合RFC 3986标准
     *
     * @param $str
     *
     * @return mixed
     */
    protected function filterQuery($str)
    {
        return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function (array $matches) {
                return rawurlencode($matches[0]);
            }, $str);
    }

    /**
     * 设置查询数组
     *
     * @param array $query
     *
     * @return YP_Uri
     */
    public function setQueryArray(array $query)
    {
        $query = http_build_query($query);

        return $this->setQuery($query);
    }

    /**
     * 添加查询字符串
     *
     * @param string $key
     * @param null   $value
     *
     * @return YP_Uri
     */
    public function addQuery(string $key, $value = null): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    /**
     * 删除一个或多个查询变量的URI。
     *
     * @param array ...$params
     *
     * @return $this
     */
    public function stripQuery(...$params)
    {
        foreach ($params as $param) {
            unset($this->query[$param]);
        }

        return $this;
    }

    /**
     * 过滤查询变量，以便只保留传入的键。其余的从对象中移除
     *
     * @param array ...$params
     *
     * @return YP_Uri
     */
    public function keepQuery(...$params): self
    {
        $temp = [];
        foreach ($this->query as $key => $value) {
            if (!in_array($key, $params)) {
                continue;
            }
            $temp[$key] = $value;
        }
        $this->query = $temp;

        return $this;
    }

    /**
     * 设置URI的片段部分
     *
     * @param string $string
     *
     * @return YP_Uri
     */
    public function setFragment(string $string): self
    {
        $this->fragment = trim($string, '# ');

        return $this;
    }

    /**
     * 过滤uri路径
     *
     * @param string|null $path
     *
     * @return mixed|string
     */
    protected function filterPath(string $path = null)
    {
        $orig = $path;
        // 解析路径
        $path = urldecode($path);
        // Remove dot segments
        $path = $this->removeDotSegments($path);
        // 修复斜线边
        if (strpos($orig, './') === 0) {
            $path = '/' . $path;
        }
        if (strpos($orig, '../') === 0) {
            $path = '/' . $path;
        }
        // 字符编码
        $path = preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function (array $matches) {
                return rawurlencode($matches[0]);
            }, $path);

        return $path;
    }

    /**
     * 保存parse_url的以下
     *
     * @param $parts
     */
    protected function applyParts($parts)
    {
        if (!empty($parts['host'])) {
            $this->host = $parts['host'];
        }
        if (!empty($parts['user'])) {
            $this->user = $parts['user'];
        }
        if (!empty($parts['path'])) {
            $this->path = $this->filterPath($parts['path']);
        }
        if (!empty($parts['query'])) {
            $this->setQuery($parts['query']);
        }
        if (!empty($parts['fragment'])) {
            $this->fragment = $this->filterQuery($parts['fragment']);
        }
        //
        if (isset($parts['scheme'])) {
            $this->setScheme(rtrim(strtolower($parts['scheme']), ':/'));
        } else {
            $this->setScheme('http');
        }
        // 端口
        if (isset($parts['port'])) {
            if (!is_null($parts['port'])) {
                $port = (int)$parts['port'];
                if (1 > $port || 0xffff < $port) {
                    throw new \InvalidArgumentException('Ports must be between 1 and 65535');
                }
                $this->port = $port;
            }
        }
        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }
        // 对路径进行拆分
        if (!empty($parts['path'])) {
            $this->segments = explode('/', trim($parts['path'], '/'));
        }
    }

    /**
     * 处理相对URI
     *
     * @param string $uri
     *
     * @return URI
     */
    public function resolveRelativeURI(string $uri)
    {
        /**
         * 提示:如果已经这样做了,就没必要使用removeDotSegments这个算法了
         */
        $relative = new YP_Uri();
        $relative->setURI($uri);
        if ($relative->getScheme() == $this->getScheme()) {
            $relative->setScheme('');
        }
        $transformed = clone $relative;
        // 在非严格方法中转换引用
        if (!empty($relative->getAuthority())) {
            $transformed->setAuthority($relative->getAuthority())->setPath($relative->getPath())->setQuery($relative->getQuery());
        } else {
            if ($relative->getPath() == '') {
                $transformed->setPath($this->getPath());
                if (!is_null($relative->getQuery())) {
                    $transformed->setQuery($relative->getQuery());
                } else {
                    $transformed->setQuery($this->getQuery());
                }
            } else {
                if (substr($relative->getPath(), 0, 1) == '/') {
                    $transformed->setPath($relative->getPath());
                } else {
                    $transformed->setPath($this->mergePaths($this, $relative));
                }
                $transformed->setQuery($relative->getQuery());
            }
            $transformed->setAuthority($this->getAuthority());
        }
        $transformed->setScheme($this->getScheme());
        $transformed->setFragment($relative->getFragment());

        return $transformed;
    }

    /**
     * 将根据RFC 2986中的规则合并给定的两个路径
     *
     * @param YP_Uri $base
     * @param YP_Uri $reference
     *
     * @return string
     */
    protected function mergePaths(YP_Uri $base, YP_Uri $reference)
    {
        if (!empty($base->getAuthority()) && empty($base->getPath())) {
            return '/' . ltrim($base->getPath(), '/ ');
        }
        $path = explode('/', $base->getPath());
        if (empty($path[0])) {
            unset($path[0]);
        }
        array_pop($path);
        array_push($path, $reference->getPath());

        return implode('/', $path);
    }

    /**
     * 该方法用于分解并且合并当前的路径,相当于对路径进行过滤()
     *
     * @param string $path
     *
     * @return string
     */
    public function removeDotSegments(string $path): string
    {
        if (empty($path) || $path == '/') {
            return $path;
        }
        $output = [];
        $input  = explode('/', $path);
        if (empty($input[0])) {
            unset($input[0]);
            $input = array_values($input);
        }
        // 这不是一个RFC完美的表现，但大多数情况下，匹配更多的例子几乎都是常用的。几乎每一个真正的用例,应该是足够好。
        foreach ($input as $segment) {
            if ($segment == '..') {
                array_pop($output);
            } else if ($segment != '.' && $segment != '') {
                array_push($output, $segment);
            }
        }
        $output = implode('/', $output);
        $output = ltrim($output, '/ ');
        if ($output != '/') {
            // 如有必要添加斜线
            if (substr($path, 0, 1) == '/') {
                $output = '/' . $output;
            }
            // 如果需要添加尾斜杠
            if (substr($path, -1, 1) == '/') {
                $output .= '/';
            }
        }

        return $output;
    }

}
