<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 10:58
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

class FileCollection
{
    /**
     * An array of UploadedFile instances for any files
     * uploaded as part of this request.
     * Populated the first time either files(), file(), or hasFile()
     * is called.
     *
     * @var array|null
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
                if ($uploadedFile instanceof UploadedFile) {
                    return $uploadedFile;
                }

                return null;
            }
            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];
                if ($uploadedFile instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
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

    //--------------------------------------------------------------------
    /**
     * Taking information from the $_FILES array, it creates an instance
     * of UploadedFile for each one, saving the results to this->files.
     *
     * Called by files(), file(), and hasFile()
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

    //--------------------------------------------------------------------
    /**
     * Given a file array, will create UploadedFile instances. Will
     * loop over an array and create objects for each.
     *
     * @param array $array
     *
     * @return array
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

        return new UploadedFile($array['tmp_name'] ?? null, $array['name'] ?? null, $array['type'] ?? null,
            $array['size'] ?? null, $array['error'] ?? null);
    }

    //--------------------------------------------------------------------
    /**
     * Reformats the odd $_FILES array into something much more like
     * we would expect, with each object having its own array.
     *
     * Thanks to Jack Sleight on the PHP Manual page for the basis
     * of this method.
     *
     * @see http://php.net/manual/en/reserved.variables.files.php#118294
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

    //--------------------------------------------------------------------
    /**
     * Navigate through a array looking for a particular index
     *
     * @param array $index The index sequence we are navigating down
     * @param array $value The portion of the array to process
     *
     * @return mixed
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