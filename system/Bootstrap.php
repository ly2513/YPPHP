<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 09:51
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
// 设置内存
//ini_set('memory_limit','300M');
// 定义根路径
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// 定义应用路径
define('APP_PATH', rtrim(realpath($paths->appDirectory), '/') . DIRECTORY_SEPARATOR);

// 定义框架路径
define('SYSTEM_PATH', rtrim(realpath($paths->systemDirectory), '/') . DIRECTORY_SEPARATOR);

// 定义测试路径
define('TEST_PATH', rtrim(realpath($paths->testsDirectory), '/') . DIRECTORY_SEPARATOR);

// 重写目录
define('WRITE_PATH', rtrim(realpath($paths->writeDirectory), '/') . DIRECTORY_SEPARATOR);

// 缓存目录
define('CACHE_PATH', rtrim(realpath($paths->cacheDirectory), '/') . DIRECTORY_SEPARATOR);

// 定义应用的命名空间
define('APP_NAMESPACE', 'App');
// 定义退出的常量的状态码
//defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS',        0); // 没出错
//defined('EXIT_ERROR')          || define('EXIT_ERROR',          1); // 一般错误
//defined('EXIT_CONFIG')         || define('EXIT_CONFIG',         3); // 配置错误
//defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE',   4); // 文件没找到错误
//defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS',  5); // 不知道类
//defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // 不知道类的成员方法
//defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT',     7); // 无效的用户输入
//defined('EXIT_DATABASE')       || define('EXIT_DATABASE',       8); // 数据库错误
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN',      9); // 最低自动分配错误
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX',    125); // 最高自动分配错误

// 定义composer的自动加载文件路径
define('COMPOSER_PATH', ROOT_PATH . 'vendor/autoload.php');

// 加载函数库
require SYSTEM_PATH . 'Functions.php';

// 加载框架的自动加载
require SYSTEM_PATH . 'Autoload.php';
require APP_PATH . 'Config/Autoload.php';
require APP_PATH . 'Config/Services.php';

// 初始化自动加载配置
$loader = Config\Services::autoload();
$loader->initialize(new Config\Autoload());
$loader->register();

// 加载composer的组件
if (file_exists(COMPOSER_PATH)) {
    require COMPOSER_PATH;
}

// 加载自定义函数
require APP_PATH . 'Functions/Function.php';

// 加载Eloquent
new \YP\Libraries\YP_Eloquent();


// 启动应用加载框架
$app = new  \YP\YP(new \Config\App());
// 初始化
$app->initialize();

return $app;