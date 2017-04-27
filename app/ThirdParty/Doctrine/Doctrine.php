<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
//namespace Doctrine;

class Doctrine
{
    // Doctrine实体管理器
    public $em = null;

    public function __construct()
    {
        //加载Doctrine的一些类
        $doctrineClassLoader = new \Doctrine\Common\ClassLoader('Doctrine', APP_PATH . 'ThirdParty/Doctrine/');
        $doctrineClassLoader->register();
        
        //加载Symfony2的帮助类
        $symfonyClassLoader = new \Doctrine\Common\ClassLoader('Symfony', APP_PATH . 'ThirdParty/Doctrine');
        $symfonyClassLoader->register();
        
        //加载实体
        $entityClassLoader = new \Doctrine\Common\ClassLoader('Entity', APP_PATH . 'ThirdParty/Doctrine/Entity');
        $entityClassLoader->register();
        
        //加载代理实体
        $proxyClassLoader = new \Doctrine\Common\ClassLoader('Proxies', APP_PATH . 'ThirdParty/Doctrine/Entity');
        $proxyClassLoader->register();
        
        //设置一些配置
        $config = new \Doctrine\ORM\Configuration;
        $cache = new \Doctrine\Common\Cache\ArrayCache;
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        
        //设置代理配置
        $config->setProxyDir(APP_PATH . 'ThirdParty/Proxies');
        $config->setProxyNamespace('Proxies');
        
        //在开发模式下，自动生成代理类
        $config->setAutoGenerateProxyClasses(ENVIRONMENT == 'dev');
        
        //设置注解驱动
        $yamlDriver = new \Doctrine\ORM\Mapping\Driver\YamlDriver(APP_PATH . 'ThirdParty/Doctrine/Mappings');
        $config->setMetadataDriverImpl($yamlDriver);

        //读取数据库配置,
        require APP_PATH . 'Config/Database.php';
        $db = new Config\Database();
        $con = (array)$db->getDB();

        // 数据库连接信息
        $connectionOptions = [
            'driver'   => $con['doctrine']['dbdriver'],
            'user'     => $con['doctrine']['username'],
            'password' => $con['doctrine']['password'],
            'host'     => $con['doctrine']['hostname'],
            'dbname'   => $con['doctrine']['database'],
            'charset'  => $con['doctrine']['char_set'],
        ];
        //创建实体管理器
        $em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);
        //将实体管理器保存为一个成员，在YP的控制器中使用
        $this->em = $em;

    }
}
