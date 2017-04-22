<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:10
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */

// Migrations
$routes->cli('migrations/(:segment)/(:segment)', '\YP\Commands\MigrationsCommand::$1/$2');
$routes->cli('migrations/(:segment)',            '\YP\Commands\MigrationsCommand::$1');
$routes->cli('migrations',                       '\YP\Commands\MigrationsCommand::index');

// CLI Catchall - uses a _remap to
$routes->cli('ci(:any)', '\CodeIgniter\CLI\CommandRunner::index/$1');