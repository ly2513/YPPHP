<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_Exceptions
{
    /**
     * 输出缓冲机制的嵌套级别
     *
     * @var int
     */
    public $ob_level;

    /**
     * 错误视图路径
     * 包含cli and html 视图
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Constructor.
     *
     * @param \Config\App $config
     */
    public function __construct(\Config\App $config)
    {
        $this->ob_level = ob_get_level();
        $this->viewPath = rtrim($config->errorViewPath, '/ ') . '/';
    }

    /**
     * 负责注册错误，异常和关闭处理我们的申请
     */
    public function initialize()
    {
        // 设置异常处理
        set_exception_handler([$this, 'exceptionHandler']);
        // 设置错误处理
        set_error_handler([$this, 'errorHandler']);
        // PHP7 设置关闭时捕获解析错误进行处理
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * 捕获任何未捕获的错误和异常，包括最致命的错误。如果display_errors开启,将记录错误显示出来，
     * 和允许自定义方法去解决
     *
     * @param \Throwable $exception
     */
    public function exceptionHandler(\Throwable $exception)
    {
        // 获取异常信息,将这些异常信息显示在错误信息模板中
        $type    = get_class($exception);
        $codes   = $this->determineCodes($exception);
        $code    = $codes[0];
        $exit    = $codes[1];
        $code    = $exception->getCode();
        $message = $exception->getMessage();
        $file    = $exception->getFile();
        $line    = $exception->getLine();
        $trace   = $exception->getTrace();
        $title   = get_class($exception);
        if (empty($message)) {
            $message = '(null)';
        }
        // 设置模板
        $templates_path = $this->viewPath;
        if (empty($templates_path)) {
            $templates_path = APP_PATH . 'Views/errors/';
        }
        if (is_cli()) {
            $templates_path .= 'cli/';
        } else {
            header('HTTP/1.1 500 Internal Server Error', true, 500);
            $templates_path .= 'html/';
        }
        $view = $this->determineView($exception, $templates_path);
        if (ob_get_level() > $this->ob_level + 1) {
            ob_end_clean();
        }
        ob_start();
        include($templates_path . $view);
        $buffer = ob_get_contents();
        ob_end_clean();
        echo $buffer;
        exit($exit);
    }

    /**
     * 有些错误可以通过错误错误,所以将这些错误转为异常,让异常处理程序将其记录并显示
     *
     * @param int         $severity
     * @param string      $message
     * @param string|null $file
     * @param int|null    $line
     * @param null        $context
     *
     * @throws \ErrorException
     */
    public function errorHandler(int $severity, string $message, string $file = null, int $line = null, $context = null)
    {
        // 将其转换为异常并将其传递给错误异常处理。
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * 处理在关闭时发生的错误
     */
    public function shutdownHandler()
    {
        $error = error_get_last();
        // 如果我们有一个没有显示的错误，然后转换或异常，并使用异常处理程序显示它到用户。
        if (!is_null($error)) {
            // 致命错误
            if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
                $this->exceptionHandler(new \ErrorException($error['message'], $error['type'], 0, $error['file'],
                    $error['line']));
            }
        }
    }

    /**
     * 根据抛出的异常来确定显示错误视图
     *
     * @param \Throwable $exception
     * @param string     $template_path
     *
     * @return string
     */
    protected function determineView(\Throwable $exception, string $template_path): string
    {
        // 线上环境应该设置一个自定义异常文件
        $view          = 'prod.php';
        $template_path = rtrim($template_path, '/ ') . '/';
        if (str_ireplace(['off', 'none', 'no', 'false', 'null'], '', ini_get('display_errors'))) {
            $view = 'error_exception.php';
        }
        // 404 错误
        if ($exception instanceof \OutOfBoundsException) {
            return 'error_404.php';
        } // 允许基于状态代码的自定义视图
        else if (is_file($template_path . 'error_' . $exception->getCode() . '.php')) {
            return 'error_' . $exception->getCode() . '.php';
        }

        return $view;
    }

    /**
     * 确定此请求的HTTP状态代码和退出状态代码
     *
     * @param \Throwable $exception
     *
     * @return array
     */
    protected function determineCodes(\Throwable $exception): array
    {
        $statusCode = abs($exception->getCode());
        if ($statusCode < 100) {
            $exitStatus = $statusCode + EXIT__AUTO_MIN; // 9 is EXIT__AUTO_MIN
            if ($exitStatus > EXIT__AUTO_MAX) // 125 is EXIT__AUTO_MAX
            {
                $exitStatus = EXIT_ERROR; // EXIT_ERROR
            }
            $statusCode = 500;
        } else {
            $exitStatus = 1; // EXIT_ERROR
        }

        return [
            $statusCode ?? 500,
            $exitStatus
        ];
    }

    /**
     * 规范路径
     *
     * @param $file
     *
     * @return string
     *
     */
    public static function cleanPath($file)
    {
        if (strpos($file, APP_PATH) === 0) {
            $file = APP_PATH . DIRECTORY_SEPARATOR . substr($file, strlen(APP_PATH));
        } elseif (strpos($file, SYSTEM_PATH) === 0) {
            $file = SYSTEM_PATH . DIRECTORY_SEPARATOR . substr($file, strlen(SYSTEM_PATH));
        } elseif (strpos($file, FRONT_PATH) === 0) {
            $file = FRONT_PATH . DIRECTORY_SEPARATOR . substr($file, strlen(FRONT_PATH));
        }

        return $file;
    }

    /**
     * 转换存储单位
     * 描述现实世界中的内存使用单位。用于memory_get_usage等
     *
     * @param int $bytes
     *
     * @return string
     */
    public static function describeMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        } else if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . 'KB';
        }

        return round($bytes / 1048576, 2) . 'MB';
    }

    /**
     * 创建PHP文件的语法高亮版本
     *
     * @param     $file
     * @param     $lineNumber
     * @param int $lines
     *
     * @return bool|string
     */
    public static function highlightFile($file, $lineNumber, $lines = 15)
    {
        if (empty ($file) || !is_readable($file)) {
            return false;
        }
        // 设置高亮颜色值
        if (function_exists('ini_set')) {
            ini_set('highlight.comment', '#767a7e; font-style: italic');
            ini_set('highlight.default', '#c7c7c7');
            ini_set('highlight.html', '#06B');
            ini_set('highlight.keyword', '#f1ce61;');
            ini_set('highlight.string', '#869d6a');
        }
        try {
            $source = file_get_contents($file);
        } catch (\Throwable $e) {
            return false;
        }
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $source = explode("\n", highlight_string($source, true));
        $source = str_replace('<br />', "\n", $source[1]);
        $source = explode("\n", str_replace("\r\n", "\n", $source));
        // 显示获取的部分
        $start = $lineNumber - (int)round($lines / 2);
        $start = $start < 0 ? 0 : $start;
        // 获得我们需要显示的线条，同时保留行号
        $source = array_splice($source, $start, $lines, true);
        // 用于格式化源行号
        $format = '% ' . strlen($start + $lines) . 'd';
        $out    = '';
        // 因为高亮可能有不均匀的数字打开和关闭跨度标签在一行，为了确保我们能关闭他们所有的线显示正常。
        $spans = 1;
        foreach ($source as $n => $row) {
            $spans += substr_count($row, '<span') - substr_count($row, '</span');
            $row = str_replace(["\r", "\n"], ['', ''], $row);
            if (($n + $start + 1) == $lineNumber) {
                preg_match_all('#<[^>]+>#', $row, $tags);
                $out .= sprintf("<span class='line highlight'><span class='number'>{$format}</span> %s\n</span>%s",
                    $n + $start + 1, strip_tags($row), implode('', $tags[0]));
            } else {
                $out .= sprintf('<span class="line"><span class="number">' . $format . '</span> %s', $n + $start + 1,
                        $row) . "\n";
            }
        }
        $out .= str_repeat('</span>', $spans);

        return '<pre><code>' . $out . '</code></pre>';
    }
}