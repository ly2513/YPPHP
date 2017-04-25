<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 17:54
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use Config\Services;

class YP_Twig
{
    /**
     * 模板对象
     *
     * @var \Twig_Environment
     */
    public $twig;

    /**
     * 请求对象
     *
     * @var
     */
    public $request;

    /**
     * Twig配置信息
     *
     * @var array
     */
    public $config = [
        'cache_dir'   => false,
        'debug'       => false,
        'auto_reload' => true,
        'extension'   => '.tpl',
    ];

    private $data = [];

    /**
     * YP_Twig constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        // 合并用户配置
        $this->config = array_merge($this->config, (array)$config);
        is_dir($this->config['cache_dir']) or mkdir($this->config['cache_dir'], 0777, true);
        is_dir($this->config['template_dir']) or mkdir($this->config['template_dir'], 0777, true);
        // 实例化一个文件加载系统
        $loader     = new \Twig_Loader_Filesystem ($this->config['template_dir']);
        $this->twig = new \Twig_Environment ($loader, [
            'cache'       => $this->config['cache_dir'],
            'debug'       => $this->config['debug'],
            'auto_reload' => $this->config['auto_reload'],
        ]);
        $this->twig->addFunction(new \Twig_SimpleFunction('site_url', 'site_url'));
        $this->twig->addFunction(new \Twig_SimpleFunction('base_url', 'base_url'));
    }

    /**
     * 给变量赋值
     *
     * @param string|array $var
     * @param string       $value
     */
    public function assign($var, $value = null)
    {
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $this->data[$var] = $value;
        }
    }

    /**
     * 模版渲染
     *
     * @param string $template 模板名
     * @param array  $data     变量数组
     * @param bool   $return   true返回 false直接输出页面
     *
     * @return string
     */
    public function render($template, $data = [], $return = false)
    {
        $template = $this->twig->loadTemplate($this->getTemplateName($template));
        $data     = array_merge($this->data, $data);
        if ($return === true) {
            return $template->render($data);
        } else {
            return $template->display($data);
        }
    }

    /**
     * 获取模版名
     *
     * @param string $template
     *
     * @return string
     */
    public function getTemplateName($template)
    {
        $default_ext_len = strlen($this->config['extension']);
        if (substr($template, -$default_ext_len) != $this->config['extension']) {
            $template .= $this->config['extension'];
        }
        
        return $template;
    }

    /**
     * 字符串渲染
     *
     * @param string $string 需要渲染的字符串
     * @param array  $data   变量数组
     * @param bool   $return true返回 false直接输出页面
     *
     * @return object
     */
    public function parse($string, $data = [], $return = false)
    {
        $string = $this->twig->loadTemplate($string);
        $data   = array_merge($this->data, $data);
        if ($return === true) {
            return $string->render($data);
        } else {
            return $string->display($data);
        }
    }
}