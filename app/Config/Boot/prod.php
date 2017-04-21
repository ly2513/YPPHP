<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 16:33
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
// 关闭所有PHP的报错信息
ini_set('display_errors', 0);
// 报错级别
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

// 调试模式 0:关闭调试 1:开始调试
define('YP_DEBUG', 0);