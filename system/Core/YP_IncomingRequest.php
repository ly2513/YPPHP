<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 14:24
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Libraries\YP_FileCollection as FileCollection;
use YP\Libraries\YP_Upload as Upload;
use YP\Core\YP_Request as Request;
use Config\Services;

class YP_IncomingRequest extends Request
{
    /**
     * CSRF 标志
     *
     * @var bool
     */
    protected $enableCSRF = false;

    /**
     * 一个 YP_Uri 实例
     *
     * @var null
     */
    public $uri;

    /**
     * 文件收集器实例
     *
     * @var \YP\Libraries\YP_FileCollection
     */
    protected $files;

    /**
     * Negotiator 实例
     *
     * @var \YP\Core\YP_Negotiate
     */
    protected $negotiate;

    /**
     * 默认的本地请求
     *
     * @var
     */
    protected $defaultLocale;

    /**
     * 当前应用的本地配置,默认值在Config\App.php中设置
     *
     * @vars
     */
    protected $locale;

    /**
     * 存储Code码
     *
     * @var array
     */
    protected $validLocales = [];

    /**
     * 配置信息
     *
     * @var \Config\App
     */
    public $config;
    

    /**
     * YP_IncomingRequest constructor.
     *
     * @param        $config
     * @param null   $uri
     * @param string $body
     */
    public function __construct($config, $uri = null, $body = 'php://input')
    {
        // 获得输入流
        if ($body == 'php://input') {
            $body = file_get_contents('php://input');
        }
        $this->body   = $body;
        $this->config = $config;
        parent::__construct($config);
        $this->populateHeaders();
        $this->uri = $uri;
        $this->detectURI($config->uriProtocol, $config->baseURL);
        $this->validLocales = $config->supportedLocales;
        $this->detectLocale($config);
    }

    /**
     * 设置当前操作配置
     *
     * @param $config
     */
    public function detectLocale($config)
    {
        $this->locale = $this->defaultLocale = $config->defaultLocale;
        if (!$config->negotiateLocale) {
            return;
        }
        $this->setLocale($this->negotiate('language', $config->supportedLocales));
    }

    /**
     * 获得默认的配置信息
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * 获得当前的配置信息
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale ?? $this->defaultLocale;
    }

    /**
     * 设置请求的区域的字符串。
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale(string $locale)
    {
        // 如果不是有效的区域设置，请将其设置为站点的默认区域设置
        if (!in_array($locale, $this->validLocales)) {
            $locale = $this->defaultLocale;
        }
        $this->locale = $locale;
        try {
            if (class_exists('\Locale', false)) {
                \Locale::setDefault($locale);
            }
        } catch (\Exception $e) {
        }

        return $this;
    }

    /**
     * 判断是否为ajax请求
     *
     * @return bool
     */
    public function isAJAX(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * 监测连接是否安全
     * 试图通过几种不同的方法检测当前连接是否安全
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }

