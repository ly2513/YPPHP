<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 00:10
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

class Filters
{
    // 方便更好的阅读，更简单的改变使用的脚本
    public $aliases = [
        'csrf'    => \App\Libraries\YP_CSRF::class,
        'toolbar' => \App\Libraries\YP_DebugToolbar::class,
    ];

    // 总是在每次请求之前应用
    public $globals = [
        'before' => [
            // 'csrf'
        ],
        'after'  => [
            'toolbar'
        ]
    ];

    // Works on all of a particular HTTP method
    // (GET, POST, etc) as BEFORE filters only
    //     like: 'post' => ['CSRF', 'throttle'],
    // 给特定的http 方法进行before 过滤
    public $methods = [];

    // List filter aliases and any before/after uri patterns
    // that they should run on, like:
    //    'isLoggedIn' => ['before' => ['account/*', 'profiles/*']],
    //
    public $filters = [];
}