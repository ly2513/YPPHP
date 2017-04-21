<?php
use YP\Config\Services;

/**
 * User: yongli
 * Date: 17/4/20
 * Time: 10:22
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
if (!function_exists('P')) {
    /**
     * 打印函数
     *
     * @param      $arr   打印的数据
     * @param bool $isDie 是否打断点
     */
    function P($arr, $isDie = false)
    {
        if (is_bool($arr)) {
            var_dump($arr);
        } elseif (is_null($arr)) {
            var_dump(null);
        } else {
            if (is_cli()) {
                print_r($arr);
            } else {
                echo "<pre style='position:relative;z-index:1000;padding:10px;border-radius:5px;background:#F5F5F5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;'>" . print_r($arr,
                        true) . "</pre>";
            }
        }
        if ($isDie) {
            die();
        }
    }
}

if (!function_exists('is_cli')) {
    /**
     * 判断是否为cli模式
     *
     * @return bool
     */
    function is_cli(): bool
    {
        return (PHP_SAPI === 'cli' || defined('STDIN'));
    }
}

if (! function_exists('helper'))
{
    /**
     * Loads a helper file into memory. Supports namespaced helpers,
     * both in and out of the 'helpers' directory of a namespaced directory.
     *
     * @param string|array $filenames
     *
     * @return string
     */
    /**
     * 加载帮助类
     *
     * @param $filenames
     */
    function helper($filenames)//: string
    {
        $loader = Services::locator(true);
        P($loader);

        if (! is_array($filenames))
        {
            $filenames = [$filenames];
        }

        foreach ($filenames as $filename)
        {
            if (strpos($filename, '_helper') === false)
            {
                $filename .= '_helper';
            }

            $path = $loader->locateFile($filename, 'Helpers');

            if (! empty($path))
            {
                include $path;
            }
        }
    }
}

