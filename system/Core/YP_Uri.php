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

    //--------------------------------------------------------------------
    /**
     * Adds a single new element to the query vars.
     *
     * @param string $key
     * @param null   $value
     *
     * @return $this
     */
    /**
     *
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

    //--------------------------------------------------------------------
    /**
     * Removes one or more query vars from the URI.
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

    //--------------------------------------------------------------------
    /**
     * Filters the query variables so that only the keys passed in
     * are kept. The rest are removed from the object.
     *
     * @param array ...$params
     *
     * @return $this
     */
    public function keepQuery(...$params)
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

    //--------------------------------------------------------------------
    /**
     * Sets the fragment portion of the URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @param string $string
     *
     * @return $this
     */
    public function setFragment(string $string)
    {
        $this->fragment = trim($string, '# ');

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Encodes any dangerous characters, and removes dot segments.
     * While dot segments have valid uses according to the spec,
     * this URI class does not allow them.
     *
     * @param $path
     *
     * @return mixed|string
     */
    protected function filterPath(string $path = null)
    {
        $orig = $path;
        // Decode/normalize percent-encoded chars so
        // we can always have matching for Routes, etc.
        $path = urldecode($path);
        // Remove dot segments
        $path = $this->removeDotSegments($path);
        // Fix up some leading slash edge cases...
        if (strpos($orig, './') === 0) {
            $path = '/' . $path;
        }
        if (strpos($orig, '../') === 0) {
            $path = '/' . $path;
        }
        // Encode characters
        $path = preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function (array $matches) {
                return rawurlencode($matches[0]);
            }, $path);

        return $path;
    }

    //--------------------------------------------------------------------
    /**
     * Saves our parts from a parse_url call.
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
        // Scheme
        if (isset($parts['scheme'])) {
            $this->setScheme(rtrim(strtolower($parts['scheme']), ':/'));
        } else {
            $this->setScheme('http');
        }
        // Port
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
        // Populate our segments array
        if (!empty($parts['path'])) {
            $this->segments = explode('/', trim($parts['path'], '/'));
        }
    }

    //--------------------------------------------------------------------
    /**
     * Combines one URI string with this one based on the rules set out in
     * RFC 3986 Section 2
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2
     *
     * @param string $uri
     *
     * @return \CodeIgniter\HTTP\URI
     */
    public function resolveRelativeURI(string $uri)
    {
        /*
         * NOTE: We don't use removeDotSegments in this
         * algorithm since it's already done by this line!
         */
        $relative = new URI();
        $relative->setURI($uri);
        if ($relative->getScheme() == $this->getScheme()) {
            $relative->setScheme('');
        }
        $transformed = clone $relative;
        // 5.2.2 Transform References in a non-strict method (no scheme)
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

    //--------------------------------------------------------------------
    /**
     * Given 2 paths, will merge them according to rules set out in RFC 2986,
     * Section 5.2
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.3
     *
     * @param URI $base
     * @param URI $reference
     *
     * @return string
     */
    protected function mergePaths(URI $base, URI $reference)
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

    //--------------------------------------------------------------------
    /**
     * Used when resolving and merging paths to correctly interpret and
     * remove single and double dot segments from the path per
     * RFC 3986 Section 5.2.4
     *
     * @see      http://tools.ietf.org/html/rfc3986#section-5.2.4
     *
     * @param string $path
     *
     * @return string
     * @internal param \CodeIgniter\HTTP\URI $uri
     *
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
        // This is not a perfect representation of the
        // RFC, but matches most cases and is pretty
        // much what Guzzle uses. Should be good enough
        // for almost every real use case.
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
            // Add leading slash if necessary
            if (substr($path, 0, 1) == '/') {
                $output = '/' . $output;
            }
            // Add trailing slash if necessary
            if (substr($path, -1, 1) == '/') {
                $output .= '/';
            }
        }

        return $output;
    }

    //--------------------------------------------------------------------
}
