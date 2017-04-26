<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 10:40
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP;

class Autoload
{
    /**
     * 命名空间映射数组
     * 将命名空间作为key,路径作为value
     *
     * @var array
     */
    protected $namespaceMap = [];

    /**
     * 类映射数组
     * 将类名作为key,类的路径作为value
     *
     * @var array
     */
    protected $classMap = [];

    /**
     * 初始化自动加载配置
     *
     * @param \Config\Autoload $config
     */
    public function initialize(\Config\Autoload $config)
    {
        if (empty($config->namespaceMap) && empty($config->classMap)) {
            throw new \InvalidArgumentException('Config array must contain either the \'namespaceMap\' key or the \'classMap\' key.');
        }
        if (isset($config->namespaceMap)) {
            $this->namespaceMap = $config->namespaceMap;
        }
        if (isset($config->classMap)) {
            $this->classMap = $config->classMap;
        }
        unset($config);
    }

    public function register()
    {
        // 设置加载文件的后缀
        spl_autoload_extensions('.php,.inc');
        // 加载类
        spl_autoload_register([$this, 'loadClass'], true, true);
        // 加载框架运行必要的类
        $config = is_array($this->classMap) ? $this->classMap : [];
        spl_autoload_register(function ($class) use ($config) {
            if (!array_key_exists($class, $config)) {
                return false;
            }
            include_once $config[$class];
        }, true, true);
    }

    /**
     * 加载类
     *
     * @param $class
     *
     * @return bool|mixed
     */
    public function loadClass($class)
    {
        $class       = trim($class, '\\');
        $class       = str_ireplace('.php', '', $class);
        $mapped_file = $this->loadInNamespace($class);
        if (!$mapped_file) {
            $mapped_file = $this->loadLegacy($class);
        }

        return $mapped_file;
    }

    /**
     * 为给定类名加载类文件
     *
     * @param $class
     *
     * @return bool
     */
    protected function loadInNamespace($class)
    {
        if (strpos($class, '\\') === false) {
            return false;
        }
        foreach ($this->namespaceMap as $namespace => $directories) {
            if (is_string($directories)) {
                $directories = [$directories];
            }
            foreach ($directories as $directory) {
                if (strpos($class, $namespace) === 0) {
                    $filePath = $directory . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
                    $filename = $this->requireFile($filePath);
                    if ($filename) {
                        return $filename;
                    }
                }
            }
        }

        // 没找到映射文件
        return false;
    }

    /**
     * 加载应用的控制器、类库、模型
     *
     * @param $class
     *
     * @return bool
     */
    protected function loadLegacy($class)
    {
        if (strpos('\\', $class) !== false) {
            return false;
        }
        // 加载应用的控制器、类库、模型
        $paths = [
            APP_PATH . 'Controllers/',
            APP_PATH . 'Libraries/',
            APP_PATH . 'Models/',
        ];
        $class = str_replace('\\', '/', $class) . '.php';

        foreach ($paths as $path) {
            if ($file = $this->requireFile($path . $class)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * 加载文件
     *
     * @param $file 需加载的文件
     *
     * @return bool|string
     */
    protected function requireFile($file)
    {
        $file = $this->sanitizeFilename($file);
        if (file_exists($file)) {
            require_once $file;

            return $file;
        }

        return false;
    }

    /**
     * 规范化文件名，移除非法字符使用破折号代替。
     *
     * @param string $filename
     *
     * @return string       需要规范的文件
     */
    public function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9\s\/\-\_\.\:\\\\]/', '', $filename);
        // 清理我们的文件名扩展
        $filename = trim($filename, '.-_');

        return $filename;
    }

}