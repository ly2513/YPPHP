<?php
/**
 * User: yongli
 * Date: 17/5/3
 * Time: 17:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Session;

use Illuminate\Database\Capsule\Manager as Capsule;
use Config\Services;
use YP\Config\Config;

/**
 * 使用数据作为session的驱动程序
 *
 * Class YP_DatabaseHandler
 *
 * @package YP\Libraries\Session
 */
class YP_DatabaseHandler extends YP_BaseHandler implements \SessionHandlerInterface
{
    /**
     * 用于存储的数据库组
     *
     * @var string
     */
    protected $DBGroup;

    /**
     * 存储session的表名
     *
     * @var
     */
    protected $table;

    /**
     * 操作数据库对象
     *
     * @var null
     */
    protected $db = null;

    /**
     * The database type, for locking purposes.
     *
     * @var string
     */
    protected $platform;

    /**
     * Row exists flag
     *
     * @var bool
     */
    protected $rowExists = false;

    /**
     * Capsule对象
     *
     * @var Capsule|null
     */
    protected $capsule = null;

    /**
     * YP_DatabaseHandler constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        // session表名
        $this->table = $config->sessionSavePath;
        if (empty($this->table)) {
            throw new \BadMethodCallException('Session: 存储session的表名没设置,必须设置`sessionSavePath`值,它是存储session的表名.');
        }
        $this->capsule = new Capsule();
        $this->initDB();
    }

    /**
     * open
     *
     * @param string $savePath
     * @param string $name
     *
     * @return bool
     */
    public function open($savePath, $name): bool
    {
        return true;

    }

    /**
     * 初始化数据
     *
     * @return bool
     */
    protected function initDB()
    {
        $db = Services::database();
        //        $db = new Database();
        $db = $db->getDB();
        if (isset($db['default'])) {
            $this->capsule->addConnection($db['default'], 'default');
            // 设置全局访问的连接
            $this->capsule->setAsGlobal();
            $this->capsule->bootEloquent();
            // 确定数据库类型
            if ($db['default']['driver'] == 'mysql') {
                $this->platform = 'mysql';
            } elseif ($db['default']['driver'] == 'postgre') {
                $this->platform = 'postgre';
            }
            $this->createTable();

            return true;
        }
    }

    /**
     * 创建session表
     */
    protected function createTable()
    {
        // 如果表存在,就不创建
        if (!Capsule::schema()->hasTable($this->table)) {
            Capsule::schema()->create($this->table, function ($table) {
                $table->increments('id');
                $table->string('session_id')->unique();
                $table->string('ip_address', 20);
                $table->text('data');
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }
    }

    /**
     * 根据sessionID 读取session
     *
     * @param string $sessionID
     *
     * @return bool|string session 数据
     */
    public function read($sessionID)
    {
        if ($this->lockSession($sessionID) == false) {
            $this->fingerprint = md5('');

            return '';
        }
        // sessionID
        $this->sessionID = $sessionID;
        // 构建查询器
        $builder = Capsule::table($this->table)->where('session_id', $sessionID);
        // 是否匹配IP
        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $_SERVER['REMOTE_ADDR']);
        }
        // 记录不存在时
        if ($result = $builder->first() === null) {
            $this->rowExists   = false;
            $this->fingerprint = md5('');

            return '';
        }
        // 一个BLOB数据类型在PostgreSQL数据库中的变体是bytea，所以我们使用Base64编码数据在文本字段代替
        if (is_bool($result)) {
            $result = '';
        } else {
            $result = ($this->platform === 'postgre') ? base64_decode(rtrim($result->data)) : $result->data;
        }
        $this->fingerprint = md5($result);
        $this->rowExists   = true;

        return $result;
    }

