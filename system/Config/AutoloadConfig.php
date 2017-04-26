<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 11:03
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;

/**
 * 自动加载配置
 *
 * Class AutoloadConfig
 *
 * @package YP\Config
 */
class AutoloadConfig
{
    /**
     * 命名空间映射数组
     * 将命名空间作为key,路径作为value
     *
     * @var array
     */
    public $namespaceMap = [];

    /**
     * 类映射数组
     * 将类名作为key,类的路径作为value
     *
     * @var array
     */
    public $classMap = [];

    public function __construct()
    {
        // 设置框架的命名空间
        $this->namespaceMap = ['YP' => realpath(SYSTEM_PATH)];
        // 当环境变量为test时,设置测试的命名空间
        if (isset($_SERVER['YP_ENV']) && $_SERVER['YP_ENV'] == 'test') {
            $this->namespaceMap['Tests\Support'] = ROOT_PATH . 'tests/_support/';
        }
        // 设置框架初始化需自动加载的类
        $this->classMap = [
            'YP\YP'                    => SYSTEM_PATH . 'YP.php',
            'YP\CLI\CLI'               => SYSTEM_PATH . 'CLI/CLI.php',
            'YP\Loader'                => SYSTEM_PATH . 'Loader.php',
            'YP\Controller'            => SYSTEM_PATH . 'Controller.php',
            'YP\Config\AutoloadConfig' => SYSTEM_PATH . 'Config/Autoload.php',
            'YP\Config\Config'         => SYSTEM_PATH . 'Config/Config.php',
            'Zend\Escaper\Escaper'     => SYSTEM_PATH . 'ThirdParty/ZendEscaper/Escaper.php',
            'YP\Libraries\YP_Eloquent' => SYSTEM_PATH . 'Libraries/YP_Eloquent.php',
        ];
    }

}