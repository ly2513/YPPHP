<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 12:49
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

use YP\Config\Config;

class App extends Config
{
    /**
     * 设置基本的网址
     *
     * @var string
     */
    public $baseURL = '';

    /**
     * URI 协议
     * 可设置的值有: REQUEST_URI(使用$_SERVER['REQUEST_URI'])、
     * QUERY_STRING ($_SERVER['QUERY_STRING'])、
     * PATH_INFO ($_SERVER['PATH_INFO'])
     * 默认值为: REQUEST_URI
     * 假如设置的值为PATH_INFO, URIs将要一直被解密
     *
     * @var string
     */
    public $uriProtocol = 'REQUEST_URI';

    /**
     * 设置默认语言环境
     *
     * @var string
     */
    public $defaultLocale = 'en';

    public $negotiateLocale = false;

    /**
     * 支持的语言环境
     *
     * @var array
     */
    public $supportedLocales = ['en'];

    /**
     * 设置时区
     *
     * @var string
     */
    public $appTimezone = 'Asia/Shanghai';

    /**
     * 设置编码
     *
     * @var string
     */
    public $charset = 'UTF-8';

    /**
     * URL 协议
     * 如果为TRUE，这将强制每个请求该应用程序必须是通过安全连接（HTTPS）。
     * 如果传入请求不是安全，用户将被重定向到一个安全的网页的并且设置HTTP严格传输安全头
     *
     * @var bool
     */
    public $forceGlobalSecureRequests = false;

    /**
     * session 处理驱动设置
     * sessionDriver:    设置session存储的驱动,包含文件存储、数据库存储、redis存储, 肯能的值为下面三种
     * YP\Libraries\Session\YP_FileHandler
     * YP\Libraries\Session\YP_DatabaseHandler
     * YYP\Libraries\Session\YP_RedisHandler
     *
     * @var string
     */
    public $sessionDriver = 'YP\Libraries\Session\YP_FileHandler';

    /**
     * 设置session名称
     *
     * @var string
     */
    public $sessionCookieName = 'yp_session';

    /**
     * 设置session会话持续的秒数,设置为0,表示当浏览器关闭时会话才关闭
     *
     * @var int
     */
    public $sessionExpiration = 0;

    /**
     * 如果是文件驱动,该值为存储的session文件的路径(仅支持绝对路径), 建议配置的值: CACHE_PATH . '/Session'
     * 如果是数据库驱动,则该属性值为存储session的表名, 值随便,建议采用相同的表的前缀
     * 如果是redis驱动,则为主机和端口 如: tcp://127.0.0.1:6379
     *
     * @var string
     */
    public $sessionSavePath = CACHE_PATH . '/Session';

    /**
     * 读取session会话数据时是否匹配用户的IP地址,
     * 注意:如果你使用的数据库驱动程序，不要忘记更新session表的主键时更改此设置
     *
     * @var bool
     */
    public $sessionMatchIP = false;

    /**
     * 间隔多少秒再生成一次session ID
     *
     * @var int
     */
    public $sessionTimeToUpdate = 300;

    /**
     * 当自动再生成的session ID时,是否销毁与旧的session ID数据。设置false时,数据将由垃圾回收器回收后删除
     *
     * @var bool
     */
    public $sessionRegenerateDestroy = false;

    /**
     * cookie 相关的属性
     * cookiePrefix:   防止cookie碰撞,可以设置Cookie名称前缀
     * cookieDomain:   设置站内cookie
     * cookiePath:     cookie路径,默认值为'/'
     * cookieSecure:   如果存在安全的HTTPS连接, 只要设置这一个属性即可
     * cookieHTTPOnly: Cookie只访问通过http(s)
     * 注意: 这些设置也会影响到session(除了cookie_prefix,cookie_httponly这两个属性设置)
     *
     */
    public $cookiePrefix   = '';
    public $cookieDomain   = '';
    public $cookiePath     = '/';
    public $cookieSecure   = false;
    public $cookieHTTPOnly = false;

    /**
     * 反向代理服务器IPS
     * 如果你的getserver背后是一个反向代理，为了正确识别访问者的IP地址, 你必须白名单的代理IP地址
     * 由YP应该信任请求头如http_x_forwarded_for和http_client_ip
     * 该属性值设置方式: string: '10.0.1.200,192.168.5.0/24' 或 array('10.0.1.200', '192.168.5.0/24')
     *
     * @var string
     */
    public $proxyIPs = '';

    /**
     * 伪造请求,跨站进行攻击
     * 设置CSRF cookie token ,当设置为true时,token将检查提交的表单数据。
     * CSRFTokenName:  设置token名称
     * CSRFCookieName: 设置cookie名称
     * CSRFExpire:     令牌过期的秒数
     * CSRFRegenerate: 每次提交时生成令牌
     *
     * @var string
     */
    public $CSRFTokenName  = 'csrf_test_name';
    public $CSRFCookieName = 'csrf_cookie_name';
    public $CSRFExpire     = 7200;
    public $CSRFRegenerate = true;
    public $CSPEnabled     = false;

    /**
     * 调试工具栏
     * 调试工具栏提供了一种查看性能信息的方法
     * 这个页面显示在您的应用程序和状态。默认情况下
     * 不在生产环境中的显示，只显示如果
     * YP_DEBUG为true，因为如果不是，没有太多的表现吧。
     *
     * @var array
     */
    public $toolbarCollectors = [
        'YP\Debug\Toolbar\Collectors\Timers',
        'YP\Debug\Toolbar\Collectors\Database',
        'YP\Debug\Toolbar\Collectors\Logs',
        'YP\Debug\Toolbar\Collectors\Views',
        //		'YP\Debug\Toolbar\Collectors\Cache',
        'YP\Debug\Toolbar\Collectors\Files',
        'YP\Debug\Toolbar\Collectors\Routes',
    ];

    /**
     * 错误视图路径
     * 这是包含“CLI”和“HTML”的目录的路径。目录包含用于显示错误的信息。
     *
     * @var string
     */
    public $errorViewPath = APP_PATH . 'Views/Errors';

    /**
     * 加密秘钥
     * 如果使用加密类，则必须设置加密密钥
     *
     * @var string
     */
    public $encryptionKey = '';

    /**
     * 应用的数据加密盐值
     * 如果使用model类的hashedID方法,这个属性不能为空
     *
     * @var string
     */
    public $salt = '';

}