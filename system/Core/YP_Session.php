<?php
/**
 * User: yongli
 * Date: 17/5/2
 * Time: 17:28
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use Psr\Log\LoggerAwareTrait;

/**
 * Class YP_Session session处理类
 *
 * @package YP\Core
 */
class YP_Session
{

    use LoggerAwareTrait;

    /**
     * 使用驱动实例
     *
     * @var \SessionHandlerInterface
     */
    protected $driver;

    /**
     * session的驱动名称,值为:files, database, redis
     *
     * @var string
     */
    protected $sessionDriverName;

    /**
     * session、cookie名称,必须包含[0-9a-z_-]字符
     *
     * @var string
     */
    protected $sessionCookieName = 'yp_session';

    /**
     * 会话持续的时间(秒),设置为0,表示当浏览器关闭时过期
     *
     * @var int
     */
    protected $sessionExpiration = 7200;

    /**
     * session保存位置,依赖于所使用的驱动
     *
     * 对于"files"驱动来说,是一个可写的目录路径,目前仅支持绝对路径
     * 对于"database"驱动来说,就是一个表名
     *
     * @var null
     */
    protected $sessionSavePath = null;

    /**
     * 读取会话数据时是否匹配用户的IP地址
     * 注意:如果正在使用"database"驱动程序，在更改此设置时,请不要忘记更新会话表的主键。
     *
     * @var bool
     */
    protected $sessionMatchIP = false;

    /**
     * 隔多久(秒)再次生成会话ID
     *
     * @var ints
     */
    protected $sessionTimeToUpdate = 300;
    
    /**
     * 是否在重新生成会话ID时,自动销毁与旧会话ID关联的会话数据
     * 当设置为false时，数据将由垃圾收集器稍后删除
     *
     * @var bool
     */
    protected $sessionRegenerateDestroy = false;

    /**
     * 用于Cookie的域名
     *
     * @var string
     */
    protected $cookieDomain = '';

    /**
     * 用于存储cookie的路径
     * 通常会是一个斜线
     *
     * @var string
     */
    protected $cookiePath = '/';

    /**
     * 如果有安全HTTPS连接存在,将只设置Cookie
     *
     * @var bool
     */
    protected $cookieSecure = false;

    protected $sidRegexp;

    /**
     * 日志对象
     *
     * @var \PSR\Log\LoggerInterface
     */
    protected $logger;

    //--------------------------------------------------------------------
    /**
     * Constructor.
     *
     * Extract configuration settings and save them here.
     *
     * @param \SessionHandlerInterface $driver
     * @param \Config\App              $config
     */
    public function __construct(\SessionHandlerInterface $driver, $config)
    {
        $this->driver                   = $driver;
        $this->sessionDriverName        = $config->sessionDriver;
        $this->sessionCookieName        = $config->sessionCookieName;
        $this->sessionExpiration        = $config->sessionExpiration;
        $this->sessionSavePath          = $config->sessionSavePath;
        $this->sessionMatchIP           = $config->sessionMatchIP;
        $this->sessionTimeToUpdate      = $config->sessionTimeToUpdate;
        $this->sessionRegenerateDestroy = $config->sessionRegenerateDestroy;
        $this->cookieDomain             = $config->cookieDomain;
        $this->cookiePath               = $config->cookiePath;
        $this->cookieSecure             = $config->cookieSecure;
    }

