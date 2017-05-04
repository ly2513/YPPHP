<?php
/**
 * User: yongli
 * Date: 17/5/3
 * Time: 17:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Session;

use YP\Config\Config;

class YP_FileHandler extends YP_BaseHandler implements \SessionHandlerInterface
{
    /**
     * 保存session文件的路径
     *
     * @var string
     */
    protected $savePath;

    /**
     * 文件处理程序对象
     *
     * @var
     */
    protected $fileHandle;

    /**
     * 文件名称
     *
     * @var
     */
    protected $filePath;

    /**
     * 是否为新的文件
     *
     * @var
     */
    protected $fileNew;

    /**
     * YP_FileHandler constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        if (!empty($config->sessionSavePath)) {
            $this->savePath = rtrim($config->sessionSavePath, '/\\');
            ini_set('session.save_path', $config->sessionSavePath);
        } else {
            $this->savePath = rtrim(ini_get('session.save_path'), '/\\');
        }
    }

    /**
     * 初始化配置
     *
     * @param string $savePath session文件的目录
     * @param string $name     session cookie的名称
     *
     * @return bool
     * @throws \Exception
     */
    public function open($savePath, $name): bool
    {
        if (!is_dir($savePath)) {
            if (!mkdir($savePath, 0700, true)) {
                throw new \Exception('Session: 配置的保存目录 "' . $this->savePath . '" 不是个目录或者目录不存在或者不能创建.');
            }
        } elseif (!is_writable($savePath)) {
            throw new \Exception('Session: 配置的保存目录 "' . $this->savePath . '", PHP程序不能写入.');
        }
        $this->savePath = $savePath;
        // 使用session cookie的名称作为前缀,避免冲突
        $this->filePath = $this->savePath . '/' . $name . ($this->matchIP ? md5($_SERVER['REMOTE_ADDR']) : '');

        return true;
    }

    /**
     * 读取Session
     *
     * @param string $sessionID sessionId
     *
     * @return bool|string
     */
    public function read($sessionID)
    {
        if ($this->fileHandle === null) {
            $this->fileNew = !file_exists($this->filePath . $sessionID);
            if (($this->fileHandle = fopen($this->filePath . $sessionID, 'c+b')) === false) {
                $this->logger->error('Session: 不能打开文件 "' . $this->filePath . $sessionID . '".');

                return false;
            }
            if (flock($this->fileHandle, LOCK_EX) === false) {
                $this->logger->error('Session: 不能获得文件锁 "' . $this->filePath . $sessionID . '."');
                fclose($this->fileHandle);
                $this->fileHandle = null;

                return false;
            }
            $this->sessionID = $sessionID;
            if ($this->fileNew) {
                chmod($this->filePath . $sessionID, 0600);
                $this->fingerprint = md5('');

                return '';
            }
        } else {
            rewind($this->fileHandle);
        }
        $session_data = '';
        for ($read = 0, $length = filesize($this->filePath . $sessionID); $read < $length; $read += strlen($buffer)) {
            if (($buffer = fread($this->fileHandle, $length - $read)) === false) {
                break;
            }
            $session_data .= $buffer;
        }
        $this->fingerprint = md5($session_data);

        return $session_data;
    }

    /**
     * 写(更新)session数据
     *
     * @param string $sessionID   sessionID
     * @param string $sessionData 序列化后的session数据
     *
     * @return bool
     */
    public function write($sessionID, $sessionData): bool
    {
        // 如果sessionID没匹配到,调用session_regenerate_id()重新生成一个
        if ($sessionID !== $this->sessionID && (!$this->close() || $this->read($sessionID) === false)) {
            return false;
        }
        if (!is_resource($this->fileHandle)) {
            return false;
        } elseif ($this->fingerprint === md5($sessionData)) {
            return ($this->fileNew) ? true : touch($this->filePath . $sessionID);
        }
        if (!$this->fileNew) {
            ftruncate($this->fileHandle, 0);
            rewind($this->fileHandle);
        }
        if (($length = strlen($sessionData)) > 0) {
            for ($written = 0; $written < $length; $written += $result) {
                if (($result = fwrite($this->fileHandle, substr($sessionData, $written))) === false) {
                    break;
                }
            }
            if (!is_int($result)) {
                $this->fingerprint = md5(substr($sessionData, 0, $written));
                $this->logger->error('Session: Unable to write data.');

                return false;
            }
        }
        $this->fingerprint = md5($sessionData);

        return true;
    }

    /**
     * 释放文件锁
     *
     * @return bool
     */
    public function close(): bool
    {
        if (is_resource($this->fileHandle)) {
            flock($this->fileHandle, LOCK_UN);
            fclose($this->fileHandle);
            $this->fileHandle = $this->fileNew = $this->sessionID = null;

            return true;
        }

        return true;
    }

    /**
     * 销毁session
     *
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id): bool
    {
        if ($this->close()) {
            return file_exists($this->filePath . $session_id) ? (unlink($this->filePath . $session_id) && $this->destroyCookie()) : true;
        } elseif ($this->filePath !== null) {
            clearstatcache();

            return file_exists($this->filePath . $session_id) ? (unlink($this->filePath . $session_id) && $this->destroyCookie()) : true;
        }

        return false;
    }

    /**
     * 垃圾回收器
     *
     * @param int $maxLifeTime session最大的生存时间
     *
     * @return bool
     */
    public function gc($maxLifeTime): bool
    {
        if (!is_dir($this->savePath) || ($directory = opendir($this->savePath)) === false) {
            $this->logger->debug("Session: 垃圾收集器无法在当前目录下列出文件 '" . $this->savePath . "'.");

            return false;
        }
        $ts      = time() - $maxLifeTime;
        $pattern = sprintf('/^%s[0-9a-f]{%d}$/', preg_quote($this->cookieName, '/'),
            ($this->matchIP === true ? 72 : 40));
        while (($file = readdir($directory)) !== false) {
            // 如果文件名与此模式不匹配，它不是会话文件
            $mTime  = filemtime($this->savePath . '/' . $file);
            $status = preg_match($pattern, $file) || !is_file($this->savePath . '/' . $file) || $mTime;
            if (!$status === false || $mTime > $ts) {
                continue;
            }
            unlink($this->savePath . '/' . $file);
        }
        closedir($directory);

        return true;
    }
}