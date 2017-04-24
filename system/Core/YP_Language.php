<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 18:57
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_Language
{
    public $language = [];

    /**
     * 短语配置
     *
     * @var null|短语
     */
    public $locale = null;

    /**
     * 存放对应的短语
     *
     * @var array
     */
    public $lan = [];

    /**
     * 存放已经使用的短语
     *
     * @var array
     */
    public $loaded = [];

    /**
     * 判断是否已加载 MessageFormatter
     *
     * @var bool
     */
    public $intlSupport = false;

    /**
     * Language constructor.
     *
     * @param $locale 短语
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
        // 判断是否存在消息格式化类
        if (class_exists('\MessageFormatter'))
        {
            $this->intlSupport = true;
        };
    }

    /**
     * 依据短语的key,获得相应的短语
     *
     * @param string $line
     * @param array  $args
     *
     * @return array|string
     */
    public function getLine(string $line, array $args = [])
    {
        // 解析文件名和实际别名。将加载语言文件和字符串
        list($file, $line) = $this->parseLine($line);
        $output = isset($this->language[$file][$line]) ? $this->language[$file][$line] : $line;
        if (count($args)) {
            $output = $this->formatMessage($output, $args);
        }
        return $output;
    }

    /**
     * 获得短语的key和类型
     *
     * @param $line
     *
     * @return array
     */
    public function parseLine($line): array
    {
        if (strpos($line, '.') === false) {
            return [
                null,
                $line
            ];
        }
        $file = substr($line, 0, strpos($line, '.'));
        $line = substr($line, strlen($file) + 1);
        if (!array_key_exists($line, $this->language)) {
            $this->load($file);
        }

        return [
            $file,
            $this->lan[$line] ?? $line
        ];
    }

    /**
     * 加载相应的短语
     *
     * @param string $file
     * @param bool   $return
     *
     * @return array
     */
    protected function load(string $file, bool $return = false)
    {
        // 判断是否已调用
        if (in_array($file, $this->loaded)) {
            return [];
        }
        // 实例化一个语言配置对象
        $language = new \YP\Config\Language();
        if (!array_key_exists($file, $this->language)) {
            $this->language[$file] = [];
        }
        // 设置需要调用的方法变量
        $functions      = 'init' . ucfirst($file);
        $message        = $language->$functions();
        $this->loaded[] = $file;
        if ($return) {
            return $message;
        }
        // Merge our string
        $this->language[$file] = $message;
    }

    /**
     * 高级格式化短语
     *
     * @param string|array $message Message.
     * @param array	       $args    Arguments.
     *
     * @return string|array Returns formatted message.
     */
    protected function formatMessage($message, array $args = [])
    {
        if (! $this->intlSupport || ! count($args))
        {
            return $message;
        }

        if (is_array($message))
        {
            foreach ($message as $index => $value)
            {
                $message[$index] = $this->formatMessage($value, $args);
            }
            return $message;
        }

        return \MessageFormatter::formatMessage($this->locale, $message, $args);
    }

}