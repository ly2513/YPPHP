<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:56
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;
use YP\Core\YP_Hooks as Hooks;

/**
 * 运用钩子,在这里可以添加你需要的钩子事件
 *
 * 钩子允许在执行程序的同时不修改或扩展核心文件。此文件提供了一个定义钩子的中心位置，尽管它们可以在运行时添加，也可以在需要时添加。
 * 你创建代码，可以执行通过订阅的on()法事件。它接受任何可调用的形式，包括闭包，当钩子被触发时将被执行。
 * 例如:
 *      Hooks::on('create', [$myInstance, 'myMethod']);
 */

// 调试工具栏侦听器。如果删除，将不再收集它们。
if (ENVIRONMENT != 'prod')
{
    Hooks::on('DBQuery', 'YP\Debug\Toolbar\Collectors\Database::collect');
}