<?php
/**
 * User: yongli
 * Date: 17/8/27
 * Time: 10:23
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
function do_thrift_click_load($class)
{
    if ($class) {
        $file     = str_replace('\\', '/', $class);
        $coreFile = APP_PATH . 'ThirdParty/' . $file . '.php';
        if (file_exists($coreFile)) {
            require $coreFile;
        }
    }
}

spl_autoload_register('do_thrift_click_load');