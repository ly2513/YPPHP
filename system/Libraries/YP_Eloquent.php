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
        // 初始化数据库配置
        $this->_initDB();
    }

    /**
     * 初始化Eloquent
     */
    public function initEloquent()
    {
        // 加载软删除设置
        require SYSTEM_PATH . 'Libraries/YP_SoftDelete.php';
        $this->capsule = new Capsule;

    }

    /**
     * 初始化数据
     *
     * @return array|Database
     */
    private function _initDB()
    {
        $db = new Database();
        $db = $db->getDB();
        foreach ($db as $key => $dbConfig) {
            if ($key != 'doctrine') {
                $this->capsule->addConnection($dbConfig, $key);
            }
        }
        $this->capsule->bootEloquent();

    }

    public function getCapsule()
    {
        return $this->capsule;
    }
}