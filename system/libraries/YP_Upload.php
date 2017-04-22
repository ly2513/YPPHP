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
     * The original filename as provided by the client.
     *
     * @var string
     */
    /**
     *
     *
     * @var string
     */
    protected $originalName;

    /**
     * The filename given to a file during a move.
     *
     * @var string
     */
    protected $name;

    /**
     * The type of file as provided by PHP
     *
     * @var string
     */
    protected $originalMimeType;

    /**
     * The type of file based on
     * our inspections.
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

    //--------------------------------------------------------------------

    /**
     * Accepts the file information as would be filled in from the $_FILES array.
     *
     * @param string $path         The temporary location of the uploaded file.
     * @param string $originalName The client-provided filename.
     * @param string $mimeType     The type of file as provided by PHP
     * @param int    $size         The size of the file, in bytes
     * @param int    $error        The error constant of the upload (one of PHP's UPLOADERRXXX constants)
     */
    /**
     * YP_Upload constructor.
     *
     * @param string      $path  本地临时上传文件
     * @param string      $originalName 客户端提供的文件名称
     * @param string|null $mimeType
     * @param int|null    $size  文件大小(字节)
     * @param int|null    $error 上传的错误码
     */
    public function __construct(string $path, string $originalName, string $mimeType = null, int $size = null, int $error = null)
    {
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
        if ($this->hasMoved)
        {
            throw new \RuntimeException('The file has already been moved.');
        }

        if (! $this->isValid())
        {
            throw new \RuntimeException('The original file is not a valid file.');
        }

        $targetPath = rtrim($targetPath, '/').'/';
        $name = is_null($name) ? $this->getName() : $name;
        $destination = $overwrite ? $this->getDestination($targetPath. $name) : $targetPath.$name;

        if (! @move_uploaded_file($this->path, $destination))
        {
            $error = error_get_last();
            throw new \RuntimeException(sprintf('Could not move file %s to %s (%s)', basename($this->path), $targetPath, strip_tags($error['message'])));
        }

        @chmod($targetPath, 0777 & ~umask());

        // Success, so store our new information
        $this->path = $targetPath;
        $this->name = $name;
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
    public function getSize(string $unit='b'): int
    {
        if (is_null($this->size))
        {
            $this->size = filesize($this->path);
        }

        switch (strtolower($unit))
        {
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
        if (is_null($this->error))
        {
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

        return isset($errors[$error])
            ? sprintf($errors[$error], $this->getName())
            : sprintf('The file "%s" was not uploaded due to an unknown error.', $this->getName());
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

    //--------------------------------------------------------------------

    /**
     * Gets the temporary filename where the file was uploaded to.
     *
     * @return string
     */
    /**
     * 获得上传文件的临时文件
     *
     * @return string
     */
    public function getTempName(): string
    {
        return $this->path;
    }

    //--------------------------------------------------------------------

    /**
     * Generates a random names based on a simple hash and the time, with
     * the correct file extension attached.
     *
     * @return string
     */
    public function getRandomName(): string
    {
        return time().'_'. bin2hex(random_bytes(10)).'.'.$this->getExtension();
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

    //--------------------------------------------------------------------

    /**
     * Returns the original file extension, based on the file name that
     * was uploaded. This is NOT a trusted source.
     * For a trusted version, use guessExtension() instead.
     *
     * @return string|null
     */
    public function getClientExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    //--------------------------------------------------------------------

    /**
     * Retrieve the media type of the file. SHOULD not use information from
     * the $_FILES array, but should use other methods to more accurately
     * determine the type of file, like finfo, or mime_content_type().
     *
     * @return string|null The media type we determined it to be.
     */
    /**
     * 获得文件的类型
     *
     * @return string
     */
    public function getType(): string
    {
        if (! is_null($this->mimeType))
        {
            return $this->mimeType;
        }

        if (function_exists('finfo_file'))
        {
            $finfo          = finfo_open(FILEINFO_MIME_TYPE);
            $this->mimeType = finfo_file($finfo, $this->path);
            finfo_close($finfo);
        }
        else
        {
            $this->mimeType = mime_content_type($this->path);
        }

        return $this->mimeType;
    }

    //--------------------------------------------------------------------

    /**
     * Returns the mime type as provided by the client.
     * This is NOT a trusted value.
     * For a trusted version, use getMimeType() instead.
     *
     * @return string|null The media type sent by the client or null if none
     *                     was provided.
     */
    public function getClientType(): string
    {
        return $this->originalMimeType;
    }

    //--------------------------------------------------------------------

    /**
     * Returns whether the file was uploaded successfully, based on whether
     * it was uploaded via HTTP and has no errors.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return is_uploaded_file($this->path) && $this->error === UPLOAD_ERR_OK;
    }

    //--------------------------------------------------------------------

    /**
     * Returns the destination path for the move operation where overwriting is not expected.
     *
     * First, it checks whether the delimiter is present in the filename, if it is, then it checks whether the
     * last element is an integer as there may be cases that the delimiter may be present in the filename.
     * For the all other cases, it appends an integer starting from zero before the file's extension.
     *
     * @param string $destination
     * @param string $delimiter
     * @param int    $i
     *
     * @return string
     */
    public function getDestination(string $destination, string $delimiter = '_', int $i = 0): string
    {
        while (file_exists($destination))
        {
            $info = pathinfo($destination);
            if (strpos($info['filename'], $delimiter) !== false)
            {
                $parts = explode($delimiter, $info['filename']);
                if (is_numeric(end($parts)))
                {
                    $i = end($parts);
                    array_pop($parts);
                    array_push($parts, ++$i);
                    $destination = $info['dirname'] . '/' . implode($delimiter, $parts) . '.' .  $info['extension'];
                }
                else
                {
                    $destination = $info['dirname'] . '/' . $info['filename'] . $delimiter . ++$i . '.' .  $info['extension'];
                }
            }
            else
            {
                $destination = $info['dirname'] . '/' . $info['filename'] . $delimiter . ++$i . '.' .  $info['extension'];
            }
        }
        return $destination;
    }
}