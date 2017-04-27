<?php
/**
 * User: yongli
 * Date: 17/4/27
 * Time: 11:25
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */

date_default_timezone_set('PRC');

function do_yp_load($class)
{
    if ($class) {
        $file           = str_replace('\\', '/', $class);
        $file = APP_PATH . 'Cli/' . $file . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }

}

spl_autoload_register('do_yp_load');

//加载 Doctrine 以及 Doctrine 的 DB连接
require APP_PATH . 'ThirdParty/Doctrine/Doctrine.php';

// 加载队列所需要的model
//require APP_PATH . 'ThirdParty/Queue/autoload.php';


//$doctrine  = new \ThirdParty\Doctrine\Doctrine();
//$doctrine  = new Doctrine\Doctrine();
$doctrine  = new Doctrine();
$helperSet = new \Symfony\Component\Console\Helper\HelperSet(
    [
        'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($doctrine->em->getConnection()),
        'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($doctrine->em),
    ]
);