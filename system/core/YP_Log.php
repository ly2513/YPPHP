<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:38
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use Psr\Log\LoggerInterface;

/**
 *
 * Class YP_Log
 *
 * @package YP\Core
 */
class YP_Log implements LoggerInterface
{

    /**
     * 保存日志的目录
     *
     * @var string
     */
    protected $logPath;

    /**
     * 日志级别
     *
     * @var array
     */
    protected $logLevels = [
        'emergency' => 1,
        'alert'     => 2,
        'critical'  => 3,
        'error'     => 4,
        'debug'     => 5,
        'warning'   => 6,
        'notice'    => 7,
        'info'      => 8,
    ];

    /**
     * 用户日志级别,Config/Log的配置
     *
     * @var array
     */
    protected $loggableLevels = [];

    /**
     * File permissions
     *
     * @var int
     */
    protected $filePermissions = 0644;

    /**
     * Format of the timestamp for log files.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 日志文件扩展名
     *
     * @var
     */
    protected $fileExt;

    /**
     * Holds the configuration for each handler.
     * The key is the handler's class name. The
     * value is an associative array of configuration
     * items.
     *
     * @var array
     */
    protected $handlerConfig = [];

    /**
     * 缓存日志
     *
     * @var array
     */
    public $logCache;

    /**
     * 是否开启缓存日志
     * true : 开启
     * false: 关闭
     *
     * @var bool
     */
    protected $cacheLogs = false;

    /**
     * YP_Log constructor.
     *
     * @param      $config 日志配置信息
     * @param bool $debug  是否开启调试
     */
    public function __construct($config, bool $debug = YP_DEBUG)
    {
        $this->loggableLevels = is_array($config->threshold) ? $config->threshold : range(1, (int)$config->threshold);
        // 使用数字设置日志级别门阀值方便开发者
        if (count($this->loggableLevels)) {
            $temp = [];
            foreach ($this->loggableLevels as $level) {
                $temp[] = array_search((int)$level, $this->logLevels);
            }
            $this->loggableLevels = $temp;
            unset($temp);
        }
        $this->dateFormat = $config->dateFormat ?? $this->dateFormat;
        if (!is_array($config->handlers) || empty($config->handlers)) {
            throw new \RuntimeException('LoggerConfig must provide at least one Handler.');
        }
        // 保存处理日志的配置信息
        $this->handlerConfig = $config->handlers;
        $this->cacheLogs     = (bool)$debug;
        if ($this->cacheLogs) {
            $this->logCache = [];
        }
    }

    /**
     * 记录系统无法使用的错误
     *
     * @param       $message
     * @param array $context
     */
    public function emergency($message, array $context = [])
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    /**
     * 记录临界条件时的错误
     *
     * @param       $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }

    /**
     * 记录运行时错误，不需要立即动作，但通常应记录和监视
     *
     * @param       $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    /**
     * 记录非错误的异常事件
     *
     * @param       $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 记录正常但是重要的事件
     *
     * @param       $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }

    /**
     * 记录有趣的事件(sql日志、用户日志)
     *
     * @param       $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    /**
     * 记录详细的调试信息
     *
     * @param       $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 记录任何日志级别的日志信息
     *
     * @param       $level   日志级别
     * @param       $message 记录的信息
     * @param array $context
     *
     * @return bool
     */
    public function log($level, $message, array $context = []): bool
    {
        if (is_numeric($level)) {
            $level = array_search((int)$level, $this->logLevels);
        }
        // 判断日志级别是否有效
        if (!array_key_exists($level, $this->logLevels)) {
            throw new \InvalidArgumentException($level . ' is an invalid log level.');
        }
        // 检查当前日志类型是否立马要记录
        if (!in_array($level, $this->loggableLevels)) {
            return false;
        }
        // 解析占位符
        $message = $this->interpolate($message, $context);
        if (!is_string($message)) {
            $message = print_r($message, true);
        }
        // 是否开启日志缓存
        if ($this->cacheLogs) {
            $this->logCache[] = [
                'level' => $level,
                'msg'   => $message
            ];
        }
        //        P($this->handlerConfig );
        foreach ($this->handlerConfig as $className => $config) {
            /**
             * @var \CodeIgniter\Log\Handlers\HandlerInterface
             */
            $handler = new $className($config);
            if (!$handler->canHandle($level)) {
                continue;
            }
            // If the handler returns false, then we
            // don't execute any other handlers.
            if (!$handler->setDateFormat($this->dateFormat)->handle($level, $message)) {
                break;
            }
        }

        return true;
    }

