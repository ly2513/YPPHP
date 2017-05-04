<?php
/**
 * User: yongli
 * Date: 17/5/3
 * Time: 17:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Session;

use Illuminate\Database\Eloquent\Model as Model;

class DB extends Model
{

    protected $table = '';

//    public function __construct(array $attributes = [])
//    {
//        parent::__construct([]);
//        $this->table = isset($attributes['table']) && !empty($attributes['table']) ? $attributes['table'] : '';
//    }

}
use YP\Config\Config;
use YP\Libraries\Session\DB;

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
            throw new \BadMethodCallException('存储session的表名没设置,必须设置`sessionSavePath`值,它是存储session的表名.');
        }
        P(DB::class);
        // Determine Database type
        $driver = strtolower(get_class($this->db));
        if (strpos($driver, 'mysql') !== false) {
            $this->platform = 'mysql';
        } elseif (strpos($driver, 'postgre') !== false) {
            $this->platform = 'postgre';
        }
    }

    //--------------------------------------------------------------------
    /**
     * Open
     *
     * Ensures we have an initialized database connection.
     *
     * @param    string $savePath Path to session files' directory
     * @param    string $name     Session cookie name
     *
     * @return bool
     * @throws \Exception
     */
    public function open($savePath, $name): bool
    {
        if (empty($this->db->connID)) {
            $this->db->initialize();
        }

        return true;
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
        if ($this->lockSession($sessionID) == false) {
            $this->fingerprint = md5('');

            return '';
        }
        // Needed by write() to detect session_regenerate_id() calls
        $this->sessionID = $sessionID;
        $builder         = $this->db->table($this->table)->select('data')->where('id', $sessionID);
        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $_SERVER['REMOTE_ADDR']);
        }
        if ($result = $builder->get()->getRow() === null) {
            // PHP7 will reuse the same SessionHandler object after
            // ID regeneration, so we need to explicitly set this to
            // FALSE instead of relying on the default ...
            $this->rowExists   = false;
            $this->fingerprint = md5('');

            return '';
        }
        // PostgreSQL's variant of a BLOB datatype is Bytea, which is a
        // PITA to work with, so we use base64-encoded data in a TEXT
        // field instead.
        if (is_bool($result)) {
            $result = '';
        } else {
            $result = ($this->platform === 'postgre') ? base64_decode(rtrim($result->data)) : $result->data;
        }
        $this->fingerprint = md5($result);
        $this->rowExists   = true;

        return $result;
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
    public function write($sessionID, $sessionData): bool
    {
        if ($this->lock === false) {
            return $this->fail();
        } // Was the ID regenerated?
        elseif ($sessionID !== $this->sessionID) {
            if (!$this->releaseLock() || !$this->lockSession($sessionID)) {
                return $this->fail();
            }
            $this->rowExists = false;
            $this->sessionID = $sessionID;
        }
        if ($this->rowExists === false) {
            $insertData = [
                'id'         => $sessionID,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'timestamp'  => time(),
                'data'       => $this->platform === 'postgre' ? base64_encode($sessionData) : $sessionData
            ];
            if (!$this->db->table($this->table)->insert($insertData)) {
                return $this->fail();
            }
            $this->fingerprint = md5($sessionData);
            $this->rowExists   = true;

            return true;
        }
        $builder = $this->db->table($this->table);
        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $_SERVER['REMOTE_ADDR']);
        }
        $updateData = [
            'timestamp' => time()
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

    //--------------------------------------------------------------------
    /**
     * Close
     *
     * Releases locks and closes file descriptor.
     *
     * @return    bool
     */
    public function close(): bool
    {
        return ($this->lock && !$this->releaseLock()) ? $this->fail() : true;
    }

    //--------------------------------------------------------------------
    /**
     * Destroy
     *
     * Destroys the current session.
     *
     * @param string $sessionID
     *
     * @return bool
     */
    public function destroy($sessionID): bool
    {
        if ($this->lock) {
            $builder = $this->db->table($this->table)->where('id', $sessionID);
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
    public function gc($maxlifetime): bool
    {
        return ($this->db->table($this->table)->delete('timestamp < ' . (time() - $maxlifetime))) ? true : $this->fail();
    }

    //--------------------------------------------------------------------
    protected function lockSession(string $sessionID): bool
    {
        if ($this->platform === 'mysql') {
            $arg = md5($sessionID . ($this->matchIP ? '_' . $_SERVER['REMOTE_ADDR'] : ''));
            if ($this->db->query("SELECT GET_LOCK('{$arg}', 300) AS ci_session_lock")->getRow()->ci_session_lock) {
                $this->lock = $arg;

                return true;
            }

            return $this->fail();
        } elseif ($this->platform === 'postgre') {
            $arg = "hashtext('{$sessionID}')" . ($this->matchIP ? ", hashtext('{$_SERVER['REMOTE_ADDR']}')" : '');
            if ($this->db->simpleQuery("SELECT pg_advisory_lock({$arg})")) {
                $this->lock = $arg;

                return true;
            }

            return $this->fail();
        }

        // Unsupported DB? Let the parent handle the simplified version.
        return parent::lockSession($sessionID);
    }

    //--------------------------------------------------------------------
    /**
     * Releases the lock, if any.
     *
     * @return bool
     */
    protected function releaseLock(): bool
    {
        if (!$this->lock) {
            return true;
        }
        if ($this->platform === 'mysql') {
            if ($this->db->query("SELECT RELEASE_LOCK('{$this->lock}') AS ci_session_lock")->getRow()->ci_session_lock) {
                $this->lock = false;

                return true;
            }

            return $this->fail();
        } elseif ($this->platform === 'postgre') {
            if ($this->db->simpleQuery("SELECT pg_advisory_unlock({$this->lock})")) {
                $this->lock = false;

                return true;
            }

            return $this->fail();
        }

        // Unsupported DB? Let the parent handle the simple version.
        return parent::releaseLock();
    }

    //--------------------------------------------------------------------
}