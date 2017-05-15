<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:42
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

class YP_Upload
{
    /**
     * 临时文件目录
     *
     * @var string
     */
    protected $path;

    /**
     * 上传文件的原始文件名
     *
     * @var string
     */
    protected $originalName;

    /**
     * 在移动文件期间给文件的文件名
     *
     * @var string
     */
    protected $name;

    /**
     * PHP提供的文件类型
     *
     * @var string
     */
    protected $originalMimeType;

    /**
     * 根据我们的检查文件类型
     *
     * @var string
     */
    protected $mimeType;

    /**
     * 文件的大小(字节)
     *
     * @var int
     */
    protected $size;

    /**
     * 上传错误
     *
     * @var int
     */
    protected $error;

    /**
     * 文件是否已被移走
     *
     * @var bool
     */
    protected $hasMoved = false;

    /**
     * YP_Upload constructor.
     *
     * @param string      $path         本地临时上传文件
     * @param string      $originalName 客户端提供的文件名称
     * @param string|null $mimeType
     * @param int|null    $size         文件大小(字节)
     * @param int|null    $error        上传的错误码
     */
    public function __construct(
        string $path,
        string $originalName,
        string $mimeType = null,
        int $size = null,
        int $error = 0
    ) {
        $this->path             = $path;
        $this->name             = $originalName;
        $this->originalName     = $originalName;
        $this->originalMimeType = $mimeType;
        $this->size             = $size;
        $this->error            = $error;
    }

    /**
     * 将上传文件移动到新的目录
     *
     * @param string      $targetPath
     * @param string|null $name
     * @param bool        $overwrite
     *
     * @return bool
     */
    public function move(string $targetPath, string $name = null, bool $overwrite = false)
    {
        if ($this->hasMoved) {
            throw new \RuntimeException('The file has already been moved.');
        }
        if (!$this->isValid()) {
            throw new \RuntimeException('The original file is not a valid file.');
        }
        $targetPath = rtrim($targetPath, '/') . '/';
        is_dir($targetPath) or mkdir($targetPath, 0777, true);
        $name        = is_null($name) ? $this->getName() : $name;
        $destination = $overwrite ? $this->getDestination($targetPath . $name) : $targetPath . $name;
        if (!@move_uploaded_file($this->path, $destination)) {
            $error = error_get_last();
            throw new \RuntimeException(sprintf('Could not move file %s to %s (%s)', basename($this->path), $targetPath,
                strip_tags($error['message'])));
        }
        @chmod($targetPath, 0777 & ~umask());
        // 上传成功,将相关信息存储起来
        $this->path     = $targetPath;
        $this->name     = $name;
        $this->hasMoved = true;

        return true;
    }

    /**
     * 获取文件是否移走的状态值
     *
     * @return bool
     */
    public function hasMoved(): bool
    {
        return $this->hasMoved;
    }

    /**
     * 获取文件的大小
     *
     * @param string $unit 单位 b:Bytes; kb:Kilobytes; mb:Megabytes
     *
     * @return int
     */
    public function getSize(string $unit = 'b'): int
    {
        if (is_null($this->size)) {
            $this->size = filesize($this->path);
        }
        switch (strtolower($unit)) {
            case 'kb':
                return number_format($this->size / 1024, 3);
                break;
            case 'mb':
                return number_format(($this->size / 1024) / 1024, 3);
                break;
        }

        return $this->size;
    }

    /**
     * 获得上传失败时的错误吗
     *
     * @return int
     */
    public function getError(): int
    {
        if (is_null($this->error)) {
            return UPLOAD_ERR_OK;
        }

        return $this->error;
    }

    /**
     * 获取错误提示信息
     *
     * @return string
     */
    public function getErrorString()
    {
        static $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds your upload_max_filesize ini directive.',
            UPLOAD_ERR_FORM_SIZE  => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.',
        ];
        $error = is_null($this->error) ? UPLOAD_ERR_OK : $this->error;

        return isset($errors[$error]) ? sprintf($errors[$error],
            $this->getName()) : sprintf('The file "%s" was not uploaded due to an unknown error.', $this->getName());
    }

    /**
     * 获得上传的文件名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获得上传文件的临时文件
     *
     * @return string
     */
    public function getTempName(): string
    {
        return $this->path;
    }

    /**
     * 根据简单哈希和时间生成随机文件名称，附加正确的文件扩展名
     *
     * @return string
     */
    public function getRandomName(): string
    {
        return time() . '_' . bin2hex(random_bytes(10)) . '.' . $this->getExtension();
    }

    /**
     * 获得文件的扩展名称
     *
     * @return string
     */
    public function getExtension(): string
    {
        return \Config\Mimes::guessExtensionFromType($this->getType());
    }

    /**
     * 根据上传的文件名返回原始文件扩展名
     *
     * @return string
     */
    public function getClientExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * 获得文件的类型
     *
     * @return string
     */
    public function getType(): string
    {
        if (!is_null($this->mimeType)) {
            return $this->mimeType;
        }
        if (function_exists('finfo_file')) {
            $finfo          = finfo_open(FILEINFO_MIME_TYPE);
            $this->mimeType = finfo_file($finfo, $this->path);
            finfo_close($finfo);
        } else {
            $this->mimeType = mime_content_type($this->path);
        }

        return $this->mimeType;
    }

    /**
     * 返回由客户端提供的MIME类型。
     *
     * @return string
     */
    public function getClientType(): string
    {
        return $this->originalMimeType;
    }

    /**
     * 返回文件是否成功上传，基于它是否通过HTTP上传并且没有错误。
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return is_uploaded_file($this->path) && $this->error === UPLOAD_ERR_OK;
    }

    /**
     * 返回进行移动操作之后的目标的路径，期望不能覆盖。
     * 首先，它检查分隔符是否存在于文件名中，如果存在，那么检查最后一个元素是否是整数，
     * 因为可能有分隔符可能存在于文件名中。在所有其他情况下，文件的扩展名之前,它将一个从零开始的整数。
     *
     * @param string $destination
     * @param string $delimiter
     * @param int    $i
     *
     * @return string
     */
    public function getDestination(string $destination, string $delimiter = '_', int $i = 0): string
    {
        while (file_exists($destination)) {
            $info = pathinfo($destination);
            if (strpos($info['filename'], $delimiter) !== false) {
                $parts = explode($delimiter, $info['filename']);
                if (is_numeric(end($parts))) {
                    $i = end($parts);
                    array_pop($parts);
                    array_push($parts, ++$i);
                    $destination = $info['dirname'] . '/' . implode($delimiter, $parts) . '.' . $info['extension'];
                } else {
                    $destination = $info['dirname'] . '/' . $info['filename'] . $delimiter . ++$i . '.' . $info['extension'];
                }
            } else {
                $destination = $info['dirname'] . '/' . $info['filename'] . $delimiter . ++$i . '.' . $info['extension'];
            }
        }

        return $destination;
    }
}