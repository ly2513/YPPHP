<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 23:03
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Cache;

class YP_Redis
{
    private static $_db       = 0;
    private static $_master   = 0;
    private static $_multi    = 0;
    private static $_prefix   = "wj_";
    private static $_redis_w;
    private static $_redis_r;
    private        $_arrayKey = 'default';
    /**
     * redis 配置
     *
     * @var array
     */
    protected $config = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
    ];

    /**
     * YP_Redis constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        // 初始化redis配置
        if (isset($config->redis)) {
            $this->config = array_merge($this->config, $config->redis);
        }
        P($this->config );
        if (!extension_loaded('redis')) {
            throw new \Exception('redis failed to load');
        }
    }

    /**
     * 获取redis对象
     *
     * @param $options 操作方法
     *
     * @return \Redis
     * @throws \Exception
     */
    public function getRedis($options)
    {
        if (!isset($options["host"]) || !isset($options["port"]) || !isset($options["persistent"])) {
            throw new \Exception('Unexpected inconsistency in options');
        }
        // redis前缀
        if (isset($options['prefix'])) {
            self::$_prefix = $options['prefix'];
        }
        $redis = new \Redis();
        if ($options["persistent"]) {
            $success = $redis->pconnect($options['host'], intval($options['port']));
        } else {
            $success = $redis->connect($options['host'], intval($options['port']));
        }
        if (!$success) {
            throw new \Exception("Could not connect to the Redisd server " . $options["host"] . ":" . $options["port"]);
        }
        // redis权限认证
        if (isset($options["auth"])) {
            $success = $redis->auth($options["auth"]);
            if (!$success) {
                throw new \Exception("Failed to authenticate with the Redisd server");
            }
        }
        // 选择redis数据库
        if (isset($options["index"])) {
            $redis->select(intval($options["index"]));
        } else {
            $redis->select(self::$_db);
        }

        return $redis;
    }

    /**
     * 获得redis写对象
     * @return Redis
     * @throws Exception
     */
    public function getWriteRedis()
    {
        if (!is_object(self::$_redis_w)) {
            self::$_redis_w = $this->getRedis($this->config);
        }

        return self::$_redis_w;
    }

    /**
     * 获得redis读对象
     * @return Redis
     * @throws Exception
     */
    public function getReadRedis()
    {
        if (self::$_multi == 1) {
            return $this->getWriteRedis();
        }
        if (self::$_master == 1) {
            return $this->getWriteRedis();
        }
        if (!is_object(self::$_redis_r)) {
            if (array_key_exists('cacheSlave', $this->_options)) {
                $options = $this->_options['cacheSlave'];
            }
            $options[] = $this->_options['cache'];
            $arr       = [];
            foreach ($options as $i => $option) {
                $arr[] = isset($option['weight']) ? intval($option['weight']) : 1;
            }
            self::$_redis_r = $this->getRedis($options[get_rand($arr)]);
        }

        return self::$_redis_r;
    }

    /**
     * get命令
     *
     * @param $key
     *
     * @return bool|string
     */
    public function get($key)
    {
        return $this->getReadRedis()->get($this->_key($key));
    }

    /**
     * set命令
     *
     * @param     $key
     * @param     $value
     * @param int $period
     *
     * @return bool
     */
    public function set($key, $value, $period = 0)
    {
        $isTrue = $this->getWriteRedis()->set($this->_key($key), $value);
        if (intval($period)) {
            $this->getWriteRedis()->expire($this->_key($key), intval($period));
        }

        return $isTrue;
    }

    /**
     * 获得所有的keys
     *
     * @param $key
     *
     * @return mixed
     */
    public function keys($key)
    {
        return $this->getWriteRedis()->keys($this->_key($key));
    }

    /**
     * 删除key
     *
     * @param $key
     */
    public function deleteLike($key)
    {
        if (self::$_multi == 1) {
            return;
        }
        $keys = $this->getWriteRedis()->keys($this->_key($key));
        if (!empty($keys)) {
            $this->getWriteRedis()->delete($keys);
        }
    }

    /**
     * 删除key
     *
     * @param        $key
     * @param string $id
     */
    public function delete($key, $id = '')
    {
        if (is_array($id)) {
            $keys = array_map([$this, '_keys'], $key, $id);
        } else {
            $keys = $this->_key($key, $id);
        }
        $this->getWriteRedis()->delete($keys);
    }

    /**
     * 批量添加哈希TABLE值
     *
     * @param     $key  表名
     * @param     $id   标识ID
     * @param     $data 添加的数据
     * @param int $period
     *
     * @return bool
     */
    public function hmset($key, $id, $data, $period = 0)
    {
        $isTrue = $this->getWriteRedis()->hMset($this->_key($key, $id), $data);
        if (intval($period)) {
            $this->getWriteRedis()->expire($this->_key($key, $id), intval($period));
        }

        return $isTrue;
    }

    /**
     * 读取哈希table值
     *
     * @param $key
     * @param $id
     * @param $fields
     *
     * @return array
     */
    public function hmget($key, $id, $fields)
    {
        $fields   = is_array($fields) ? $fields : explode(',', $fields);
        $fields[] = 'redisFlag';
        $rs       = $this->getReadRedis()->hmGet($this->_key($key, $id), $fields);

        return is_array($rs) && $rs['redisFlag'] === false ? [] : $rs;
    }

    /**
     * 删除哈希table值
     *
     * @param $key
     * @param $id
     * @param $fields
     *
     * @return int
     */
    public function hdel($key, $id, $fields)
    {
        return $this->getWriteRedis()->hdel($this->_key($key, $id), $fields);
    }

    public function hIncrBy($key, $id, $field, $num = 1)
    {
        return $this->getWriteRedis()->hIncrBy($this->_key($key, $id), $field, intval($num));
    }

    public function hIncrByFloat($key, $id, $field, $num = 1)
    {
        return $this->getWriteRedis()->hIncrByFloat($this->_key($key, $id), $field, floatval($num));
    }

    public function incrBy($key, $num = 1)
    {
        return $this->getWriteRedis()->incrBy($this->_key($key), intval($num));
    }

    public function decrBy($key, $num = 1)
    {
        return $this->getWriteRedis()->decrBy($this->_key($key), $num);
    }

    /**
     * 获得某个key的长度
     *
     * @param $key
     * @param $id
     *
     * @return mixed
     */
    public function llen($key, $id)
    {
        return $this->getReadRedis()->llen($this->_key($key, $id));
    }

    public function lrange($key, $id, $start, $stop)
    {
        return $this->getReadRedis()->lrange($this->_key($key, $id), intval($start), intval($stop));
    }

    public function lrem($key, $id, $count, $value)
    {
        return $this->getReadRedis()->lrem($this->_key($key, $id), intval($count), $value);
    }

    public function rpush($key, $id, $arr = [], $period = 0)
    {
        $redis = $this->getWriteRedis();
        foreach ($arr as $v) {
            $redis->rpush($this->_key($key, $id), $v);
        }
        if (intval($period)) {
            $redis->expire($this->_key($key, $id), intval($period));
        }

        return true;
    }

    public function sadd($key, $id, $arr = [], $period = 0)
    {
        $redis = $this->getWriteRedis();
        foreach ($arr as $v) {
            $redis->sadd($this->_key($key, $id), $v);
        }
        if (intval($period)) {
            $redis->expire($this->_key($key, $id), intval($period));
        }

        return true;
    }

    public function lpush($key, $id, $arr = [], $period = 0)
    {
        $redis = $this->getWriteRedis();
        foreach ($arr as $v) {
            $redis->lpush($this->_key($key, $id), $v);
        }
        if (intval($period)) {
            $redis->expire($this->_key($key, $id), intval($period));
        }

        return true;
    }

    public function ltrim($key, $id, $start, $stop)
    {
        return $this->getWriteRedis()->ltrim($this->_key($key, $id), intval($start), intval($stop));
    }

    public function smembers($key, $id)
    {
        return $this->getReadRedis()->sMembers($this->_key($key, $id));
    }

    public function clear()
    {
        return $this->getWriteRedis()->flushDB();
    }

    public function pipeline($rw = 'r')
    {
        if ($rw == 'w') {
            self::$_multi = 1;

            return $this->getWriteRedis()->multi(\Redis::PIPELINE);
        } else {
            self::$_multi = 0;

            return $this->getReadRedis()->multi(\Redis::PIPELINE);
        }
    }

    public function multi()
    {
        self::$_multi = 1;

        return $this->getWriteRedis()->multi(\Redis::MULTI);
    }

    public function exec()
    {
        if (self::$_multi == 1) {
            $rs = $this->getWriteRedis()->exec();
        } else {
            $rs = $this->getReadRedis()->exec();
        }
        self::$_multi = 0;

        return $rs;
    }

    public function master()
    {
        self::$_master = 1;
    }

    private function _key($key, $id = '')
    {
        return self::$_prefix . $key . (($id !== '') ? (':' . $id) : '');
    }
}