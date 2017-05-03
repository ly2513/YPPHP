<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 22:36
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

class YP_FileCollection
{

    /**
     * 存放收集上传的文件的数组
     *
     * @var
     */
    protected $files;

    /**
     * 获得所有上传的文件
     *
     * @return array|null
     */
    public function all()
    {
        $this->populateFiles();

        return $this->files;
    }

    /**
     * 获得上传的文件
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function getFile(string $name)
    {
        $this->populateFiles();
        $name = strtolower($name);
        if ($this->hasFile($name)) {
            if (strpos($name, '.') !== false) {
                $name         = explode('.', $name);
                $uploadedFile = $this->getValueDotNotationSyntax($name, $this->files);
                if ($uploadedFile instanceof YP_Upload) {
                    return $uploadedFile;
                }

                return null;
            }
            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];
                if ($uploadedFile instanceof YP_Upload) {
                    return $uploadedFile;
                }

                return null;
            }

            return null;

        }

        return null;
    }

    /**
     * 根据文件名称检查文件是否存在
     *
     * @param string $fileID
     *
     * @return bool
     */
    public function hasFile(string $fileID): bool
    {
        $this->populateFiles();
        if (strpos($fileID, '.') !== false) {
            $segments = explode('.', $fileID);
            $el = $this->files;
            foreach ($segments as $segment) {
                if (!array_key_exists($segment, $el)) {
                    return false;
                }
                $el = $el[$segment];
            }

            return true;
        }

        return isset($this->files[$fileID]);
    }

    /**
     * 从$_FILES数组获取上传信息,实例化YP_Upload对象将每一个文件保存为this->files格式
     *
     * 方便files(), file(), and hasFile()这些方法调用
     *
     */
    protected function populateFiles()
    {
        if (is_array($this->files)) {
            return;
        }
        if (empty($_FILES)) {
            return;
        }
        $this->files = [];
        $files = $this->fixFilesArray($_FILES);
        foreach ($files as $name => $file) {
            $this->files[$name] = $this->createFileObject($file);
        }
    }

    /**
     * 给定一个文件数组,实例化一个YP_Upload实例,将循环数组并为每个文件创建对象。
     *
     * @param array $array
     *
     * @return array|YP_Upload
     */
    protected function createFileObject(array $array)
    {
        if (!isset($array['name'])) {
            $output = [];
            foreach ($array as $key => $values) {
                if (!is_array($values)) {
                    continue;
                }
                $output[$key] = $this->createFileObject($values);
            }

            return $output;
        }

        return new YP_Upload($array['tmp_name'] ?? null, $array['name'] ?? null, $array['type'] ?? null,
            $array['size'] ?? null, $array['error'] ?? null);
    }

    /**
     * 将$_FILES数组中的数据格式成期望的格式,每个对象都有自己的一组数据
     *
     * @param array $data
     *
     * @return array
     */
    protected function fixFilesArray(array $data): array
    {
        $output = [];
        foreach ($data as $name => $array) {
            foreach ($array as $field => $value) {
                $pointer = &$output[$name];
                if (!is_array($value)) {
                    $pointer[$field] = $value;
                    continue;
                }
                $stack    = [&$pointer];
                $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value),
                    \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $key => $value) {
                    array_splice($stack, $iterator->getDepth() + 1);
                    $pointer = &$stack[count($stack) - 1];
                    $pointer = &$pointer[$key];
                    $stack[] = &$pointer;
                    if (!$iterator->hasChildren()) {
                        $pointer[$field] = $value;
                    }
                }
            }
        }

        return $output;
    }
    
    /**
     * 通过一个数组查找指定的下标的值
     *
     * @param $index
     * @param $value
     *
     * @return null
     */
    protected function getValueDotNotationSyntax($index, $value)
    {
        if (is_array($index) && count($index)) {
            $current_index = array_shift($index);
        }
        if (is_array($index) && count($index) && is_array($value[$current_index]) && count($value[$current_index])) {
            return $this->getValueDotNotationSyntax($index, $value[$current_index]);
        } else {
            if (isset($value[$current_index])) {
                return $value[$current_index];
            } else {
                return null;
            }
        }
    }
}