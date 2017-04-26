<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 13:54
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Config\Mimes;
use YP\Libraries\YP_Message as Message;
use YP\Config\ContentSecurityPolicy;

/**
 * Class YP_Response 响应体类
 *
 * @package YP\Core
 */
class YP_Response extends Message
{
    /**
     * HTTP 状态码
     *
     * @var type
     */
    protected static $statusCodes = [
        // 1xx: Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // 2xx: 响应成功
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // 3xx: 重定向
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // 4xx: 客服端错误
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot",
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        // 5xx: 服务器错误
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * 状态码对应的原因短语
     *
     * @var string
     */
    protected $reason;

    /**
     * 当前响应状态码
     *
     * @var int
     */
    protected $statusCode;

    /**
     * 是否执行安全策略的状态,TRUE :执行,FALSE : 否
     *
     * @var bool
     */
    protected $CSPEnabled = false;

    /**
     * 内容安全策略对象
     *
     * @var ContentSecurityPolicy
     */
    public $CSP;

    /**
     * Cookie前缀,主要避免Cookie碰撞
     *
     * @var string
     */
    protected $cookiePrefix = '';

    /**
     * 当前网站的Cookie
     * @var string
     */
    protected $cookieDomain = '';

    /**
     * Cookie路径,默认存在当前操作系统的根目录下
     *
     * @var string
     */
    protected $cookiePath = '/';

    /**
     * Cookie安全状态,当存在安全的https连接,将会设置Cookie
     *
     * @var bool
     */
    protected $cookieSecure = false;

    /**
     * Cookie只能通过HTTP(s)访问标识
     *
     * @var bool
     */
    protected $cookieHTTPOnly = false;

    /**
     * YP_Response constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        // 设置不缓存,同时确保设置Cache-control在响应头里
        $this->noCache();
        // 判断是否执行内容安全策略
        if ($config->CSPEnabled === true) {
            $this->CSP        = new ContentSecurityPolicy();
            $this->CSPEnabled = true;
        }
        $this->cookiePrefix   = $config->cookiePrefix;
        $this->cookieDomain   = $config->cookieDomain;
        $this->cookiePath     = $config->cookiePath;
        $this->cookieSecure   = $config->cookieSecure;
        $this->cookieHTTPOnly = $config->cookieHTTPOnly;
        // 默认内容类型为html,如果开发者需要,可以复写
        $this->setContentType('text/html');
    }

    /**
     * 获取状态码
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        if (empty($this->statusCode)) {
            throw new \BadMethodCallException('HTTP Response is missing a status code');
        }

        return $this->statusCode;
    }

    /**
     * 设置状态码
     *
     * @param int    $code   状态码(3位整数集)
     * @param string $reason 设置状态码使用的原因短语；如果没有，将默认为IANA的名字。
     *
     * @return YP_Response
     */
    public function setStatusCode(int $code, string $reason = ''): self
    {
        $codeLen = count($code);

        // 状态码的有效范围
        if ($codeLen == 3 && ($code < 100 || $code > 599)) {
            // http 报错
            throw new \InvalidArgumentException($code . ' is not a valid HTTP return status code');
        }
        $this->statusCode = $code;
        if (!empty($reason)) {
            $this->reason = $reason;
        } else {
            if ($codeLen == 3 && ($code > 100 || $code < 599)) {
                $this->reason = static::$statusCodes[$code] ?? '';
            }
        }
        return $this;
    }

    /**
     * 根据状态码,获得相应的状态码对应的说明
     *
     * @return string
     */
    public function getReason(): string
    {
        if (empty($this->reason)) {
            return !empty($this->statusCode) ? static::$statusCodes[$this->statusCode] : '';
        }

        return $this->reason;
    }

    /**
     * 设置响应头日期时间
     *
     * @param \DateTime $date
     *
     * @return YP_Response
     */
    public function setDate(\DateTime $date): self
    {
        $date->setTimezone(new \DateTimeZone('UTC'));
        $this->setHeader('Date', $date->format('D, d M Y H:i:s') . ' GMT');

        return $this;
    }

    /**
     * 设置响应头的内容类型以及字符类型
     *
     * @param string $mime
     * @param string $charset
     *
     * @return YP_Response
     */
    public function setContentType(string $mime, string $charset = 'UTF-8'): self
    {
        if (!empty($charset)) {
            $mime .= '; charset=' . $charset;
        }
        $this->setHeader('Content-Type', $mime);

        return $this;
    }

    /**
     * 设置合适的缓存控制头,确保浏览器不会缓存此响应
     *
     * @return YP_Response
     */
    public function noCache(): self
    {
        $this->removeHeader('Cache-control');
        $this->setHeader('Cache-control', ['no-store', 'max-age=0', 'no-cache']);

        return $this;
    }

    /**
     * 设置缓存头
     * $options数组可能像这样的参数
     * $options = [
     *          'max-age'  => 300,
     *          's-maxage' => 900
     *          'etag'     => 'abcde',
     *      ];
     * 值可能为以下几种
     *  - etag
     *  - last-modified
     *  - max-age
     *  - s-maxage
     *  - private
     *  - public
     *  - must-revalidate
     *  - proxy-revalidate
     *  - no-transform
     *
     * @param array $options
     *
     * @return YP_Response
     */
    public function setCache(array $options = []): self
    {
        if (empty($options)) {
            return $this;
        }
        $this->removeHeader('Cache-Control');
        $this->removeHeader('ETag');
        // 设置ETag
        if (isset($options['etag'])) {
            $this->setHeader('ETag', $options['etag']);
            unset($options['etag']);
        }
        // 设置最后修改
        if (isset($options['last-modified'])) {
            $this->setLastModified($options['last-modified']);
            unset($options['last-modified']);
        }
        $this->setHeader('Cache-control', $options);

        return $this;
    }

    /**
     * 设置最后修改日期头
     *
     * @param $date
     *
     * @return YP_Response
     */
    public function setLastModified($date): self
    {
        if ($date instanceof \DateTime) {
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
        } elseif (is_string($date)) {
            $this->setHeader('Last-Modified', $date);
        }

        return $this;
    }

    /**
     * 将输出的信息发送到浏览器
     *
     * @return YP_Response
     */
    public function send(): self
    {
        // 如果正在执行内容安全策略，需要构建它的头文件
        if ($this->CSPEnabled === true) {
            $this->CSP->finalize($this);
        }
        $this->sendHeaders();
        $this->sendBody();

        return $this;
    }

    /**
     * 将HTTP请求头发送到浏览器
     *
     * @return YP_Response
     */
    public function sendHeaders(): self
    {
        // 确认响应头是否已发送
        if (headers_sent()) {
            return $this;
        }
        // 设置响应头时间
        if (isset($this->headers['Date'])) {
            $this->setDate(\DateTime::createFromFormat('U', time()));
        }
        // HTTP状态
        header(sprintf('HTTP/%s %s %s', $this->protocolVersion, $this->statusCode, $this->reason), true,
            $this->statusCode);
        // 发送所有的响应头
        foreach ($this->getHeaders() as $name => $values) {
            header($name . ': ' . $this->getHeaderLine($name), false, $this->statusCode);
        }

        return $this;
    }

    /**
     * 将消息的主体发送到浏览器
     *
     * @return YP_Response
     */
    public function sendBody(): self
    {
        echo $this->body;

        return $this;
    }

    /**
     * 重定向到新的URI
     *
     * @param string   $uri  重定向URI
     * @param string   $method
     * @param int|null $code 重定向状态码 ,默认值:302
     *
     * @throws \Exception
     */
    public function redirect(string $uri, string $method = 'auto', int $code = null)
    {
        // 如果是IIS服务器,用refresh做最好的兼容
        if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],
                'Microsoft-IIS') !== false
        ) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && (empty($code) || !is_numeric($code))) {
            if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                $code = ($_SERVER['REQUEST_METHOD'] !== 'GET') ? 303    // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
                    : 307;
            } else {
                $code = 302;
            }
        }
        switch ($method) {
            case 'refresh':
                $this->setHeader('Refresh', '0;url=' . $uri);
                break;
            default:
                $this->setHeader('Location', $uri);
                break;
        }
        $this->setStatusCode($code);
        $this->sendHeaders();
        // 捕获异常退出
        throw new \Exception('Redirect to ' . $uri, $code);
    }

    /**
     * 设置Cookie
     *
     * @param        $name      Cookie 名称
     * @param string $value     Cookie 值
     * @param string $expire    Cookie 过期时间
     * @param string $domain    Cookie 域 ()
     * @param string $path      Cookie 路径
     * @param string $prefix    Cookie 前缀
     * @param bool   $secure    Cookie 安全策略,是否只通过SSL传输cookie
     * @param bool   $http_only Cookie 访问方式,是否只能通过HTTP访问cookie
     */
    public function setCookie(
        $name,
        $value = '',
        $expire = '',
        $domain = '',
        $path = '/',
        $prefix = '',
        $secure = false,
        $http_only = false
    ) {
        if (is_array($name)) {
            // always leave 'name' in last place, as the loop will break otherwise, due to $$item
            foreach (['value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name'] as $item) {
                if (isset($name[$item])) {
                    $$item = $name[$item];
                }
            }
        }
        if ($prefix === '' && $this->cookiePrefix !== '') {
            $prefix = $this->cookiePrefix;
        }
        if ($domain == '' && $this->cookieDomain != '') {
            $domain = $this->cookieDomain;
        }
        if ($path === '/' && $this->cookiePath !== '/') {
            $path = $this->cookiePath;
        }
        if ($secure === false && $this->cookieSecure === true) {
            $secure = $this->cookieSecure;
        }
        if ($http_only === false && $this->cookieHTTPOnly !== false) {
            $http_only = $this->cookieHTTPOnly;
        }
        if (!is_numeric($expire)) {
            $expire = time() - 86500;
        } else {
            $expire = ($expire > 0) ? time() + $expire : 0;
        }
        setcookie($prefix . $name, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * 下载,生成强烈下载的响应头发送给浏览器进行下载
     *
     * @param string $filename 发送文件的路径
     * @param string $data     下载的数据
     * @param bool   $setMime  是否尝试发送实际MIME类型
     */
    public function download(string $filename = '', $data = '', bool $setMime = false)
    {
        if ($filename === '' || $data === '') {
            return;
        } elseif ($data === null) {
            if (!@is_file($filename) || ($file_size = @filesize($filename)) === false) {
                return;
            }
            $filepath = $filename;
            $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
            $filename = end($filename);
        } else {
            $file_size = strlen($data);
        }
        // 设置默认的 MIME 类型进行发送
        $mime      = 'application/octet-stream';
        $x         = explode('.', $filename);
        $extension = end($x);
        if ($setMime === true) {
            if (count($x) === 1 OR $extension === '') {
                // 如果要检测MIME类型,需要一个文件扩展名
                return;
            }
            $mime = Mimes::guessTypeFromExtension($extension);
        }
        // 对安卓系统下载进行处理
        if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/',
                $_SERVER['HTTP_USER_AGENT'])
        ) {
            $x[count($x) - 1] = strtoupper($extension);
            $filename         = implode('.', $x);
        }
        if ($data === null && ($fp = @fopen($filepath, 'rb')) === false) {
            return;
        }
        // 干净的输出缓冲器
        if (ob_get_level() !== 0 && @ob_end_clean() === false) {
            @ob_clean();
        }
        // 生成服务器响应头部
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: private, no-transform, no-store, must-revalidate');
        // If we have raw data - just dump it
        if ($data !== null) {
            exit($data);
        }
        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, 1048576)) !== false) {
            echo $data;
        }
        fclose($fp);
        exit;
    }

}