<?php
/**
 * User: yongli
 * Date: 17/5/3
 * Time: 17:32
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Session;

use YP\Config\Config;
use YP\Config\Services;

/**
 * session redis 处理类
 *
 * Class YP_RedisHandler
 *
 * @package YP\Libraries\Session
 */
class YP_RedisHandler extends YP_BaseHandler implements \SessionHandlerInterface
{
    /**
     * redis 对象
     *
     * @var
     */
    protected $redis;

    /**
     * session前缀
     *
     * @var string
     */
    protected $keyPrefix = 'yp_session:';

    /**
     * 锁的key
     *
     * @var string
     */
    protected $lockKey = '';

    /**
     * key存在的标识
     *
     * @var bool
     */
    protected $keyExists = true;

    /**
     * session生存时间
     *
     * @var int
     */
    protected $sessionExpiration = 7200;

    /**
     * session id 重新生成时间
     *
     * @var int
     */
    protected $sessionTimeToUpdate = 300;

    /**
     * YP_RedisHandler constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        if (empty($this->savePath)) {
            throw new \Exception('Session: 没有配置redis.');
        } elseif (preg_match('#(?:tcp://)?([^:?]+)(?:\:(\d+))?(\?.+)?#', $this->savePath, $matches)) {
            isset($matches[3]) or $matches[3] = '';
            $this->savePath = [
                'host'     => $matches[1],
                'port'     => empty($matches[2]) ? null : $matches[2],
                'password' => preg_match('#auth=([^\s&]+)#', $matches[3], $match) ? $match[1] : null,
                'database' => preg_match('#database=(\d+)#', $matches[3], $match) ? (int)$match[1] : null,
                'timeout'  => preg_match('#timeout=(\d+\.\d+)#', $matches[3], $match) ? (float)$match[1] : null,
            ];
            preg_match('#prefix=([^\s&]+)#', $matches[3], $match) && $this->keyPrefix = $match[1];
        } else {
            throw new \Exception('Session: redis配置格式有问题: ' . $this->savePath);
        }
        if ($this->matchIP === true) {
            $this->keyPrefix .= $_SERVER['REMOTE_ADDR'] . ':';
        }
        $this->sessionExpiration = $config->sessionExpiration;
    }

    /**
     * 初始化redis连接
     *
     * @param string $save_path
     * @param string $name
     *
     * @return bool
     */
    public function open($save_path, $name)
    {
        if (empty($this->savePath)) {
            return false;
        }
        $redis = new \Redis();
        if (!$redis->connect($this->savePath['host'], $this->savePath['port'], $this->savePath['timeout'])) {
            $this->logger->error('Session: 无法连接Redis');
        } elseif (isset($this->savePath['password']) && !$redis->auth($this->savePath['password'])) {
            $this->logger->error('Session: 无法验证到Redis实例.');
        } elseif (isset($this->savePath['database']) && !$redis->select($this->savePath['database'])) {
            $this->logger->error('Session: 无法选择redis数据库索引' . $this->savePath['database']);
        } else {
            $this->redis = $redis;

            return true;
        }

        return false;
    }

    /**
     * 读取session数据
     *
     * @param string $sessionID sessionID
     *
     * @return string 序列化的session数据
     */
    public function read($sessionID)
    {
        if (isset($this->redis)) {
            $this->sessionID = $sessionID;
            $session_data    = $this->redis->get($this->keyPrefix . $sessionID);
            //
            is_string($session_data) ? ($this->keyExists = true) : $session_data = '';
            $this->fingerprint = md5($session_data);

            return $session_data;
        }

        return '';
    }

    /**
     * 添加(更新)session数据
     *
     * @param string $sessionID   sessionID
     * @param string $sessionData 序列化的session数据
     *
     * @return bool
     */
    public function write($sessionID, $sessionData)
    {
        if (!isset($this->redis)) {
            return false;
        } elseif ($sessionID !== $this->sessionID) {// 重新生成sessionID
            $this->keyExists = false;
            $this->sessionID = $sessionID;
        }
        if ($this->fingerprint !== ($fingerprint = md5($sessionData)) || $this->keyExists) {
            if ($this->redis->set($this->keyPrefix . $sessionID, $sessionData, $this->sessionExpiration)) {
                $this->fingerprint = $fingerprint;
                $this->keyExists   = true;

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * 销毁session
     *
     * @param string $sessionID 当前的sessionID
     *
     * @return bool
     */
    public function destroy($sessionID)
    {
        if (isset($this->redis)) {
            if (($result = $this->redis->delete($this->keyPrefix . $sessionID)) !== 1) {
                $this->logger->debug('Session: 正常删除返回1,然而现在返回 ' . var_export($result, true) . ' .');
            }

            return $this->destroyCookie();
        }

        return false;
    }

    /**
     * 垃圾回收器
     *
     * @param int $maxLifeTime session 最大的生存时间
     *
     * @return bool
     */
    public function gc($maxLifeTime)
    {
        return true;
    }

    /**
     * 关闭连接,释放锁
     *
     * @return bool
     */
    public function close()
    {
        if (isset($this->redis)) {
            try {
                if ($this->redis->ping() === '+PONG') {
                    if (!$this->redis->close()) {
                        return false;
                    }
                }
            } catch (\RedisException $e) {
                $this->logger->error('Session: redis关闭出现异常: ' . $e->getMessage());
            }
            $this->redis = null;

            return true;
        }

        return true;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        session_write_close();
    }
}
