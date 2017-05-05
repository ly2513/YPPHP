<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 11:46
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
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
     * 加载自动验证
     *
     * @param \Config\Validation|null $config
     * @param bool                    $getShared
     *
     * @return mixed|\YP\Libraries\YP_Validation
     */
    //    public static function validation(\Config\Validation $config = null, $getShared = true)
    //    {
    //        if ($getShared) {
    //            return parent::getSharedInstance('validation', $config);
    //        }
    //        if (!is_object($config)) {
    //            $config = new \Config\Validation();
    //        }
    //
    //        return new \YP\Libraries\YP_Validation($config);
    //    }
}