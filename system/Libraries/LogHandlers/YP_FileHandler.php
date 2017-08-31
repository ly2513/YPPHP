<?php
/**
 * User: yongli
 * Date: 17/8/31
 * Time: 23:34
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\LogHandlers;

/**
 * 文件系统处理日志错误信息
 *
 * Class YP_FileHandler
 *
 * @package YP\Libraries\LogHandlers
 */
class YP_FileHandler implements YP_HandlerInterface
{

    /**
     * 保存日志的文件夹
     *
     * @var string
     */
    protected $path;

    /**
     * 用于日志文件的扩展名
     *
     * @var string
     */
    protected $fileExtension;

    /**
     * 新日志文件的权限
     *
     * @var int
     */
    protected $filePermissions;

    /**
     * 处理程序对象
     *
     * @var array
     */
    protected $handles;

    /**
     * 日志的日期格式
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * YP_FileHandler constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->handles = $config['handles'] ?? [];
        $this->path    = $config['path'] ?? CACHE_PATH . 'Logs/';
        is_dir($this->path) or mkdir($this->path, 0777, true);
        $this->fileExtension   = $config['fileExtension'] ?? 'php';
        $this->fileExtension   = ltrim($this->fileExtension, '.');
        $this->filePermissions = $config['filePermissions'] ?? 0644;
    }

    /**
     * 检查处理程序是否处理此日志级别的日志
     *
     * @param string $level
     *
     * @return bool
     */
    public function canHandle(string $level): bool
    {
        return in_array($level, $this->handles);
    }

    /**
     * 存储日志记录时使用的日期格式
     *
     * @param string $format
     *
     * @return YP_HandlerInterface
     */
    public function setDateFormat(string $format): YP_HandlerInterface
    {
        $this->dateFormat = $format;

        return $this;
    }

    /**
     * 处理记录消息。
     * 如果处理程序返回False，则处理程序的执行将停止。尚未运行的任何处理程序都不会运行。
     *
     * @param $level
     * @param $message
     *
     * @return bool
     */
    public function handle($level, $message): bool
    {
        $filepath = $this->path . 'log-' . date('Y-m-d') . '.' . $this->fileExtension;
        $msg      = '';
        if (!file_exists($filepath)) {
            $new_file = true;
            // 只为php文件添加保护
            if ($this->fileExtension === 'php') {
                $msg .= "<?php defined('APP_PATH') OR exit('No direct script access allowed'); ?>\n\n";
            }
            touch($filepath);
        }
        if (!$fp = @fopen($filepath, 'ab')) {
            return false;
        }
        // 实例化与附加的初始日期日期时间是微秒, 这个格式需要适当的支持
        if (strpos($this->dateFormat, 'u') !== false) {
            $micro_time_full  = microtime(true);
            $micro_time_short = sprintf("%06d", ($micro_time_full - floor($micro_time_full)) * 1000000);
            $date             = new \DateTime(date('Y-m-d H:i:s.' . $micro_time_short, $micro_time_full));
            $date             = $date->format($this->dateFormat);
        } else {
            $date = date($this->dateFormat);
        }
        $msg .= strtoupper($level) . ' - ' . $date . ' --> ' . $message . "\n";
        flock($fp, LOCK_EX);
        for ($written = 0, $length = strlen($msg); $written < $length; $written += $result) {
            if (($result = fwrite($fp, substr($msg, $written))) === false) {
                break;
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        if (isset($new_file) && $new_file === true) {
            chmod($filepath, $this->filePermissions);
        }

        return is_int($result);
    }

}
