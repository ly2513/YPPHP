<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 11:46
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

require SYSTEM_PATH . 'Config/Services.php';

/**
 * Class Services  服务类
 *
 * @package Config
 */
class Services extends \YP\Config\Services
{
    // TODO 你可以在这里添加你需要加载的的类库,以下仅供参考
    /**
     * 队列
     *
     * @param Queue|null $config
     * @param bool       $getShared
     *
     * @return \App\Libraries\YP_Queue|mixed
     */
    public static function queue(\Config\Queue $config = null, $getShared = true)
    {
        if ($getShared) {
            return parent::getSharedInstance('queue', $config);
        }
        if (! is_object($config)) {
            $config = new \Config\Queue();
        }

        return new \App\Libraries\Queue\YP_Queue($config);
    }
}
