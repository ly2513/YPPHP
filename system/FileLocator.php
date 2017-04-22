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
        // Always keep the Application directory as a "package".
        array_unshift($this->namespaces, APP_PATH);
    }

    //--------------------------------------------------------------------
    /**
     * Attempts to locate a file by examining the name for a namespace
     * and looking through the PSR-4 namespaced files that we know about.
     *
     * @param string $file   The namespaced file to locate
     * @param string $folder The folder within the namespace that we should look for the file.
     * @param string $ext    The file extension the file should have.
     *
     * @return string       The path to the file if found, or an empty string.
     */
    /**
     * 加载文件
     *
     * @param string      $file
     * @param string|null $folder
     * @param string      $ext
     *
     * @return string
     */
    public function locateFile(string $file, string $folder = null, string $ext = 'php'): string
    {
        // Ensure the extension is on the filename
        $file = strpos($file, '.' . $ext) !== false ? $file : $file . '.' . $ext;
        // Clean the folder name from the filename
        if (!empty($folder)) {
            $file = str_replace($folder . '/', '', $file);
        }
        // No namespaceing? Try the application folder.
        if (strpos($file, '\\') === false) {
            return $this->legacyLocate($file, $folder);
        }
        // Standardize slashes to handle nested directories.
        $file     = str_replace('/', '\\', $file);
        $segments = explode('\\', $file);
        // The first segment will be empty if a slash started the filename.
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
        // IF we have a folder name, then the calling function
        // expects this file to be within that folder, like 'Views',
        // or 'libraries'.
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
     *      'application/modules/foo/Config/Routes.php',
     *      'application/modules/bar/Config/Routes.php',
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
        // Ensure the extension is on the filename
        $path = strpos($path, '.' . $ext) !== false ? $path : $path . '.' . $ext;
        foreach ($this->namespaces as $name => $folder) {
            $folder = rtrim($folder, '/') . '/';
            if (file_exists($folder . $path)) {
                $foundPaths[] = $folder . $path;
            }
        }
        // Remove any duplicates
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
                // Remove the file extension (.php)
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
     * Checks the application folder to see if the file can be found.
     * Only for use with filenames that DO NOT include namespacing.
     *
     * @param string      $file
     * @param string|null $folder
     *
     * @return string
     * @internal param string $ext
     *
     */
    protected function legacyLocate(string $file, string $folder = null): string
    {
        $paths = [APPPATH, BASEPATH];
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