    /**
     * 遍历$_REQUEST数组中的值
     *
     * @param null $index
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getVar($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_REQUEST, $index, $filter);
    }

    /**
     * 获得json数据
     *
     * @param bool $assoc true: 转化为数组;false: 转化为对象
     * @param int  $depth
     * @param int  $options
     *
     * @return mixed
     */
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->body, $assoc, $depth, $options);
    }

    /**
     * 获取输入流并解码字符串成数组
     *
     * @return mixed
     */
    public function getRawInput()
    {
        parse_str($this->body, $output);

        return $output;
    }

    /**
     * 遍历GET方法的数据
     *
     * @param null $index null: 表示遍历整个数组; not null: 获取具体的$index的值
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getGet($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_GET, $index, $filter);
    }

    /**
     * 遍历POST方法的数据
     *
     * @param null $index null: 表示遍历整个数组; not null: 获取具体的$index的值
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getPost($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_POST, $index, $filter);
    }

    /**
     * 遍历GET、POST方法的数据
     *
     * @param null $index null: 表示遍历整个数组; not null: 获取具体的$index的值
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getPostGet($index = null, $filter = null)
    {
        return isset($_POST[$index]) ? $this->getPost($index, $filter) : $this->getGet($index, $filter);
    }

    /**
     * 遍历Cookie数据
     *
     * @param null $index null: 表示遍历整个数组; not null: 获取具体的$index的值
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getCookie($index = null, $filter = null)
    {
        return $this->fetchGlobal(INPUT_COOKIE, $index, $filter);
    }

    /**
     * 获取用户代理信息
     *
     * @param null $filter
     *
     * @return array|mixed|null
     */
    public function getUserAgent($filter = null)
    {
        return $this->fetchGlobal(INPUT_SERVER, 'HTTP_USER_AGENT', $filter);
    }

    /**
     * 获得旧的输出
     * 通过redirect_with_input()方法刷新会话,试图获得旧的输入数据。它首先检查旧的POST数据，然后在检查旧的GET数据。
     *
     * @param string $key
     */
    public function getOldInput(string $key)
    {
        // 如果会话还没开始，或者以前没有保存数据
        if (empty($_SESSION['_yp_old_input'])) {
            return;
        }
        // 首先检查POST数组
        if (isset($_SESSION['_yp_old_input']['post'][$key])) {
            return $_SESSION['_yp_old_input']['post'][$key];
        }
        // 再检查GET数组
        if (isset($_SESSION['_yp_old_input']['get'][$key])) {
            return $_SESSION['_yp_old_input']['get'][$key];
        }
    }

    /**
     * 获得所有上传的文件
     *
     * @return array
     */
    public function getFiles(): array
    {
        if (is_null($this->files)) {
            $this->files = new FileCollection();
        }

        return $this->files->all();
    }

    /**
     * 通过输出文件的名称去检索文件,用于下载
     *
     * @param string $fileID
     *
     * @return mixed
     */
    public function getFile(string $fileID)
    {
        if (is_null($this->files)) {
            $this->files = new FileCollection();
        }

        return $this->files->getFile($fileID);
    }

    /**
     * 基于相关配置来设置Uri对象
     * 相关配置指用户配置的baseURL或者由当前的环境决定
     *
     * @param $protocol
     * @param $baseURL
     */
    protected function detectURI($protocol, $baseURL)
    {
        $this->uri->setPath($this->detectPath($protocol));
        // 基于开发者设置baseURL来设置当前域名的
        if (!empty($baseURL)) {
            // 如果我们在子文件夹中,不能在这里添加路径,否则路由可能无法正常工作。所以在这里进行修改
            $this->uri->setScheme(parse_url($baseURL, PHP_URL_SCHEME));
            $this->uri->setHost(parse_url($baseURL, PHP_URL_HOST));
            $this->uri->setPort(parse_url($baseURL, PHP_URL_PORT));
            $this->uri->resolveRelativeURI(parse_url($baseURL, PHP_URL_PATH));
        } else {
            $this->isSecure() ? $this->uri->setScheme('https') : $this->uri->setScheme('http');
            //当SERVER_NAME和HTTP_HOST都开放安全的,如果必须选择的话,首先用server-controlled 版本
            !empty($_SERVER['SERVER_NAME']) ? (isset($_SERVER['SERVER_NAME']) ? $this->uri->setHost($_SERVER['SERVER_NAME']) : null) : (isset($_SERVER['HTTP_HOST']) ? $this->uri->setHost($_SERVER['HTTP_HOST']) : null);
            if (!empty($_SERVER['SERVER_PORT'])) {
                $this->uri->setPort($_SERVER['SERVER_PORT']);
            }
        }
    }

    /**
     * 基于URI协议配置设置，将检测当前URI的路径部分
     *
     * @param $protocol
     *
     * @return string
     */
    public function detectPath($protocol)
    {
        if (empty($protocol)) {
            $protocol = 'REQUEST_URI';
        }
        switch ($protocol) {
            case 'REQUEST_URI':
                $path = $this->parseRequestURI();
                break;
            case 'QUERY_STRING':
                $path = $this->parseQueryString();
                break;
            case 'PATH_INFO':
            default:
                $path = isset($_SERVER[$protocol]) ? $_SERVER[$protocol] : $this->parseRequestURI();
                break;
        }

        return $path;
    }

    /**
     * 使用Negotiate类来处理
     *
     * @param string $type
     * @param array  $supported
     * @param bool   $strictMatch
     *
     * @return mixed
     */
    public function negotiate(string $type, array $supported, bool $strictMatch = false)
    {
        if (is_null($this->negotiate)) {
            $this->negotiate = Services::negotiator($this, true);
        }
        switch (strtolower($type)) {
            case 'media':
                return $this->negotiate->media($supported, $strictMatch);
                break;
            case 'charset':
                return $this->negotiate->charset($supported);
                break;
            case 'encoding':
                return $this->negotiate->encoding($supported);
                break;
            case 'language':
                return $this->negotiate->language($supported);
                break;
        }
        throw new \InvalidArgumentException($type . ' is not a valid negotiation type.');
    }

    /**
     * 通过解析REQUEST_URI,自动检测从它的URI，如果有必要，修复查询字符串
     *
     * @return string 找到的URI
     */
    protected function parseRequestURI(): string
    {
        if (!isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'])) {
            return '';
        }
        // 如果主机不存在,parse_url()会返回FALSE;
        // 路径或查询字符串包含一个冒号，后跟一个数字
        $parts = parse_url('http://dummy' . $_SERVER['REQUEST_URI']);
        $query = isset($parts['query']) ? $parts['query'] : '';
        $uri   = isset($parts['path']) ? $parts['path'] : '';
        if (isset($_SERVER['SCRIPT_NAME'][0])) {
            if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
                $uri = (string)substr($uri, strlen($_SERVER['SCRIPT_NAME']));
            } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
                $uri = (string)substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
            }
        }
        //
        if (trim($uri, '/') === '' && strncmp($query, '/', 1) === 0) {
            $query                   = explode('?', $query, 2);
            $uri                     = $query[0];
            $_SERVER['QUERY_STRING'] = isset($query[1]) ? $query[1] : '';
        } else {
            $_SERVER['QUERY_STRING'] = $query;
        }
        parse_str($_SERVER['QUERY_STRING'], $_GET);
        if ($uri === '/' || $uri === '') {
            return '/';
        }

        return $this->removeRelativeDirectory($uri);
    }

    /**
     * 解析 QUERY_STRING
     * 将解析的QUERY_STRING并且从它的URI自动检测出来。
     *
     * @return    string
     */
    protected function parseQueryString(): string
    {
        $uri = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
        if (trim($uri, '/') === '') {
            return '';
        } elseif (strncmp($uri, '/', 1) === 0) {
            $uri                     = explode('?', $uri, 2);
            $_SERVER['QUERY_STRING'] = isset($uri[1]) ? $uri[1] : '';
            $uri                     = $uri[0];
        }
        parse_str($_SERVER['QUERY_STRING'], $_GET);

        return $this->removeRelativeDirectory($uri);
    }
    
    /**
     * 除去相对目录（/）和多斜线（/ / /)
     *
     * 做最后的清洗这URI并且返回
     *
     * @param $uri
     *
     * @return string
     */
    protected function removeRelativeDirectory($uri)
    {
        $uris = [];
        $tok  = strtok($uri, '/');
        while ($tok !== false) {
            if ((!empty($tok) || $tok === '0') && $tok !== '..') {
                $uris[] = $tok;
            }
            $tok = strtok('/');
        }

        return implode('/', $uris);
    }
}
