<?php
/**
 * User: yongli
 * Date: 17/5/3
 * Time: 17:32
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Session;

use YP\Config\BaseConfig;
use Psr\Log\LoggerAwareTrait;


/**
 * Class BaseHandler 会话处理的基类
 *
 * @package YP\Libraries\Session
 */
abstract class YP_BaseHandler implements \SessionHandlerInterface
{
    use LoggerAwareTrait;

    /**
     * 数据指纹
     *
     * @var bool
     */
    protected $fingerprint;

    /**
     * 锁的状态
     *
     * @var bool
     */
    protected $lock = false;

    /**
     * Cookie的前缀
     *
     * @var string
     */
    protected $cookiePrefix = '';

    /**
     * Cookie的主域名
     *
     * @var string
     */
    protected $cookieDomain = '';

    /**
     * Cookie存储的路径
     *
     * @var string
     */
    protected $cookiePath = '/';

    /**
     * Cookie 是否安全
     *
     * @var bool
     */
    protected $cookieSecure = false;

    /**
     * Cookie name to use
     * @var type
     */
    protected $cookieName;

    /**
     * 匹配Cookie的IP地址
     *
     * @var bool
     */
    protected $matchIP = false;

    /**
     * 当前的session的ID
     *
     * @var
     */
    protected $sessionID;

    /**
     * session的保存路径
     *
     * @var
     */
    protected $savePath;

    /**
     * YP_BaseHandler constructor.
     * 初始化配置
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->cookiePrefix = $config->cookiePrefix;
        $this->cookieDomain = $config->cookieDomain;
        $this->cookiePath   = $config->cookiePath;
        $this->cookieSecure = $config->cookieSecure;
        $this->cookieName   = $config->sessionCookieName;
        $this->matchIP      = $config->sessionMatchIP;
        $this->savePath     = $config->sessionSavePath;
    }
    
    /**
     * 销毁Cookie
     * 当调用session_destroy()进行销毁sessions时,调用该方法
     *
     * @return bool
     */
    protected function destroyCookie(): bool
    {
        return setcookie(
            $this->cookieName,
            null,
            1,
            $this->cookiePath,
            $this->cookieDomain,
            $this->cookieSecure,
            true
        );
    }

    /**
     * 这是虚拟的锁,除数据库(数据库有自带锁)
     *
     * @param string $sessionID sessionId
     *
     * @return bool
     */
    protected function lockSession(string $sessionID): bool
    {
        $this->lock = true;
        return true;
    }

    /**
     * 如果有锁,就释放锁
     *
     * @return bool
     */
    protected function releaseLock(): bool
    {
        $this->lock = false;

        return true;
    }

    /**
     * 失败时,记录session
     *
     * 除了files驱动外,其他不需要调用此方法
     *
     * @return bool
     */
    protected function fail()
    {
        ini_set('session.save_path', $this->savePath);

        return false;
    }
}