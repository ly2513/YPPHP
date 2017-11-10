<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 08:11
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Cli;

use YP\Core\YP_Request as Request;
use Config\App;

/**
 * Class YP_CliRequest
 *
 * @package YP\Cli
 */
class YP_CliRequest extends Request
{
    /**
     * 存储段我们 CLI“URI” 命令
     *
     * @var array
     */
    protected $segments = [];

    /**
     * 命令行选项及其值
     *
     * @var array
     */
    protected $options = [];

    /**
     * YP_CliRequest constructor.
     *
     * @param App $config
     */
    public function __construct(App $config)
    {
        parent::__construct($config, null);
        // 不终止脚本, 当CLI终端消失
        ignore_user_abort(true);
        $this->parseCommand();
    }

    /**
     * 返回请求脚本的“路径”，以便将其用于路由到适当的 控制器/方法。
     * 路径是通过处理命令行参数来决定的，就像它是一个URL一样，直到我们命中第一个选项为止
     *
     * @return string
     */
    public function getPath(): string
    {
        $path = implode('/', $this->segments);

        return empty($path) ? '' : $path;
    }

    /**
     * 返回找到的所有CLI选项的关联数组及其值
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 返回传入的单个CLI选项的值。
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption(string $key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }

    /**
     * 将选项返回为字符串，适合在CLI上传递给其他命令
     *
     * @return string
     */
    public function getOptionString(): string
    {
        if (empty($this->options)) {
            return '';
        }
        $out = '';
        foreach ($this->options as $name => $value) {
            // 如果有一个空间，我们需要组会通过正确
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }
            $out .= "-{$name} $value ";
        }

        return $out;
    }

    /**
     * 解析命令行，它被称为从收集所有的选择和有效的细分
     */
    protected function parseCommand()
    {
        // 由于我们自己构建了选项，所以一旦我们找到第一个破折号，就停止将它添加到段数组中
        $options_found = false;
        $argc          = $this->getServer('argc', FILTER_SANITIZE_NUMBER_INT);
        $argv          = $this->getServer('argv');
        //
        for ($i = 1; $i < $argc; $i++) {
            // 如果参数开始时没有“-”，则将其添加到我们的段中。
            if (! $options_found && strpos($argv[$i], '-') === false) {
                $this->segments[] = filter_var($argv[$i], FILTER_SANITIZE_STRING);
                continue;
            }
            $options_found = true;
            if (substr($argv[$i], 0, 1) != '-') {
                continue;
            }
            $arg   = filter_var(str_replace('-', '', $argv[$i]), FILTER_SANITIZE_STRING);
            $value = null;
            //
            if (isset($argv[$i + 1]) && substr($argv[$i + 1], 0, 1) != '-') {
                $value = filter_var($argv[$i + 1], FILTER_SANITIZE_STRING);
                $i++;
            }
            $this->options[$arg] = $value;
        }
    }
}