    //--------------------------------------------------------------------
    /**
     * Replaces any placeholders in the message with variables
     * from the context, as well as a few special items like:
     *
     * {session_vars}
     * {post_vars}
     * {get_vars}
     * {env}
     * {env:foo}
     * {file}
     * {line}
     *
     * @param       $message
     * @param array $context
     *
     * @return string
     */
    protected function interpolate($message, array $context = [])
    {
        if (!is_string($message)) {
            return $message;
        }
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // Verify that the 'exception' key is actually an exception
            // or error, both of which implement the 'Throwable' interface.
            if ($key == 'exception' && $val instanceof \Throwable) {
                $val = $val->getMessage() . ' ' . $this->cleanFileNames($val->getFile()) . ':' . $val->getLine();
            }
            // todo - sanitize input before writing to file?
            $replace['{' . $key . '}'] = $val;
        }
        // Add special placeholders
        $replace['{post_vars}'] = '$_POST: ' . print_r($_POST, true);
        $replace['{get_vars}']  = '$_GET: ' . print_r($_GET, true);
        $replace['{env}']       = ENVIRONMENT;
        // Allow us to log the file/line that we are logging from
        if (strpos($message, '{file}') !== false) {
            list($file, $line) = $this->determineFile();
            $replace['{file}'] = $file;
            $replace['{line}'] = $line;
        }
        // Match up environment variables in {env:foo} tags.
        if (strpos($message, 'env:') !== false) {
            preg_match('/env:[^}]+/', $message, $matches);
            if (count($matches)) {
                foreach ($matches as $str) {
                    $key                 = str_replace('env:', '', $str);
                    $replace["{{$str}}"] = $_ENV[$key] ?? 'n/a';
                }
            }
        }
        if (isset($_SESSION)) {
            $replace['{session_vars}'] = '$_SESSION: ' . print_r($_SESSION, true);
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    //--------------------------------------------------------------------
    /**
     * Determines the current file/line that the log method was called from.
     * by analyzing the backtrace.
     *
     * @return array
     */
    public function determineFile()
    {
        // Determine the file and line by finding the first
        // backtrace that is not part of our logging system.
        $trace = debug_backtrace();
        $file  = null;
        $line  = null;
        foreach ($trace as $row) {
            if (in_array($row['function'], ['interpolate', 'determineFile', 'log', 'log_message'])) {
                continue;
            }
            $file = $row['file'] ?? isset($row['object']) ? get_class($row['object']) : 'unknown';
            $line = $row['line'] ?? $row['function'] ?? 'unknown';
            break;
        }

        return [
            $file,
            $line
        ];
    }

    //--------------------------------------------------------------------
    /**
     * Cleans the paths of filenames by replacing APPPATH, BASEPATH, FCPATH
     * with the actual var. i.e.
     *
     *  /var/www/site/application/Controllers/Home.php
     *      becomes:
     *  APPPATH/Controllers/Home.php
     *
     * @param $file
     *
     * @return mixed
     */
    protected function cleanFileNames($file)
    {
        $file = str_replace(APPPATH, 'APPPATH/', $file);
        $file = str_replace(BASEPATH, 'BASEPATH/', $file);
        $file = str_replace(FCPATH, 'FCPATH/', $file);

        return $file;
    }
}