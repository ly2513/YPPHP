<?php
/**
 * User: yongli
 * Date: 16/9/29
 * Time: 下午1:56
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
if (!defined('ROOT_PATH')) {
    die('Access Denied');
}
/**
 * 加载类
 *
 * @param $class
 *
 * @return bool
 */
function load_class($class)
{
    $className = str_ireplace('.php', '', $class);
    $files     = [
        APP_PATH . 'Libraries/',
        APP_PATH . 'Config/',
    ];
    $namespace = explode('\\', $className);
    foreach ($files as $file) {
        $file = $file . end($namespace) . '.php';
        if (strpos($className, 'Libraries') == 4) {
            if (file_exists($file) && !class_exists($className)) {
                require_once $file;
            }
        }else if (strpos($className, 'Config') == 0){
            if (file_exists($file) && !class_exists($className)) {
                require_once $file;
            }
        }

    }
}

spl_autoload_register('load_class');