    /**
     * 初始化会话容器并启动会话
     */
    public function start()
    {
        if (is_cli() && ENVIRONMENT !== 'testing') {
            $this->logger->debug('Session: Initialization under CLI aborted.');

            return;
        } elseif ((bool)ini_get('session.auto_start')) {
            $this->logger->error('Session: session.auto_start is enabled in php.ini. Aborting.');

            return;
        }
        if (!$this->driver instanceof \SessionHandlerInterface) {
            $this->logger->error("Session: Handler '" . $this->driver . "' doesn't implement SessionHandlerInterface. Aborting.");
        }
        $this->configure();
        $this->setSaveHandler();
        // 干净的Cookie
        if (isset($_COOKIE[$this->sessionCookieName]) && (!is_string($_COOKIE[$this->sessionCookieName]) || !preg_match('#\A' . $this->sidRegexp . '\z#',
                    $_COOKIE[$this->sessionCookieName]))
        ) {
            unset($_COOKIE[$this->sessionCookieName]);
        }
        $this->startSession();
        // 会话ID自动更新配置(忽略ajax请求)
        if ((empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') && ($regenerate_time = $this->sessionTimeToUpdate) > 0) {
            if (!isset($_SESSION['__yp_last_regenerate'])) {
                $_SESSION['__yp_last_regenerate'] = time();
            } elseif ($_SESSION['__yp_last_regenerate'] < (time() - $regenerate_time)) {
                $this->regenerate((bool)$this->sessionRegenerateDestroy);
            }
        } elseif (isset($_COOKIE[$this->sessionCookieName]) && $_COOKIE[$this->sessionCookieName] === session_id()) {
            // 设置Cookie
            $this->setCookie();
        }
        $this->initVars();
        $this->logger->info("Session: Class initialized using '" . $this->sessionDriverName . "' driver.");
    }

    /**
     * 停止会话
     * 销毁会话,销毁Cookie,设置Session_id
     */
    public function stop()
    {
        setcookie($this->sessionCookieName, session_id(), 1, $this->cookiePath, $this->cookieDomain,
            $this->cookieSecure, true);
        session_regenerate_id(true);
    }

    /**
     * 配置信息,使用默认配置
     */
    protected function configure()
    {
        if (empty($this->sessionCookieName)) {
            $this->sessionCookieName = ini_get('session.name');
        } else {
            ini_set('session.name', $this->sessionCookieName);
        }
        session_set_cookie_params($this->sessionExpiration, $this->cookiePath, $this->cookieDomain, $this->cookieSecure,
            true);
        if (empty($this->sessionExpiration)) {
            $this->sessionExpiration = (int)ini_get('session.gc_maxlifetime');
        } else {
            ini_set('session.gc_maxlifetime', (int)$this->sessionExpiration);
        }
        // 安全为主
        ini_set('session.use_trans_sid', 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        $this->configureSidLength();
    }

    /**
     * 配置sessionID的长度
     */
    protected function configureSidLength()
    {
        if (PHP_VERSION_ID < 70100) {
            $bits          = 160;
            $hash_function = ini_get('session.hash_function');
            if (ctype_digit($hash_function)) {
                if ($hash_function !== '1') {
                    ini_set('session.hash_function', 1);
                    $bits = 160;
                }
            } elseif (!in_array($hash_function, hash_algos(), true)) {
                ini_set('session.hash_function', 1);
                $bits = 160;
            } elseif (($bits = strlen(hash($hash_function, 'dummy', false)) * 4) < 160) {
                ini_set('session.hash_function', 1);
                $bits = 160;
            }
            $bits_per_character = (int)ini_get('session.hash_bits_per_character');
            $sid_length         = (int)ceil($bits / $bits_per_character);
        } else {
            $bits_per_character = (int)ini_get('session.sid_bits_per_character');
            $sid_length         = (int)ini_get('session.sid_length');
            if (($sid_length * $bits_per_character) < 160) {
                $bits = ($sid_length * $bits_per_character);
                // 添加尽可能多的字符，以达到至少160位
                $sid_length += (int)ceil((160 % $bits) / $bits_per_character);
                ini_set('session.sid_length', $sid_length);
            }
        }
        // 4、5、6是唯一已知的可能值
        switch ($bits_per_character) {
            case 4:
                $this->sidRegexp = '[0-9a-f]';
                break;
            case 5:
                $this->sidRegexp = '[0-9a-v]';
                break;
            case 6:
                $this->sidRegexp = '[0-9a-zA-Z,-]';
                break;
        }
        $this->sidRegexp .= '{' . $sid_length . '}';
    }

    /**
     * 处理临时变量
     *
     * 清除旧的闪存数据
     *
     */
    protected function initVars()
    {
        if (empty($_SESSION['__yp_vars'])) {
            return;
        }
        $current_time = time();
        foreach ($_SESSION['__yp_vars'] as $key => &$value) {
            if ($value === 'new') {
                $_SESSION['__yp_vars'][$key] = 'old';
            } elseif ($value < $current_time) {
                unset($_SESSION[$key], $_SESSION['__yp_vars'][$key]);
            }
        }
        if (empty($_SESSION['__yp_vars'])) {
            unset($_SESSION['__yp_vars']);
        }
    }

    /**
     * 再生会话ID
     *
     * @param bool $destroy 旧的会话数据是否被销毁
     */
    public function regenerate(bool $destroy = false)
    {
        $_SESSION['__yp_last_regenerate'] = time();
        session_regenerate_id($destroy);
    }

    /**
     * 销毁当前的会话
     */
    public function destroy()
    {
        session_destroy();
    }

    /**
     * 将用户数据设置到会话中
     *
     * @param      $data  如果$data是字符串,将解析为key,$value为值;$data为数组,期望是键值对数组
     * @param null $value
     */
    public function set($data, $value = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                if (is_int($key)) {
                    $_SESSION[$value] = null;
                } else {
                    $_SESSION[$key] = $value;
                }
            }

            return;
        }
        $_SESSION[$data] = $value;
    }

