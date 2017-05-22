<?php defined('ROOT_PATH') OR exit('No direct script access allowed'); ?>
/**
* User: yongli
* Date: 17/5/17
* Time: 11:05
* Email: yong.li@szypwl.com
* Copyright: 深圳优品未来科技有限公司
*/
An uncaught Exception was encountered

Type:        <?= get_class($exception), "\n"; ?>
Message:     <?= $message, "\n"; ?>
Filename:    <?= $exception->getFile(), "\n"; ?>
Line Number: <?= $exception->getLine(); ?>

<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>

    Backtrace:
    <?php foreach ($exception->getTrace() as $error): ?>
        <?php if (isset($error['file'])): ?>
            <?= trim('-'. $error['line'] .' - '. $error['file'] .'::'. $error['function']) ."\n" ?>
        <?php endif ?>
    <?php endforeach ?>

<?php endif ?>
