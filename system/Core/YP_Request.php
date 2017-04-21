<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 13:53
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_Request
{

    /**
     * HTTP 请求头数组
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 请求头参数名称映射
     *
     * @var array
     */
    protected $headerMap = [];

    /**
     * 协议的版本
     *
     * @var
     */
    protected $protocolVersion;

    /**
     * 有效协议的版本列表
     *
     * @var array
     */
    protected $validProtocolVersions = ['1.0', '1.1', '2'];

    /**
     * 消息的主体
     *
     * @var
     */
    protected $body;

    /**
     * 客户端的IP地址
     *
     * @var string
     */
    protected $ipAddress = '';

    /**
     * 代理IP地址
     *
     * @var
     */
    protected $proxyIPs;

    /**
     * 请求的方法
     *
     * @var string
     */
    protected $method;

    /**
     * YP_Request constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        // 代理IPs
        $this->proxyIPs = $config->proxyIPs;
        // 获取当前请求的方法
        $this->method = $this->getServer('REQUEST_METHOD') ?? 'GET';
    }

    /**
     * 获取客户端的IP地址
     *
     * @return string
     */
    public function getIPAddress(): string
    {
        if (!empty($this->ipAddress)) {
            return $this->ipAddress;
        }
        // 代理IPs
        $proxy_ips = $this->proxyIPs;
        if (!empty($this->proxyIPs) && !is_array($this->proxyIPs)) {
            $proxy_ips = explode(',', str_replace(' ', '', $this->proxyIPs));
        }
        // 获取客户端IP地址
        $this->ipAddress = $this->getServer('REMOTE_ADDR');
        if ($proxy_ips) {
            foreach ([
                         'HTTP_X_FORWARDED_FOR',
                         'HTTP_CLIENT_IP',
                         'HTTP_X_CLIENT_IP',
                         'HTTP_X_CLUSTER_CLIENT_IP'
                     ] as $header) {
                if (($spoof = $this->getServer($header)) !== null) {
                    // 获取客户端是通过哪个IP地址来访问我们的
                    sscanf($spoof, '%[^,]', $spoof);
                    if (!$this->isValidIP($spoof)) {
                        $spoof = null;
                    } else {
                        break;
                    }
                }
            }
            if ($spoof) {
                for ($i = 0, $c = count($this->proxyIPs); $i < $c; $i++) {
                    // 对IP地址进行检测
                    if (strpos($proxy_ips[$i], '/') === false) {
                        // 对指定的IP地址进行对比
                        if ($proxy_ips[$i] === $this->ipAddress) {
                            $this->ipAddress = $spoof;
                            break;
                        }
                        continue;
                    }
                    // 存在子网IP,对子网IP进行校验处理
                    isset($separator) OR $separator = $this->isValidIP($this->ipAddress, 'ipv6') ? ':' : '.';
                    // 如果代理的IP地址没有匹配到,直接跳过
                    if (strpos($proxy_ips[$i], $separator) === false) {
                        continue;
                    }
                    // 将服务端IP地址转换为二进制
                    if (!isset($ip, $sprintf)) {
                        if ($separator === ':') {
                            // 确保我们有个全IPv6格式的地址
                            $ip = explode(':',
                                str_replace('::', str_repeat(':', 9 - substr_count($this->ipAddress, ':')),
                                    $this->ipAddress));
                            for ($j = 0; $j < 8; $j++) {
                                $ip[$j] = intval($ip[$j], 16);
                            }
                            $sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
                        } else {
                            $ip      = explode('.', $this->ipAddress);
                            $sprintf = '%08b%08b%08b%08b';
                        }
                        $ip = vsprintf($sprintf, $ip);
                    }
                    // 网络地址
                    $netAddress = '';
                    // 子码掩码的长度
                    $maskLen = 0;
                    // 获得网络地址和子码掩码的长度
                    sscanf($proxy_ips[$i], '%[^/]/%d', $netAddress, $maskLen);
                    // 尽量压缩IPv6地址
                    if ($separator === ':') {
                        $netAddress = explode(':',
                            str_replace('::', str_repeat(':', 9 - substr_count($netAddress, ':')), $netAddress));
                        for ($i = 0; $i < 8; $i++) {
                            $netAddress[$i] = intval($netAddress[$i], 16);
                        }
                    } else {
                        $netAddress = explode('.', $netAddress);
                    }
                    // 转换为二进制进行比较
                    if (strncmp($ip, vsprintf($sprintf, $netAddress), $maskLen) === 0) {
                        $this->ipAddress = $spoof;
                        break;
                    }
                }
            }
        }
        if (!$this->isValidIP($this->ipAddress)) {
            return $this->ipAddress = '0.0.0.0';
        }

        return empty($this->ipAddress) ? '' : $this->ipAddress;
    }

    /**
     * 验证IP地址
     *
     * @param string      $ip
     * @param string|null $which
     *
     * @return bool
     */
    public function isValidIP(string $ip, string $which = null): bool
    {
        switch (strtolower($which)) {
            case 'ipv4':
                $which = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $which = FILTER_FLAG_IPV6;
                break;
            default:
                $which = null;
                break;
        }

        return (bool)filter_var($ip, FILTER_VALIDATE_IP, $which);
    }

    /**
     * 获得请求的方法
     *
     * @param bool $upper
     *
     * @return string
     */
    public function getMethod($upper = false): string
    {
        return ($upper) ? strtoupper($this->method) : strtolower($this->method);
    }

    /**
     * 设置请求的方法,用于欺骗请求
     *
     * @param string $method
     *
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 遍历$_SERVER超全局数组
     *
     * @param null $index
     * @param null $filter
     *
     * @return mixed
     */
    public function getServer($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_SERVER, $index, $filter);
    }

    /**
     * 遍历$_ENV超全局数组
     *
     * @param null $index
     * @param null $filter
     *
     * @return mixed
     */
    public function getEnv($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_ENV, $index, $filter);
    }

    /**
     * 用于遍历超全局数组
     *
     * @param      $type
     * @param null $index
     * @param null $filter
     *
     * @return array|mixed|null
     */
    protected function fetchGlobal($type, $index = null, $filter = null)
    {
        // 设置过滤器
        if (is_null($filter)) {
            $filter = FILTER_DEFAULT;
        }
        // $index为获取的参数名称,如果为空,表示取整个超全局数组
        if (is_null($index)) {
            $loopThrough = [];
            switch ($type) {
                case INPUT_GET    :
                    $loopThrough = $_GET;
                    break;
                case INPUT_POST   :
                    $loopThrough = $_POST;
                    break;
                case INPUT_COOKIE :
                    $loopThrough = $_COOKIE;
                    break;
                case INPUT_SERVER :
                    $loopThrough = $_SERVER;
                    break;
                case INPUT_ENV    :
                    $loopThrough = $_ENV;
                    break;
            }
            $values = [];
            foreach ($loopThrough as $key => $value) {
                $values[$key] = is_array($value) ? $this->fetchGlobal($type, $key, $filter) : filter_var($value,
                    $filter);
            }

            return $values;
        }
        // 允许同时获取多个键值
        if (is_array($index)) {
            $output = [];
            foreach ($index as $key) {
                $output[$key] = $this->fetchGlobal($type, $key, $filter);
            }

            return $output;
        }
        //
        //		// Does the index contain array notation?
        //		if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        //		{
        //			$value = $array;
        //			for ($i = 0; $i < $count; $i++)
        //			{
        //				$key = trim($matches[0][$i], '[]');
        //				if ($key === '') // Empty notation will return the value as array
        //				{
        //					break;
        //				}
        //
        //				if (isset($value[$key]))
        //				{
        //					$value = $value[$key];
        //				}
        //				else
        //				{
        //					return NULL;
        //				}
        //			}
        //		}
        // Due to issues with FastCGI and testing,
        // we need to do these all manually instead
        // of the simpler filter_input();
        // 由于FastCGI和测试问题，我们需要手工代替filter_input()；做简单的数据过滤
        switch ($type) {
            case INPUT_GET:
                $value = $_GET[$index] ?? null;
                break;
            case INPUT_POST:
                $value = $_POST[$index] ?? null;
                break;
            case INPUT_SERVER:
                $value = $_SERVER[$index] ?? null;
                break;
            case INPUT_ENV:
                $value = $_ENV[$index] ?? null;
                break;
            case INPUT_COOKIE:
                $value = $_COOKIE[$index] ?? null;
                break;
            case INPUT_REQUEST:
                $value = $_REQUEST[$index] ?? null;
                break;
            case INPUT_SESSION:
                $value = $_SESSION[$index] ?? null;
                break;
            default:
                $value = '';
        }
        if (is_array($value) || is_object($value) || is_null($value)) {
            return $value;
        }

        return filter_var($value, $filter);
    }

    /**
     * 返回消息的主体
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 设置当前消息主体内容
     *
     * @param $data
     *
     * @return $this
     */
    public function setBody(&$data)
    {
        $this->body = $data;

        return $this;
    }

    /**
     * 将数据追加到当前消息体
     *
     * @param $data
     *
     * @return YP_Request
     */
    public function appendBody($data): self
    {
        $this->body .= (string)$data;

        return $this;
    }

    /**
     * 将$_SERVER中的头部信息存入到$headers
     */
    public function populateHeaders()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : getenv('CONTENT_TYPE');
        if (!empty($contentType)) {
            $this->setHeader('Content-Type', $contentType);
        }
        unset($contentType);
        foreach ($_SERVER as $key => $val) {
            if (sscanf($key, 'HTTP_%s', $header) === 1) {
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));
                if (array_key_exists($key, $_SERVER)) {
                    $this->setHeader($header, $_SERVER[$key]);
                } else {
                    $this->setHeader($header, '');
                }
                // 存放请求头映射关系
                $this->headerMap[strtolower($header)] = $header;
            }
        }
    }

    /**
     * 返回包含所有头部信息数组
     *
     * @return array
     */
    public function getHeaders(): array
    {
        // 如果没有定义头文件，但用户请求它，那么它很可能希望它被填充
        if (empty($this->headers)) {
            $this->populateHeaders();
        }

        return $this->headers;
    }

    /**
     * 返回一个头部对象。如果存在同名的多个标头，则将返回头部对象的数组
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function getHeader($name)
    {
        $orig_name = $this->getHeaderName($name);
        if (!isset($this->headers[$orig_name])) {
            return null;
        }

        return $this->headers[$orig_name];
    }

    /**
     * 判断是否存在某个头部
     *
     * @param $name
     *
     * @return bool
     */
    public function hasHeader($name): bool
    {
        $orig_name = $this->getHeaderName($name);

        return isset($this->headers[$orig_name]);
    }

    /**
     * 用逗号分隔符将单个头部串联起来,并返回这个串联的字符串
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        $orig_name = $this->getHeaderName($name);
        if (!array_key_exists($orig_name, $this->headers)) {
            return '';
        }
        // 如果头部数组中含有一个以上的值,将返回数组的第一个值
        if (is_array($this->headers[$orig_name])) {
            return $this->headers[$orig_name][0]->getValueLine();
        }

        return $this->headers[$orig_name]->getValueLine();
    }

    /**
     * 设置一个头部参数
     *
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value)
    {
        if (!isset($this->headers[$name])) {
            $this->headers[$name]               = new Header($name, $value);
            $this->headerMap[strtolower($name)] = $name;

            return $this;
        }
        if (!is_array($this->headers[$name])) {
            $this->headers[$name] = [$this->headers[$name]];
        }
        if (isset($this->headers[$name])) {
            $this->headers[$name] = new Header($name, $value);
        } else {
            $this->headers[$name][] = new Header($name, $value);
        }

        return $this;
    }

    /**
     * 从头部数组中移除指定的头部参数
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeHeader(string $name)
    {
        $orig_name = $this->getHeaderName($name);
        unset($this->headers[$orig_name]);
        unset($this->headerMap[strtolower($name)]);

        return $this;
    }

    /**
     * 向头部数组添加指定的头部参数
     *
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function appendHeader(string $name, $value)
    {
        $orig_name = $this->getHeaderName($name);
        $this->headers[$orig_name]->appendValue($value);

        return $this;
    }

    //--------------------------------------------------------------------
    /**
     * Adds an additional header value to any headers that accept
     * multiple values (i.e. are an array or implement ArrayAccess)
     *
     * @param string $name
     * @param        $value
     *
     * @return string
     */
    public function prependHeader(string $name, $value)
    {
        $orig_name = $this->getHeaderName($name);
        $this->headers[$orig_name]->prependValue($value);

        return $this;
    }

    /**
     * 获得HTTP请求的协议版本号
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * 设置HTTP协议版本
     *
     * @param string $version
     *
     * @return $this
     */
    public function setProtocolVersion(string $version)
    {
        if (!is_numeric($version)) {
            $version = substr($version, strpos($version, '/') + 1);
        }
        if (!in_array($version, $this->validProtocolVersions)) {
            throw new \InvalidArgumentException('Invalid HTTP Protocol Version. Must be one of: ' . implode(', ',
                    $this->validProtocolVersions));
        }
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * 取出指定的头部名称
     *
     * @param $name
     *
     * @return string
     */
    protected function getHeaderName($name): string
    {
        $lower_name = strtolower($name);

        return isset($this->headerMap[$lower_name]) ? $this->headerMap[$lower_name] : $name;
    }

}