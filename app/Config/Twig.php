<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 17:57
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

/**
 * 模板引擎twig配置类
 *
 * Class Twig
 *
 * @package Config
 */
class Twig
{

    /**
     *  模板扩展名
     *
     * @var string
     */
    public $extension = '.html';

    /**
     * 模板目录
     *
     * @var string
     */
    public $template_dir = APP_PATH . 'Views/Twig/';

    /**
     * 模板缓存目录
     *
     * @var string
     */
    public $cache_dir = CACHE_PATH . 'Twig/';

    /**
     * 是否开启调试
     *
     * @var bool
     */
    public $debug = false;

    /**
     * 是否开启自动刷新
     *
     * @var bool
     */
    public $auto_reload = true;
}
