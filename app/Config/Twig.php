<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 17:57
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;


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