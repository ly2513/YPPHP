<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 23:03
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Cache;

/**
 * redis缓存处理
 *
 * Class YP_Redis
 *
 * @package YP\Libraries\Cache
 */
class YP_Redis
{
    protected $_options = [];

    /**
     * redis数据库下标
     *
     * @var int
     */
    private static $_db = 0;

    /**
     * 主redis库
     *
     * @var int
     */
    private static $_master = 0;

    /**
     * 从redis库
     *
     * @var int
     */
    private static $_multi = 0;

    /**
     * redis前缀
     *
     * @var string
     */
    private static $_prefix = "wj_";
    /**
     * redis 写对象
     *
     * @var
     */
    private static $_redis_w;

    /**
     * redis 读对象
     *
     * @var
     */
    private static $_redis_r;

    /**
     * redis对象
     *
     * @var
     */
    public $redis;

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
     * @param \Config\Cache|null $config
     */
    public function __construct(\Config\Cache $config = null)
    {
        $redisConf = \Config\Cache::$redis;
        // redis前缀
        self::$_prefix = $config->prefix ? : $redisConf['prefix'];
        // 初始化redis配置
        if ($redisConf) {
            $this->config = array_merge($this->config, $redisConf);
        }
    }

    /**
     * 初始化redis
     *
     * @return \Redis
     * @throws \Exception
     */
    public function initialize()
    {
        if (!isset($this->config['host']) || !isset($this->config['port']) || !isset($this->config['persistent'])) {
            throw new \Exception('Unexpected inconsistency in options');
        }
        $redis = new \Redis();
        if ($this->config['persistent']) {
            $success = $redis->pconnect($this->config['host'], intval($this->config['port']));
        } else {
            $success = $redis->connect($this->config['host'], intval($this->config['port']));
        }
        if (!$success) {
            throw new \Exception('Could not connect to the Redis server ' . $this->config['host'] . ':' . $this->config['port']);
        }
        // redis权限认证
        if (isset($this->config['auth'])) {
            $success = $redis->auth($this->config['auth']);
            if (!$success) {
                throw new \Exception('Failed to authenticate with the Redis server');
            }
        }
        // 选择redis数据库
        if (isset($this->config['index'])) {
            $redis->select(intval($this->config['index']));
        } else {
            $redis->select(self::$_db);
        }
        $this->redis = $redis;

        return $this->redis;
    }

    /**
     * 获取redis对象
     *
     * @return mixed
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * 获得redis写对象
     *
     * @return Redis
     * @throws Exception
     */
    public function getWriteRedis()
    {
        if (!is_object(self::$_redis_w)) {
            self::$_redis_w = $this->getRedis();
        }

        return self::$_redis_w;
    }

    /**
     * 获得redis读对象
     *
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
            $options[] = $this->config;
            $arr       = [];
            foreach ($options as $i => $option) {
                $arr[] = isset($option['weight']) ? intval($option['weight']) : 1;
            }
            $this->config   = $options[get_rand($arr)];
            self::$_redis_r = $this->initialize();
        }

        return self::$_redis_r;
    }

    // ------------ string 命令 ------------
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
     * 将 key 所储存的值加上增量 increment
     *
     * @param     $key
     * @param int $num
     *
     * @return mixed
     */
    public function incrBy($key, $num = 1)
    {
        return $this->getWriteRedis()->incrBy($this->_key($key), intval($num));
    }

    /**
     * 将 key 所储存的值减去减量 decrement
     *
     * @param     $key
     * @param int $num
     *
     * @return mixed
     */
    public function decrBy($key, $num = 1)
    {
        return $this->getWriteRedis()->decrBy($this->_key($key), $num);
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

    // ------------ hash 命令 ------------
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

    /**
     * 为哈希表 key 中的域 field 的值加上增量 increment
     *
     * @param     $key
     * @param     $id
     * @param     $field
     * @param int $num
     *
     * @return mixed
     */
    public function hIncrBy($key, $id, $field, $num = 1)
    {
        return $this->getWriteRedis()->hIncrBy($this->_key($key, $id), $field, intval($num));
    }

    /**
     * 为哈希表 key 中的域 field 加上浮点数增量 increment
     *
     * @param     $key
     * @param     $id
     * @param     $field
     * @param int $num
     *
     * @return mixed
     */
    public function hIncrByFloat($key, $id, $field, $num = 1)
    {
        return $this->getWriteRedis()->hIncrByFloat($this->_key($key, $id), $field, floatval($num));
    }

    // ------------ list 命令 ------------
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

    /**
     * 返回列表 key 中指定区间内的元素，区间以偏移量 start 和 stop 指定
     *
     * @param $key
     * @param $id
     * @param $start
     * @param $stop
     *
     * @return mixed
     */
    public function lrange($key, $id, $start, $stop)
    {
        return $this->getReadRedis()->lrange($this->_key($key, $id), intval($start), intval($stop));
    }

    /**
     * 根据参数 count 的值，移除列表中与参数 value 相等的元素
     *
     * @param $key
     * @param $id
     * @param $count
     * @param $value
     *
     * @return mixed
     */
    public function lrem($key, $id, $count, $value)
    {
        return $this->getReadRedis()->lrem($this->_key($key, $id), intval($count), $value);
    }

    /**
     * 将一个或多个值 value 插入到列表 key 的表尾(最右边)
     *
     * @param       $key
     * @param       $id
     * @param array $arr
     * @param int   $period
     *
     * @return bool
     */
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

    /**
     * 将一个或多个值 value 插入到列表 key 的表头
     *
     * @param       $key
     * @param       $id
     * @param array $arr
     * @param int   $period
     *
     * @return bool
     */
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

    /**
     * 对一个列表进行修剪(trim)，就是说，让列表只保留指定区间内的元素，不在指定区间之内的元素都将被删除
     *
     * @param $key
     * @param $id
     * @param $start
     * @param $stop
     *
     * @return mixed
     */
    public function ltrim($key, $id, $start, $stop)
    {
        return $this->getWriteRedis()->ltrim($this->_key($key, $id), intval($start), intval($stop));
    }

    // ----------------- 有序集合 命令 ---------------
    /**
     * 将一个或多个 member 元素加入到集合 key 当中，已经存在于集合的 member 元素将被忽略
     *
     * @param       $key
     * @param       $id
     * @param array $arr
     * @param int   $period
     *
     * @return bool
     */
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

    /**
     * 返回集合 key 中的所有成员
     *
     * @param $key
     * @param $id
     *
     * @return mixed
     */
    public function smembers($key, $id)
    {
        return $this->getReadRedis()->sMembers($this->_key($key, $id));
    }

    /**
     * 清空当前数据库中的所有 key
     *
     * @return mixed
     */
    public function clear()
    {
        return $this->getWriteRedis()->flushDB();
    }

    /**
     * 标记一个事务块的开始
     *
     * @param string $rw
     *
     * @return mixed
     */
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

    /**
     * 标记一个事务块的开始
     *
     * @return mixed
     */
    public function multi()
    {
        self::$_multi = 1;

        return $this->getWriteRedis()->multi(\Redis::MULTI);
    }

    /**
     * 执行所有事务块内的命令
     *
     * @return mixed
     */
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

    /**
     * 设置master Redis
     */
    public function master()
    {
        self::$_master = 1;
    }

    /**
     * 设置key
     *
     * @param        $key
     * @param string $id
     *
     * @return string
     */
    private function _key($key, $id = '')
    {
        return self::$_prefix . $key . (($id !== '') ? (':' . $id) : '');
    }

    /**
     * 是否支持 redis
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return extension_loaded('redis');
    }
}
