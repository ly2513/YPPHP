<?php
/**
 * User: yongli
 * Date: 17/4/26
 * Time: 15:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Config;

class Email
{
    /**
     * 邮箱服务器
     *
     * @var string
     */
    public $host = 'smtp.exmail.qq.com';

    /**
     * 邮箱服务器端口
     *
     * @var int
     */
    public $port = 465;

    /**
     * 账户(需配置)
     * TODO 该邮箱为实际的邮箱账号
     *
     * @var string
     */
    public $username = 'noreply@addnewer.com';

    /**
     * 密码(需配置)
     * TODO 该密码为实际的邮箱密码
     *
     * @var string
     */
    public $password = 'nWaybb74rLYm';

    /**
     * 账户名称,主要显示
     * TODO 该账户名称主要用于显示
     *
     * @var string
     */
    public $name = '深圳优品未来';

    /**
     * 加密方式
     *
     * @var string
     */
    public $smtpSecure = 'ssl';

    /**
     * 编码
     *
     * @var string
     */
    public $charset = 'UTF-8';

    /**
     * 是否支持html
     *
     * @var bool
     */
    public $isHTML = TRUE;

    /**
     * 非html邮件客户端的纯文本正文
     *
     * @var string
     */
    public $AltBody = 'This is the body in plain text for non-HTML mail clients';

    /**
     * 连接类型
     *
     * @var string
     */
    public $connectType = 'ssl';

}
