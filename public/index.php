<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:33
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
// 前端资源目录
define('FRONT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

// 设置编码
header("Content-type:text/html;charset=utf-8");

//xhprof_enable(XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_CPU + XHPROF_FLAGS_NO_BUILTINS);
//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_NO_BUILTINS);

// 加载启动框架文件
$app = require  dirname(__DIR__) . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'Bootstrap.php';

// 运行框架
$app->run();
