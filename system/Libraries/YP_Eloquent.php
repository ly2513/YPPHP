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
use Config\Database;

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
        $config        = $this->_initDB();
        $this->capsule->addConnection($config);
        $this->capsule->bootEloquent();

    }

    /**
     * 初始化数据
     *
     * @return array|Database
     */
    private function _initDB()
    {
        $db = new Database();
        $db = (array)$db;
        return $db;
    }

    public function getCapsule()
    {
        return $this->capsule;
    }
}