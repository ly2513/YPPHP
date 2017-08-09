<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:38
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDelete;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class YP_Model 基类控制器
 *
 * @package YP\Core
 */
class YP_Model extends Model
{
    // 开启软删除
    use SoftDelete;
    // 定义软删除字段
    const  DELETED_AT =   'is_delete';

    protected $dates =   ['deleted_at'];

    // 处理 Eloquent 的自动维护db 列
    const  CREATED_AT =   'create_time';
    const  UPDATED_AT =   'update_time';

    // 设置create_at/update_at 时间格式为 Unix 时间戳,默认为 DateTime 格式数据
    protected  $dateFormat =   'U';

    /**
     * 批量更新数据
     *
     * @param array  $multipleData    更新的数据
     * @param string $referenceColumn 更新条件字段名
     * @param string $table           表名
     *
     * @return bool
     */
    public static function batchUpdate($multipleData = [], $referenceColumn = '', $table = '')
    {
        if (empty($multipleData)) {
            return false;
        }
        // column or fields to update
        $updateColumn = array_keys($multipleData[0]);
        $whereIn      = "";
        $q            = "UPDATE " . $table . " SET ";
        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn . " = CASE ";
            foreach ($multipleData as $data) {
                $q .= "WHEN " . $referenceColumn . " = " . $data[$referenceColumn] . " THEN '" . $data[$uColumn] . "' ";
            }
            $q .= "ELSE " . $uColumn . " END, ";
        }
        foreach ($multipleData as $data) {
            $whereIn .= "'" . $data[$referenceColumn] . "', ";
        }
        $q = rtrim($q, ", ") . " WHERE " . $referenceColumn . " IN (" . rtrim($whereIn, ', ') . ")";

        // Update
        return DB::update(DB::raw($q));
    }

}
