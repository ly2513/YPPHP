<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 23:23
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\Cache;

class YP_File
{
    /**
     * Prefixed to all cache names.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Where to store cached files on the disk.
     *
     * @var string
     */
    protected $path;

    public function __construct($config)
    {
        $this->prefix = $config->prefix ? : '';
        $this->path   = !empty($config->path) ? $config->path : WRITEPATH . 'cache';
        $this->path   = rtrim($this->path, '/') . '/';
    }

    /**
     * 初始化file缓存,除file缓存驱动外,redis、memcached在此方法中初始化
     */
    public function initialize()
    {

    }

    /**
     * 获取某个缓存key的值
     *
     * @param string $key
     *
     * @return bool|mixed
     */
    public function get(string $key)
    {
        $key  = $this->prefix . $key;
        $data = $this->getItem($key);

        return is_array($data) ? $data['data'] : false;
    }

    /**
     * 保存缓存
     *
     * @param string $key   缓存键名
     * @param        $value 缓存值
     * @param int    $ttl   过期时间
     *
     * @return bool
     */
    public function save(string $key, $value, int $ttl = 60)
    {
        $key      = $this->prefix . $key;
        $contents = [
            'time' => time(),
            'ttl'  => $ttl,
            'data' => $value,
        ];
        if ($this->writeFile($this->path . $key, serialize($contents))) {
            chmod($this->path . $key, 0640);

            return true;
        }

        return false;
    }

    /**
     * 删除某个指定键的值
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key)
    {
        $key = $this->prefix . $key;

        return file_exists($this->path . $key) ? unlink($this->path . $key) : false;
    }

    /**
     * 递增一行的最小单位(列)存储值
     *
     * @param string $key 缓存ID
     * @param int    $offset 步长
     *
     * @return bool|int
     */
    public function increment(string $key, int $offset = 1)
    {
        $key  = $this->prefix . $key;
        $data = $this->getItem($key);
        if ($data === false) {
            $data = ['data' => 0, 'ttl' => 60];
        } elseif (!is_int($data['data'])) {
            return false;
        }
        $new_value = $data['data'] + $offset;

        return $this->save($key, $new_value, $data['ttl']) ? $new_value : false;
    }

    /**
     * 递减一行的最小单位(列)存储值
     *
     * @param string $key    缓存ID
     * @param int    $offset 步长
     *
     * @return mixed
     */
    public function decrement(string $key, int $offset = 1)
    {
        $key  = $this->prefix . $key;
        $data = $this->getItem($key);
        if ($data === false) {
            $data = ['data' => 0, 'ttl' => 60];
        } elseif (!is_int($data['data'])) {
            return false;
        }
        $new_value = $data['data'] - $offset;

        return $this->save($key, $new_value, $data['ttl']) ? $new_value : false;
    }

    /**
     * 清除缓存
     *
     * @return bool
     */
    public function clean()
    {
        return $this->deleteFiles($this->path, false, true);
    }

    /**
     * 获取缓存信息
     *
     * @return array
     */
    public function getCacheInfo()
    {
        return $this->getDirFileInfo($this->path);
    }

    /**
     * 返回缓存中特定项的详细信息
     *
     * @param string $key  缓存的键名
     *
     * @return array|bool
     */
    public function getMetaData(string $key)
    {
        $key = $this->prefix . $key;
        if (!file_exists($this->path . $key)) {
            return false;
        }
        $data = unserialize(file_get_contents($this->path . $key));
        if (is_array($data)) {
            $mtime = filemtime($this->path . $key);
            if (!isset($data['ttl'])) {
                return false;
            }

            return [
                'expire' => $mtime + $data['ttl'],
                'mtime'  => $mtime
            ];
        }

        return false;
    }

    /**
     * 检测文件是否可以写,其他处理缓存对象在此方法中会判断该驱动是否加载
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return is_writable($this->path);
    }

    /**
     * 是否重重检索文件并验证其存放时间
     *
     * @param string $key
     *
     * @return mixed|null
     */
    protected function getItem(string $key)
    {
        if (!is_file($this->path . $key)) {
            return null;
        }
        $data = unserialize(file_get_contents($this->path . $key));
        if ($data['ttl'] > 0 && time() > $data['time'] + $data['ttl']) {
            unlink($this->path . $key);

            return null;
        }

        return $data;
    }

