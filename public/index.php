<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:33
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
date_default_timezone_set('Asia/Shanghai');

// 前端资源目录
define('FRONT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// 设置编码
header("Content-type:text/html;charset=utf-8");

// 加载目录类
require '../app/Config/Paths.php';

// 实例化一个目录对象
$paths = new Config\Paths();

// 加载启动框架文件
$app = require $paths->systemDirectory . DIRECTORY_SEPARATOR . 'Bootstrap.php';

// 运行框架
$app->run();

