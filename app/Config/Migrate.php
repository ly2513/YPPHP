<?php
/**
 * User: yongli
 * Date: 17/9/2
 * Time: 08:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

use YP\Config\BaseConfig;

class Migrations extends BaseConfig
{
    /**
     * 启用/禁用迁移
     * 出于安全原因，默认情况下迁移是禁用的。
     * 当您打算进行模式迁移时，应该启用迁移，并在完成后将其禁用。
     *
     * @var bool
     */
    public $enabled = false;

    /**
     * 迁移的文件的时间格式
     *
     * @var string
     */
    public $type = 'timestamp';


    /**
     * 记录迁移文件的数据库表
     * 这是将存储当前迁移状态的表的名称。当迁移运行时，它将存储在一个数据库表中，该系统处于迁移级别。
     * 然后比较迁移本表至配置[ 'migration_version ]如果他们不一致会迁移。这必须设置。
     *
     * @var string
     */
    public $table = 'migrations';

    /**
     * 当前迁移的版本
     *
     * @var int
     */
    public $currentVersion = 0;

    /**
     * 迁移文件存储目录
     *
     * @var string
     */
    public $migratePath = APP_PATH . '/ThirdParty/Migrate';

}
