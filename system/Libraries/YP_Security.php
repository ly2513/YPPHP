<?php
/**
 * User: yongli
 * Date: 17/8/31
 * Time: 21:53
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use YP\Core\YP_IncomingRequest;

/**
 * Class Security
 *
 * @package YP\Libraries\Security
 */
class YP_Security
{

    /**
     * CSRF 哈希值
     *
     * 防止伪造跨站点请求,用于保护cookie的随机散列
     *
     * @var string
     */
    protected $CSRFHash = '';

    /**
     * CSRF的到期时间
     * 伪造跨站点请求保护cookie的到期时间。
     * 默认为两小时（以秒为单位）。
     *
     * @var int
     */
    protected $CSRFExpire = 7200;

    /**
     * CSRF令牌名称
     * 防止伪造跨站点请求的令牌名称用于保护cookie
     *
     * @var string
     */
    protected $CSRFTokenName = 'CSRFToken';

    /**
     * CSRF Cookie 名称
     * 防止伪造的跨站点请求,用于保护cookie
     *
     * @var string
     */
    protected $CSRFCookieName = 'CSRFToken';

    /**
     * CSRF 是否重新生成token
     *
     * 如果为True,将在每一个请求, 重新生成新的csrf token。
     * 如果为False,将Cookie的周期保持不变
     *
     * @var bool
     */
    protected $CSRFRegenerate = true;

    /**
     * Cookie路径
     *
     * @var string
     */
    protected $cookiePath = '/';

    /**
     * 设置站点范围内的Cookie。
     *
     * @var string
     */
    protected $cookieDomain = '';

    /**
     * 只有存在安全HTTPS连接时才设置cookie。
     *
     * @var bool
     */
    protected $cookieSecure = false;

    /**
     * 列出安全的文件名称字符
     *
     * @var array
     */
    public $filenameBadChars = [
        '../',
        '<!--',
        '-->',
        '<',
        '>',
        "'",
        '"',
        '&',
        '$',
        '#',
        '{',
        '}',
        '[',
        ']',
        '=',
        ';',
        '?',
        '%20',
        '%22',
        '%3c', // <
        '%253c', // <
        '%3e', // >
        '%0e', // >
        '%28', // (
        '%29', // )
        '%2528', // (
        '%26', // &
        '%24', // $
        '%3f', // ?
        '%3b', // ;
        '%3d'       // =
    ];

    /**
     * 构造方法,用于初始化工作
     *
     * YP_Security constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        // 储存了CSRF的相关设置
        $this->CSRFExpire     = $config->CSRFExpire;
        $this->CSRFTokenName  = $config->CSRFTokenName;
        $this->CSRFCookieName = $config->CSRFCookieName;
        $this->CSRFRegenerate = $config->CSRFRegenerate;
        if (isset($config->cookiePrefix)) {
            $this->CSRFCookieName = $config->cookiePrefix . $this->CSRFCookieName;
        }
        // 存储与cookie相关的设置
        $this->cookiePath   = $config->cookiePath;
        $this->cookieDomain = $config->cookieDomain;
        $this->cookieSecure = $config->cookieSecure;
        $this->CSRFSetHash();
        unset($config);
    }

    /**
     * CSRF的验证
     *
     * @param YP_IncomingRequest $request
     *
     * @return $this|bool|YP_Security
     */
    public function CSRFVerify(YP_IncomingRequest $request)
    {
        // 如果不是一个POST请求我们将设置CSRF Cookie
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
            return $this->CSRFSetCookie($request);
        }
        // 判断CSRF Token 是否在$_POST和$_COOKIE数组中？
        if (!isset($_POST[$this->CSRFTokenName], $_COOKIE[$this->CSRFCookieName]) || $_POST[$this->CSRFTokenName] !== $_COOKIE[$this->CSRFCookieName]) // Do the tokens match?
        {
            throw new \LogicException('The requested is not allowed', 403);
        }
        // 删除CSRF token,避免污染$_POST数组
        unset($_POST[$this->CSRFTokenName]);
        // 是否每次提交都重新生成新的Cookie
        if ($this->CSRFRegenerate) {
            // 重新生成新的Cookie, 将之前的Cookie删除
            unset($_COOKIE[$this->CSRFCookieName]);
        }
        $this->CSRFSetHash();
        $this->CSRFSetCookie($request);
        log_message('info', 'CSRF token verified');

        return $this;
    }

    /**
     * CSRF 设置Cookie
     *
     * @param YP_IncomingRequest $request
     *
     * @return $this|bool
     */
    public function CSRFSetCookie(YP_IncomingRequest $request)
    {
        $expire        = time() + $this->CSRFExpire;
        $secure_cookie = (bool)$this->cookieSecure;
        if ($secure_cookie && !$request->isSecure()) {
            return false;
        }
        // 设置Cookie
        setcookie($this->CSRFCookieName, $this->CSRFHash, $expire, $this->cookiePath, $this->cookieDomain,
            $secure_cookie, true);
        log_message('info', 'CSRF cookie sent');

        return $this;
    }

    /**
     * 返回当前的CSRF 哈希值
     *
     * @return string
     */
    public function getCSRFHash()
    {
        return $this->CSRFHash;
    }

    /**
     * 返回CSRF的Token名称
     *
     * @return string
     */
    public function getCSRFTokenName()
    {
        return $this->CSRFTokenName;
    }

    /**
     * 设置CSRF 哈希值和Cookie
     *
     * @return string
     */
    protected function CSRFSetHash()
    {
        if ($this->CSRFHash === null) {
            // 如果cookie存在，我们将使用它的值。我们不一定要在每个页面加载下重新生成它，因为一个页面可能包含嵌入的子页，导致这个特性失败。
            if (isset($_COOKIE[$this->CSRFCookieName]) && is_string($_COOKIE[$this->CSRFCookieName]) && preg_match('#^[0-9a-f]{32}$#iS',
                    $_COOKIE[$this->CSRFCookieName]) === 1
            ) {
                return $this->CSRFHash = $_COOKIE[$this->CSRFCookieName];
            }
            $rand           = random_bytes(16);
            $this->CSRFHash = bin2hex($rand);
        }

        return $this->CSRFHash;
    }

    /**
     * 过滤文件名称
     *
     * @param      $str           输入文件名称
     * @param bool $relative_path 是否保留路径
     *
     * @return string
     */
    public function sanitizeFilename($str, $relative_path = false)
    {
        $bad = $this->filenameBadChars;
        if (!$relative_path) {
            $bad[] = './';
            $bad[] = '/';
        }
        $str = remove_invisible_characters($str, false);
        do {
            $old = $str;
            $str = str_replace($bad, '', $str);
        } while ($old !== $str);

        return stripslashes($str);
    }
}
