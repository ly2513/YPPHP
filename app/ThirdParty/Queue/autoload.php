<?php
/**
 * Created by IntelliJ IDEA.
 * User: yongli
 * Date: 16/9/29
 * Time: 下午1:56
 * Email: liyong@addnewer.com
 */
if(!defined('APPLICATION_ROOT')) { die('Access Denied'); }


function do_queue_load($class)
{
    if($class) {
        $file = str_replace('\\', '/', $class);
        
        if (strpos($file, 'CIModel') !== FALSE) {
            $file      = str_replace('CIModel/', '', $file);
            $modelFile = APPLICATION_ROOT . 'application/models/' . $file . '.php';
            if (file_exists($modelFile)) {
                require $modelFile;
            }
        }
        if($file == 'MY_model') {
            $coreFile = APPLICATION_ROOT . 'application/core/' . $file . '.php';
            if (file_exists($coreFile)) {
                require $coreFile;
            }
        }
    }
}
//spl_autoload_register('do_queue_load');
