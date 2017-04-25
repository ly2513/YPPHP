<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 16:45
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Illuminate\Database\Eloquent;

/**
 * Class SoftDelete
 * 软删除方法
 * 通过重构软删除包中函数,强制限定 deleted_at 1/已删除 0/未删除
 *
 * @package YP\Libraries
 */
trait SoftDelete
{
    use SoftDeletes;

    /**
     * 创建软删除对象
     *
     * 如果要使用原始的逻辑,直接屏蔽此函数
     */
    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeleteScope);
    }

    /**
     * 判断当前数据是否被删除
     *
     * @return bool
     */
    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} ? true : false;
    }

    /**
     * 恢复被删除的数据
     *
     * @return bool/null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }
        $this->{$this->getDeletedAtColumn()} = SoftDeleteScope::DELETED_NORMAL;
        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;
        $result = $this->save();
        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Perform the actual delete query on this model instance
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());
        $this->{$this->getDeletedAtColumn()} = SoftDeleteScope::DELETED_NORMAL;
        $query->update([$this->getDeletedAtColumn() => SoftDeleteScope::DELETED_NORMAL]);
    }
}

/**
 * Class SoftDeletScope
 * 通过扩展 SoftDeletingScope 强制指定 deleted_at的值
 *
 * @package YP\Libraries
 */
class SoftDeleteScope extends SoftDeletingScope
{
    // 新增的关于deleted_at 值的定义
    const DELETED_NORMAL = 0;
    const DELETED_DEL    = 1;

    /**
     * 获取正常数据
     */
    public function apply(Builder $builder, Model $model)
    {
        $model = $builder->getModel();
        $builder->where($model->getQualifiedDeletedAtColumn(), '=', self::DELETED_NORMAL);
        $this->extend($builder);
    }

    /**
     * 只获取软删除数据
     *
     * @return bool
     */
    public function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->where($model->getQualifiedDeletedAtColumn(), '=', self::DELETED_DEL);

            return $builder;
        });
    }

    /**
     * 恢复被删除的数据
     *
     * @return bool
     */
    public function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withTrashed();

            return $builder->update([$builder->getModel()->getDeletedAtColumn() => self::DELETED_NORMAL]);
        });
    }

    /**
     * 软删除 delete
     *
     * @return bool/null
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);

            return $builder->update([
                $column => self::DELETED_DEL,
            ]);
        });
    }

}