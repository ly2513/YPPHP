<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 16:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\SoftDelete;
use Config\Services;

/**
 * Class YP_Eloquent
 *
 * @package YP\Libraries
 */
class YP_Eloquent
{
    public $capsule = null;

    /**
     * YP_Eloquent constructor.
     */
    public function __construct()
    {
        $this->initEloquent();
    }

    /**
     * 初始化Eloquent
     */
    public function initEloquent()
    {
        $this->capsule = new Capsule;
        // 数据库配置
        $config = (array)Services::model();
        $this->capsule->addConnection($config);
        $this->capsule->bootEloquent();
    }

    public function initDB()
    {
        $config = (array)Services::model();
    }

    public function getCapsule()
    {
        return $this->capsule;
    }
}