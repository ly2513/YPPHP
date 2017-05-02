<?php
/**
 * User: yongli
 * Date: 17/5/2
 * Time: 09:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

/**
 * Class Validation 自动验证配置
 *
 * @package Config
 */
class Validation
{
    /**
     * 存储包含可用规则的类
     *
     * @var array
     */
    public $ruleSets = [
        \YP\Libraries\YP_Rules::class,
    ];

    /**
     * 指定用于显示错误的视图
     *
     * @var array
     */
    public $templates = [
        'list'   => APP_PATH . 'Views/Validation/list.html',
        'single' => APP_PATH . 'Views/Validation/single.html'
    ];

    // TODO 在此添加校验规则
}