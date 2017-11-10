<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 10:49
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

// 加载框架的运行时需要加载类
require SYSTEM_PATH . 'Config/AutoloadConfig.php';

/**
 * 自动加载配置类
 *
 * Class Autoload
 *
 * @package Config
 */
class Autoload extends \YP\Config\AutoloadConfig
{

    /**
     * Autoload constructor.
     */
    public function __construct()
    {
        // 执行父类构造方法
        parent::__construct();
        // 命名空间映射
        $namespaceMap =
            [
             'Config'      => APP_PATH . 'Config',
             'Function'    => APP_PATH . 'Function',
             APP_NAMESPACE => APP_PATH,
            ];
        $classMap     = [];
        // 将用户设置的命名空间与系统设置的命名空间进行合并
        $this->namespaceMap = array_merge($this->namespaceMap, $namespaceMap);
        // 将用户设置的类的映射关系与系统类的映射关系进行合并
        $this->classMap = array_merge($this->classMap, $classMap);
        unset($namespaceMap, $classMap);
    }
}
