<?php

/**
 * User: yongli
 * Date: 17/5/10
 * Time: 18:19
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
class CreditorRightModel extends \YP\Core\YP_Model
{
    protected $table = 'cd_creditor_rights';

    /**
     * 定义操作人员与机构一对一关系
     *
     * @return type
     */
    public function getAgency()
    {
        return $this->hasOne('AgencyUserModel', 'id', 'create_by');
    }
}