    /**
     * 将缓存写入文件中 如果写入失败,返回FALSE
     *
     * @param        $path
     * @param        $data
     * @param string $mode
     *
     * @return bool
     */
    protected function writeFile($path, $data, $mode = 'wb')
    {
        if (!$fp = @fopen($path, $mode)) {
            return false;
        }
        flock($fp, LOCK_EX);
        for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($data, $written))) === false) {
                break;
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return is_int($result);
    }

    /**
     * 删除缓存文件
     *
     * @param      $path     文件目录
     * @param bool $del_dir  删除的目录
     * @param bool $htdocs   如果存在.htaccess and index page files 直接跳过
     * @param int  $_level   当前目录的深度
     *
     * @return bool
     */
    protected function deleteFiles($path, $del_dir = false, $htdocs = false, $_level = 0)
    {
        // Trim the trailing slash
        $path = rtrim($path, '/\\');
        if (!$current_dir = @opendir($path)) {
            return false;
        }
        while (false !== ($filename = @readdir($current_dir))) {
            if ($filename !== '.' && $filename !== '..') {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename) && $filename[0] !== '.') {
                    $this->deleteFiles($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $htdocs, $_level + 1);
                } elseif ($htdocs !== true || !preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i',
                        $filename)
                ) {
                    @unlink($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
        closedir($current_dir);

        return ($del_dir === true && $_level > 0) ? @rmdir($path) : true;
    }

    /**
     * 获得目录下的文件信息
     *
     * @param      $source_dir    原路径
     * @param bool $top_level_only 目录的深度
     * @param bool $_recursion
     *
     * @return array|bool
     */
    protected function getDirFileInfo($source_dir, $top_level_only = true, $_recursion = false)
    {
        static $_fileData = [];
        $relative_path = $source_dir;
        if ($fp = @opendir($source_dir)) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($_recursion === false) {
                $_fileData  = [];
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }
            // Used to be foreach (scandir($source_dir, 1) as $file), but scandir() is simply not as fast
            while (false !== ($file = readdir($fp))) {
                if (is_dir($source_dir . $file) && $file[0] !== '.' && $top_level_only === false) {
                    $this->getDirFileInfo($source_dir . $file . DIRECTORY_SEPARATOR, $top_level_only, true);
                } elseif ($file[0] !== '.') {
                    $_fileData[$file]                  = $this->getFileInfo($source_dir . $file);
                    $_fileData[$file]['relative_path'] = $relative_path;
                }
            }
            closedir($fp);

            return $_fileData;
        }

        return false;
    }

    /**
     * 获得文件信息
     *
     * @param       $file   缓存文件路径
     * @param array $returned_values
     *
     * @return bool
     */
    protected function getFileInfo($file, $returned_values = ['name', 'server_path', 'size', 'date'])
    {
        if (!file_exists($file)) {
            return false;
        }
        if (is_string($returned_values)) {
            $returned_values = explode(',', $returned_values);
        }
        foreach ($returned_values as $key) {
            switch ($key) {
                case 'name':
                    $fileInfo['name'] = basename($file);
                    break;
                case 'server_path':
                    $fileInfo['server_path'] = $file;
                    break;
                case 'size':
                    $fileInfo['size'] = filesize($file);
                    break;
                case 'date':
                    $fileInfo['date'] = filemtime($file);
                    break;
                case 'readable':
                    $fileInfo['readable'] = is_readable($file);
                    break;
                case 'writable':
                    $fileInfo['writable'] = is_writable($file);
                    break;
                case 'executable':
                    $fileInfo['executable'] = is_executable($file);
                    break;
                case 'fileperms':
                    $fileInfo['file_perms'] = fileperms($file);
                    break;
            }
        }

        return $fileInfo;
    }
}