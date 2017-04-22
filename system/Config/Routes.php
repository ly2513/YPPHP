<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 18:16
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
/**
 * URI路由系统
 *
 * 此文件包含对系统工具的任何路由，如迁移的命令行工具等
 *
 */
// 迁移路由规则
$routes->cli('migrations/(:segment)/(:segment)', '\YP\Commands\MigrationsCommand::$1/$2');
$routes->cli('migrations/(:segment)', '\YP\Commands\MigrationsCommand::$1');
$routes->cli('migrations', '\YP\Commands\MigrationsCommand::index');
// 命令行路由规则
$routes->cli('ci(:any)', '\YP\CLI\CommandRunner::index/$1');