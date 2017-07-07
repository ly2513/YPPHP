<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 21:17
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP;

use Config\Autoload;

class FileLocator
{
    /**
     * 命名空间
     *
     * @var array
     */
    protected $namespaces;

    /**
     * FileLocator constructor.
     *
     * @param Autoload $autoload
     */
    public function __construct(Autoload $autoload)
    {
        $this->namespaces = $autoload->namespaceMap;
        unset($autoload);
        // 始终将应用程序目录保持为“包”
        array_unshift($this->namespaces, APP_PATH);
    }

    /**
     * 通过命名空间,加载相应的文件
     *
     * @param string      $file 命名空间
     * @param string|null $folder 命名空间内的文件夹
     * @param string      $ext 可能的文件后缀
     *
     * @return string 返回文件的目录或返回为空字符串
     */
    public function locateFile(string $file, string $folder = null, string $ext = 'php'): string
    {
        // 确保扩展名在文件名上
        $file = strpos($file, '.' . $ext) !== false ? $file : $file . '.' . $ext;
        // 将文件夹名从文件名中清除
        if (!empty($folder)) {
            $file = str_replace($folder . '/', '', $file);
        }
        // 如没有命名空间,尝试在app文件夹中查找
        if (strpos($file, '\\') === false) {
            return $this->legacyLocate($file, $folder);
        }
        // 规范斜线处理嵌套目录
        $file     = str_replace('/', '\\', $file);
        $segments = explode('\\', $file);
        // 如果斜杠开始文件名，第一段将为空。
        if (empty($segments[0])) {
            unset($segments[0]);
        }
        $path = $prefix = $filename = '';
        while (!empty($segments)) {
            $prefix .= empty($prefix) ? ucfirst(array_shift($segments)) : '\\' . ucfirst(array_shift($segments));
            if (!array_key_exists($prefix, $this->namespaces)) {
                continue;
            }
            $path     = $this->namespaces[$prefix] . '/';
            $filename = implode('/', $segments);
            break;
        }
        // 如果我们有一个文件夹名，那么调用函数希望这个文件在该文件夹中，比如“视图”或“库”。
        // @todo Allow it to check with and without the nested folder.
        if (!empty($folder) && strpos($filename, $folder) === false) {
            $filename = $folder . '/' . $filename;
        }
        $path .= $filename;
        if (!$this->requireFile($path)) {
            $path = '';
        }

        return $path;
    }

    /**
     * 搜索查找文件
     *
     * Example:
     *  $locator->search('Config/Routes.php');
     *  // Assuming PSR4 namespaces include foo and bar, might return:
     *  [
     *      'app/modules/foo/Config/Routes.php',
     *      'app/modules/bar/Config/Routes.php',
     *  ]
     *
     * @param string $path
     * @param string $ext
     *
     * @return array
     */
    public function search(string $path, string $ext = 'php'): array
    {
        $foundPaths = [];
        // 确保扩展名在文件名上
        $path = strpos($path, '.' . $ext) !== false ? $path : $path . '.' . $ext;
        foreach ($this->namespaces as $name => $folder) {
            $folder = rtrim($folder, '/') . '/';
            if (file_exists($folder . $path)) {
                $foundPaths[] = $folder . $path;
            }
        }
        // 去重路径
        $foundPaths = array_unique($foundPaths);

        return $foundPaths;
    }

    /**
     * 获得文件的域名空间
     *
     * @param string $path
     *
     * @return string|void
     */
    public function findQualifiedNameFromPath(string $path)
    {
        $path = realpath($path);
        if (!$path) {
            return;
        }
        foreach ($this->namespaces as $namespace => $nsPath) {
            $nsPath = realpath($nsPath);
            if (is_numeric($namespace)) {
                continue;
            }
            if (mb_strpos($path, $nsPath) === 0) {
                $className = '\\' . $namespace . '\\' . ltrim(str_replace('/', '\\',
                        mb_substr($path, mb_strlen($nsPath))), '\\');
                // 去除文件的扩展名
                $className = mb_substr($className, 0, -4);

                return $className;
            }
        }
    }

    /**
     * 文件列表
     *
     * @param string $path
     *
     * @return array
     */
    public function listFiles(string $path): array
    {
        if (empty($path)) {
            return [];
        }
        $files = [];
        helper('filesystem');
        foreach ($this->namespaces as $namespace => $nsPath) {
            $fullPath = realpath(rtrim($nsPath, '/') . '/' . $path);
            if (!is_dir($fullPath)) {
                continue;
            }
            $tempFiles = get_filenames($fullPath, true);
            //CLI::newLine($tempFiles);
            if (!count($tempFiles)) {
                continue;
            }
            $files = array_merge($files, $tempFiles);
        }

        return $files;
    }

    /**
     * 对于仅使用文件名不包含命名空间的类库,直接去检查app文件夹，查看是否找到文件。
     *
     * @param string      $file
     * @param string|null $folder
     *
     * @return string
     */
    protected function legacyLocate(string $file, string $folder = null): string
    {
        $paths = [APP_PATH, BASE_PATH];
        foreach ($paths as $path) {
            $path .= empty($folder) ? $file : $folder . '/' . $file;
            if ($this->requireFile($path) === true) {
                return $path;
            }
        }

        return '';
    }

    /**
     * 判断加载文件的是否存在
     *
     * @param string $path
     *
     * @return bool
     */
    protected function requireFile(string $path): bool
    {
        return file_exists($path);
    }
}