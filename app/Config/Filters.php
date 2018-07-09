<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 00:10
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

/**
 * 过滤配置类
 *
 * Class Filters
 *
 * @package Config
 */
class Filters
{
    // 方便更好的阅读，更简单的改变使用的脚本
    public $aliases =
        [
         'csrf'    => \App\Libraries\YP_CSRF::class,
         'toolbar' => \App\Libraries\YP_DebugToolbar::class,
        ];

    // 总是在每次请求之前应用
    public $globals =
        [
         'before' =>
          [
            // 'csrf'
          ],
         'after'  =>
          [
            'toolbar'
          ]
        ];
    
    /**
     * 给特定的http 方法进行before 过滤
     *
     * 例如:
     * 'post' => ['CSRF', 'throttle'],
     *
     * @var array
     */
    public $methods = [];

    /**
     * 过滤器别名及before/after的URI参数列表
     * 例如:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']],
     *
     * @var array
     */
    public $filters = [];
}