    /**
     * 获取已在会话中设置的用户数据
     *
     * 如果该属性值存在,则返回;不存在,将返回旧数据或
     *
     * @param string|null $key 要检索的会话属性的标识符
     *
     * @return array|null  属性值
     */
    public function get(string $key = null)
    {
        if (isset($key)) {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        } elseif (empty($_SESSION)) {
            return [];
        }
        $userdata = [];
        $_exclude = array_merge(['__yp_vars'], $this->getFlashKeys(), $this->getTempKeys());
        $keys     = array_keys($_SESSION);
        foreach ($keys as $key) {
            if (!in_array($key, $_exclude, true)) {
                $userdata[$key] = $_SESSION[$key];
            }
        }

        return $userdata;
    }

    /**
     * 返回会话数组中是否存在索引
     *
     * @param string $key 属性名称
     *
     * @return bool
     */
    public function has(string $key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * 删除一个或多个session属性
     *
     * @param $key
     */
    public function remove($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($_SESSION[$k]);
            }

            return;
        }
        unset($_SESSION[$key]);
    }

    /**
     * 魔术方法设置session中的值
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * 魔术方法取出session中的值
     *
     * @param $key
     *
     * @return null|string
     */
    public function __get($key)
    {
        // 注意：保持此顺序相同，万一有人想用“session_id '作为一个会话数据的关键，无论什么原因
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } elseif ($key === 'session_id') {
            return session_id();
        }

        return null;
    }

    /**
     * 将数据设置为只为单个请求持续的会话,完美的使用与使用状态更新消息
     *
     * @param      $data  属性标识符或属性关联数组
     * @param null $value 如果$data是标量,为属性值
     */
    public function setFlashData($data, $value = null)
    {
        $this->set($data, $value);
        $this->markAsFlashData(is_array($data) ? array_keys($data) : $data);
    }

    /**
     * 从会话中检索一个或多个'闪存'数据项。
     *
     * @param string|null $key
     *
     * @return array|null
     */
    public function getFlashData(string $key = null)
    {
        if (isset($key)) {
            return (isset($_SESSION['__yp_vars'], $_SESSION['__yp_vars'][$key], $_SESSION[$key]) && !is_int($_SESSION['__yp_vars'][$key])) ? $_SESSION[$key] : null;
        }
        $flashData = [];
        if (!empty($_SESSION['__yp_vars'])) {
            foreach ($_SESSION['__yp_vars'] as $key => &$value) {
                is_int($value) OR $flashData[$key] = $_SESSION[$key];
            }
        }

        return $flashData;
    }

    /**
     * 保存闪存数据
     *
     * @param string $key
     */
    public function keepFlashData(string $key)
    {
        $this->markAsFlashData($key);
    }

    /**
     * 将一个会话属性或者多个属性作为闪存数据
     *
     * @param $key
     *
     * @return bool 如果许多属性没有设置,将返回FALSE
     */
    public function markAsFlashData($key)
    {
        if (is_array($key)) {
            for ($i = 0, $c = count($key); $i < $c; $i++) {
                if (!isset($_SESSION[$key[$i]])) {
                    return false;
                }
            }
            $new                   = array_fill_keys($key, 'new');
            $_SESSION['__yp_vars'] = isset($_SESSION['__yp_vars']) ? array_merge($_SESSION['__yp_vars'], $new) : $new;

            return true;
        }
        if (!isset($_SESSION[$key])) {
            return false;
        }
        $_SESSION['__yp_vars'][$key] = 'new';

        return true;
    }

    /**
     * 不将session数据设置为闪存数据
     *
     * @param $key 可以是关联数组,也可以是单个的属性名称
     */
    public function unMarkFlashData($key)
    {
        if (empty($_SESSION['__yp_vars'])) {
            return;
        }
        is_array($key) OR $key = [$key];
        foreach ($key as $k) {
            if (isset($_SESSION['__yp_vars'][$k]) && !is_int($_SESSION['__yp_vars'][$k])) {
                unset($_SESSION['__yp_vars'][$k]);
            }
        }
        if (empty($_SESSION['__yp_vars'])) {
            unset($_SESSION['__yp_vars']);
        }
    }

    /**
     * 检索session数据中所有的键值标识为闪存数据
     *
     * @return array
     */
    public function getFlashKeys()
    {
        if (!isset($_SESSION['__yp_vars'])) {
            return [];
        }
        $keys = [];
        foreach (array_keys($_SESSION['__yp_vars']) as $key) {
            is_int($_SESSION['__yp_vars'][$key]) OR $keys[] = $key;
        }

        return $keys;
    }

    /**
     * 在会话中设置新数据，并将其设置带有过期时间的临时数据
     *
     * @param      $data  session数据
     * @param null $value 存储的值
     * @param int  $ttl   过期时间(秒)
     */
    public function setTempData($data, $value = null, $ttl = 300)
    {
        $this->set($data, $value);
        $this->markAsTempData($data, $ttl);
    }

    /**
     * 返回当前的session的临时数据
     *
     * @param null $key session数据的键值
     *
     * @return array|null  session数据的值,如果没找到返回null
     */
    public function getTempData($key = null)
    {
        if (isset($key)) {
            return (isset($_SESSION['__yp_vars'], $_SESSION['__yp_vars'][$key], $_SESSION[$key]) && is_int($_SESSION['__yp_vars'][$key])) ? $_SESSION[$key] : null;
        }
        $tempData = [];
        if (!empty($_SESSION['__yp_vars'])) {
            foreach ($_SESSION['__yp_vars'] as $key => &$value) {
                is_int($value) && $tempData[$key] = $_SESSION[$key];
            }
        }

        return $tempData;
    }

    /**
     * 删除session数据中指定的数据
     *
     * @param $key session数据的键名
     */
    public function removeTempData($key)
    {
        $this->unMarkTempData($key);
        unset($_SESSION[$key]);
    }

    /**
     * 将session 数据设置过期时间
     *
     * @param     $key session数据的键名
     * @param int $ttl 过期时间
     *
     * @return bool
     */
    public function markAsTempData($key, $ttl = 300)
    {
        $ttl += time();
        if (is_array($key)) {
            $temp = [];
            foreach ($key as $k => $v) {
                if (is_int($k)) {
                    $k = $v;
                    $v = $ttl;
                } elseif (is_string($v)) {
                    $v = time() + $ttl;
                } else {
                    $v += time();
                }
                if (!array_key_exists($k, $_SESSION)) {
                    return false;
                }
                $temp[$k] = $v;
            }
            $_SESSION['__yp_vars'] = isset($_SESSION['__yp_vars']) ? array_merge($_SESSION['__yp_vars'], $temp) : $temp;

            return true;
        }
        if (!isset($_SESSION[$key])) {
            return false;
        }
        $_SESSION['__yp_vars'][$key] = $ttl;

        return true;
    }

    /**
     * 取消标记临时数据会话，只要会话存在,数据不过期
     *
     * @param $key
     */
    public function unMarkTempData($key)
    {
        if (empty($_SESSION['__yp_vars'])) {
            return;
        }
        is_array($key) OR $key = [$key];
        foreach ($key as $k) {
            if (isset($_SESSION['__yp_vars'][$k]) && is_int($_SESSION['__yp_vars'][$k])) {
                unset($_SESSION['__yp_vars'][$k]);
            }
        }
        if (empty($_SESSION['__yp_vars'])) {
            unset($_SESSION['__yp_vars']);
        }
    }

    /**
     * 检索已标记为临时数据的所有会话数据的键
     *
     * @return array
     */
    public function getTempKeys()
    {
        if (!isset($_SESSION['__yp_vars'])) {
            return [];
        }
        $keys = [];
        foreach (array_keys($_SESSION['__yp_vars']) as $key) {
            is_int($_SESSION['__yp_vars'][$key]) && $keys[] = $key;
        }

        return $keys;
    }

    /**
     * 设置驱动程序作为会话处理程序
     */
    protected function setSaveHandler()
    {
        session_set_save_handler($this->driver, true);
    }

    /**
     * 开启会话
     */
    protected function startSession()
    {
        session_start();
    }

    /**
     * 在客户端设置cookie
     */
    protected function setCookie()
    {
        setcookie($this->sessionCookieName, session_id(),
            (empty($this->sessionExpiration) ? 0 : time() + $this->sessionExpiration), $this->cookiePath,
            $this->cookieDomain, $this->cookieSecure, true);
    }
}