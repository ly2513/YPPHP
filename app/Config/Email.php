<?php
/**
 * User: yongli
 * Date: 17/4/26
 * Time: 15:13
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

/**
 * 邮件配置类
 *
 * Class Email
 *
 * @package Config
 */
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
    public $username = '';

    /**
     * 密码(需配置)
     * TODO 该密码为实际的邮箱密码
     *
     * @var string
     */
    public $password = '';

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
    public $isHTML = true;

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
