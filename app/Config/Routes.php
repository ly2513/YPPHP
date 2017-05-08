<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 16:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

    /**
     * URI路由
     * 此文件允许将URI请求映射到特定的控制器-方法中去
     * 例如:
     * example.com/class/method/id
     *
     */
// 实例化路由收集器对象
$routes = Services::routes(true);
// 先加载系统的路由文件，以便应用程序和环境可以根据需要重写
if (file_exists(SYSTEM_PATH . 'Config/Routes.php')) {
    require SYSTEM_PATH . 'Config/Routes.php';
}

/**
 * 启动路由
 *
 * RouteCollection对象允许您修改路由器的工作方式，以保持它的配置设置。可以在对象上调用下列方法来修改默认操作
 *
 * 如果它没有设置默认的控制器命名空间,可使用下面方法设置,默认情况下，这是全局命名空间（\）
 * $routes->defaultNamespace()
 *
 * 当路由指向一个目录而不是类时,改用作控制器的类的名称
 * $routes->defaultController()
 *
 * 当路由器无法确定适当的方法运行时，分配控制器内部的方法
 * $routes->defaultMethod()
 *
 * 设置自动路由
 * $routes->setAutoRoute()
 *
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/**
 * 路由定义,可以通过指定默认路由来提高性能，因为我们不需要扫描目录
 */
$routes->add('/', 'Home::index');

/**
 * 额外的路由设置
 * 可根据不同环境设置不同的路由规则
 *
 */
if (file_exists(APP_PATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APP_PATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