    /**
     * 记录session
     *
     * @param string $sessionID   sessionID
     * @param string $sessionData 序列化后的session数据
     *
     * @return bool
     */
    public function write($sessionID, $sessionData): bool
    {
        $builder = Capsule::table($this->table);
        if ($this->lock === false) {
            return $this->fail();
        } elseif ($sessionID !== $this->sessionID) { // 是否再生成sessionID
            if (!$this->releaseLock() || !$this->lockSession($sessionID)) {
                return $this->fail();
            }
            $this->rowExists = false;
            $this->sessionID = $sessionID;
        }
        if ($this->rowExists === false) {
            $insertData = [
                'session_id' => $sessionID,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => time(),
                'updated_at' => time(),
                'data'       => $this->platform === 'postgre' ? base64_encode($sessionData) : $sessionData
            ];
            if (!$builder->insert($insertData)) {
                return $this->fail();
            }
            $this->fingerprint = md5($sessionData);
            $this->rowExists   = true;

            return true;
        }
        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $_SERVER['REMOTE_ADDR']);
        }
        $updateData = [
            'updated_at' => time()
        ];
        if ($this->fingerprint !== md5($sessionData)) {
            $updateData['data'] = ($this->platform === 'postgre') ? base64_encode($sessionData) : $sessionData;
        }
        if (!$builder->update($updateData)) {
            return $this->fail();
        }
        $this->fingerprint = md5($sessionData);

        return true;
    }

    /**
     * 关闭锁
     *
     * @return bool
     */
    public function close(): bool
    {
        return ($this->lock && !$this->releaseLock()) ? $this->fail() : true;
    }

    /**
     * 销毁当前的session
     *
     * @param string $sessionID
     *
     * @return bool
     */
    public function destroy($sessionID): bool
    {
        if ($this->lock) {
            $builder = Capsule::table($this->table)->where('session_id', $sessionID);
            if ($this->matchIP) {
                $builder = $builder->where('ip_address', $_SERVER['REMOTE_ADDR']);
            }
            if (!$builder->delete()) {
                return $this->fail();
            }
        }
        if ($this->close()) {
            $this->destroyCookie();

            return true;
        }

        return $this->fail();
    }

    /**
     * 垃圾回收器
     *
     * @param int $maxLifeTime session的最大生存时间
     *
     * @return bool
     */
    public function gc($maxLifeTime): bool
    {
        return (Capsule::table($this->table)->delete('timestamp < ' . (time() - $maxLifeTime))) ? true : $this->fail();
    }

    /**
     * 锁住session
     *
     * @param string $sessionID
     *
     * @return bool
     */
    protected function lockSession(string $sessionID): bool
    {
        if ($this->platform === 'mysql') {
            $arg      = md5($sessionID . ($this->matchIP ? '_' . $_SERVER['REMOTE_ADDR'] : ''));
            $status   = Capsule::select('SELECT GET_LOCK("' . $arg . '", 300) AS yp_session_lock');
            $querySql = $status[0]->yp_session_lock;
            if ($querySql) {
                $this->lock = $arg;

                return true;
            }

            return $this->fail();
        } elseif ($this->platform === 'postgre') {
            $arg      = "hashtext('{$sessionID}')" . ($this->matchIP ? ", hashtext('{$_SERVER['REMOTE_ADDR']}')" : '');
            $querySql = Capsule::select('SELECT pg_advisory_lock(' . $arg . ')');
            if ($querySql) {
                $this->lock = $arg;

                return true;
            }

            return $this->fail();
        }

        return parent::lockSession($sessionID);
    }

    /**
     * 释放锁
     *
     * @return bool
     */
    protected function releaseLock(): bool
    {
        if (!$this->lock) {
            return true;
        }
        if ($this->platform === 'mysql') {
            $status   = Capsule::select('SELECT RELEASE_LOCK("' . $this->lock . '") AS yp_session_lock');
            $querySql = $status[0]->yp_session_lock;
            if ($querySql) {
                $this->lock = false;

                return true;
            }

            return $this->fail();
        } elseif ($this->platform === 'postgre') {
            $status   = Capsule::select('SELECT pg_advisory_unlock' . $this->lock . ')');
            $querySql = $status[0]->yp_session_lock;
            if ($querySql) {
                $this->lock = false;

                return true;
            }

            return $this->fail();
        }

        return parent::releaseLock();
    }
}