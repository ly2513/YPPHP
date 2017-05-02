<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 13:53
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Libraries\YP_Message as Message;

class YP_Request extends Message
{

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
     * Stores the segments of our cli "URI" command.
     *
     * @var array
     */
    protected $segments = [];

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
        $this->parseCommand();
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
        //		if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) 
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
     * Parses the command line it was called from and collects all options
     * and valid segments.
     *
     * NOTE: I tried to use getopt but had it fail occasionally to find
     * any options, where argv has always had our back.
     */
    protected function parseCommand()
    {
        // Since we're building the options ourselves,
        // we stop adding it to the segments array once
        // we have found the first dash.
        $options_found = false;

        $argc = $this->getServer('argc', FILTER_SANITIZE_NUMBER_INT);
        $argv = $this->getServer('argv');

        // We start at 1 since we never want to include index.php
        for ($i = 1; $i < $argc; $i++)
        {
            // If there's no '-' at the beginning of the argument
            // then add it to our segments.
            if ( ! $options_found && strpos($argv[$i], '-') === false)
            {
                $this->segments[] = filter_var($argv[$i], FILTER_SANITIZE_STRING);
                continue;
            }

            $options_found = true;

            if (substr($argv[$i], 0, 1) != '-')
            {
                continue;
            }

            $arg = filter_var(str_replace('-', '', $argv[$i]), FILTER_SANITIZE_STRING);
            $value = null;

            // If the next item starts with a dash it's a value
            if (isset($argv[$i + 1]) && substr($argv[$i + 1], 0, 1) != '-' )
            {
                $value = filter_var($argv[$i + 1], FILTER_SANITIZE_STRING);
                $i++;
            }

            $this->options[$arg] = $value;
        }
    }

    /**
     * Returns the "path" of the request script so that it can be used
     * in routing to the appropriate controller/method.
     *
     * The path is determined by treating the command line arguments
     * as if it were a URL - up until we hit our first option.
     *
     * Example:
     *      php index.php users 21 profile -foo bar
     *
     *      // Routes to /users/21/profile (index is removed for routing sake)
     *      // with the option foo = bar.
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = implode('/', $this->segments);

        return empty($path) ? '' : $path;
    }

}