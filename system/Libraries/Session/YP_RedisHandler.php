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
     * @var
     */
    protected $lockKey;

    /**
     * key存在的标识
     *
     * @var bool
     */
    protected $keyExists = false;

    /**
     * session生存时间
     *
     * @var int
     */
    protected $sessionExpiration = 7200;

    /**
     * YP_RedisHandler constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        if (empty($this->savePath)) {
            throw new \Exception('Session: 没有配置redis持久化保存的目录.');
        } elseif (preg_match('#(?:tcp://)?([^:?]+)(?:\:(\d+))?(\?.+)?#', $this->savePath, $matches)) {
            P($this->savePath);
            P($matches);
            isset($matches[3]) OR $matches[3] = '';
            $this->savePath = [
                'host'     => $matches[1],
                'port'     => empty($matches[2]) ? null : $matches[2],
                'password' => preg_match('#auth=([^\s&]+)#', $matches[3], $match) ? $match[1] : null,
                'database' => preg_match('#database=(\d+)#', $matches[3], $match) ? (int)$match[1] : null,
                'timeout'  => preg_match('#timeout=(\d+\.\d+)#', $matches[3], $match) ? (float)$match[1] : null,
            ];
            preg_match('#prefix=([^\s&]+)#', $matches[3], $match) && $this->keyPrefix = $match[1];
        } else {
            throw new \Exception('Session: Invalid Redis save path format: ' . $this->savePath);
        }
        P($this->savePath);die;
        if ($this->matchIP === true) {
            $this->keyPrefix .= $_SERVER['REMOTE_ADDR'] . ':';
        }
        $this->sessionExpiration = $config->sessionExpiration;
    }

    //--------------------------------------------------------------------
    /**
     * Open
     *
     * Sanitizes save_path and initializes connection.
     *
     * @param    string $save_path Server path
     * @param    string $name      Session cookie name, unused
     *
     * @return    bool
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
            $this->logger->error('Session: Unable to select Redis database with index ' . $this->savePath['database']);
        } else {
            $this->redis = $redis;

            return true;
        }

        return false;
    }

    public function initRedis(){
        if (empty($this->savePath)) {
            return false;
        }
        $redis = new \Redis();
        if (!$redis->connect($this->savePath['host'], $this->savePath['port'], $this->savePath['timeout'])) {
            $this->logger->error('Session: 无法连接Redis');
        } elseif (isset($this->savePath['password']) && !$redis->auth($this->savePath['password'])) {
            $this->logger->error('Session: 无法验证到Redis实例.');
        } elseif (isset($this->savePath['database']) && !$redis->select($this->savePath['database'])) {
            $this->logger->error('Session: Unable to select Redis database with index ' . $this->savePath['database']);
        } else {
            $this->redis = $redis;

            return true;
        }
    }

    //--------------------------------------------------------------------
    /**
     * Read
     *
     * Reads session data and acquires a lock
     *
     * @param    string $sessionID Session ID
     *
     * @return    string    Serialized session data
     */
    public function read($sessionID)
    {
        if (isset($this->redis) && $this->lockSession($sessionID)) {
            // Needed by write() to detect session_regenerate_id() calls
            $this->sessionID = $sessionID;
            $session_data = $this->redis->get($this->keyPrefix . $sessionID);
            is_string($session_data) ? $this->keyExists = true : $session_data = '';
            $this->fingerprint = md5($session_data);

            return $session_data;
        }

        return false;
    }

    //--------------------------------------------------------------------
    /**
     * Write
     *
     * Writes (create / update) session data
     *
     * @param    string $sessionID   Session ID
     * @param    string $sessionData Serialized session data
     *
     * @return    bool
     */
    public function write($sessionID, $sessionData)
    {
        if (!isset($this->redis)) {
            return false;
        } // Was the ID regenerated?
        elseif ($sessionID !== $this->sessionID) {
            if (!$this->releaseLock() || !$this->lockSession($sessionID)) {
                return false;
            }
            $this->keyExists = false;
            $this->sessionID = $sessionID;
        }
        if (isset($this->lockKey)) {
            $this->redis->setTimeout($this->lockKey, 300);
            if ($this->fingerprint !== ($fingerprint = md5($sessionData)) || $this->keyExists === false) {
                if ($this->redis->set($this->keyPrefix . $sessionID, $sessionData, $this->sessionExpiration)) {
                    $this->fingerprint = $fingerprint;
                    $this->keyExists   = true;

                    return true;
                }

                return false;
            }

            return $this->redis->setTimeout($this->keyPrefix . $sessionID, $this->sessionExpiration);
        }

        return false;
    }

    //--------------------------------------------------------------------
    /**
     * Close
     *
     * Releases locks and closes connection.
     *
     * @return    bool
     */
    public function close()
    {
        if (isset($this->redis)) {
            try {
                if ($this->redis->ping() === '+PONG') {
                    isset($this->lockKey) && $this->redis->delete($this->lockKey);
                    if (!$this->redis->close()) {
                        return false;
                    }
                }
            } catch (\RedisException $e) {
                $this->logger->error('Session: Got RedisException on close(): ' . $e->getMessage());
            }
            $this->redis = null;

            return true;
        }

        return true;
    }

    //--------------------------------------------------------------------
    /**
     * Destroy
     *
     * Destroys the current session.
     *
     * @param    string $session_id Session ID
     *
     * @return    bool
     */
    public function destroy($sessionID)
    {
        if (isset($this->redis, $this->lockKey)) {
            if (($result = $this->redis->delete($this->keyPrefix . $sessionID)) !== 1) {
                $this->logger->debug('Session: Redis::delete() expected to return 1, got ' . var_export($result,
                        true) . ' instead.');
            }

            return $this->destroyCookie();
        }

        return false;
    }

    //--------------------------------------------------------------------
    /**
     * Garbage Collector
     *
     * Deletes expired sessions
     *
     * @param    int $maxlifetime Maximum lifetime of sessions
     *
     * @return    bool
     */
    public function gc($maxlifetime)
    {
        // Not necessary, Redis takes care of that.
        return true;
    }

    //--------------------------------------------------------------------
    /**
     * Get lock
     *
     * Acquires an (emulated) lock.
     *
     * @param    string $sessionID Session ID
     *
     * @return    bool
     */
    protected function lockSession(string $sessionID): bool
    {
        // PHP 7 reuses the SessionHandler object on regeneration,
        // so we need to check here if the lock key is for the
        // correct session ID.
        if ($this->lockKey === $this->keyPrefix . $sessionID . ':lock') {
            return $this->redis->setTimeout($this->lockKey, 300);
        }
        // 30 attempts to obtain a lock, in case another request already has it
        $lock_key = $this->keyPrefix . $sessionID . ':lock';
        $attempt  = 0;
        do {
            if (($ttl = $this->redis->ttl($lock_key)) > 0) {
                sleep(1);
                continue;
            }
            if (!$this->redis->setex($lock_key, 300, time())) {
                $this->logger->error('Session: Error while trying to obtain lock for ' . $this->keyPrefix . $sessionID);

                return false;
            }
            $this->lockKey = $lock_key;
            break;
        } while (++$attempt < 30);
        if ($attempt === 30) {
            log_message('error',
                'Session: Unable to obtain lock for ' . $this->keyPrefix . $sessionID . ' after 30 attempts, aborting.');

            return false;
        } elseif ($ttl === -1) {
            log_message('debug', 'Session: Lock for ' . $this->keyPrefix . $sessionID . ' had no TTL, overriding.');
        }
        $this->lock = true;

        return true;
    }

    //--------------------------------------------------------------------
    /**
     * Release lock
     *
     * Releases a previously acquired lock
     *
     * @return    bool
     */
    protected function releaseLock(): bool
    {
        if (isset($this->redis, $this->lockKey) && $this->lock) {
            if (!$this->redis->delete($this->lockKey)) {
                $this->logger->error('Session: Error while trying to free lock for ' . $this->lockKey);

                return false;
            }
            $this->lockKey = null;
            $this->lock    = false;
        }

        return true;
    }

    //--------------------------------------------------------------------
}
