<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 09:29
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

class Paths
{
    
    /**
     * 框架目录
     * 该目录是框架目录,存放框架所有的资源文件
     *
     * @var string
     */
    public $systemDirectory = '../system';

    /**
     * 应用目录
     * 该目录是应用目录,开发者主要在该目录下进行开发工作
     *
     * @var string
     */
    public $appDirectory = '../app';

    /**
     * 测试目录
     * 该目录主要用于存放自测的类文件
     *
     * @var string
     */
    public $testsDirectory = '../tests';

    /**
     * 应用的入口目录
     * 如采用前后端分离开发,可以将前端的资源存放在该目录下
     *
     * @var string
     */
    public $publicDirectory = '../public';

    /**
     * 可写的目录
     *
     * @var string
     */
    public $writeDirectory =  '../writable';

    /**
     * 所有缓存目录
     *
     * @var string
     */
    public $cacheDirectory = '../cache';

}