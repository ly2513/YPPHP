<?php
/**
 * User: yongli
 * Date: 2018/7/9
 * Time: 下午5:34
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

class Pay
{
    /**
     * AppId和app_secret在开通微信支付后接收到的邮件里面可看到
     *
     * @var string
     */
    public $wx_app_id = '';

    /**
     * 微信的商户号
     *
     * @var string
     */
    public $wx_mch_id = '';

    /**
     * 支付的签名，32位签名，微信商户后台设置
     *
     * @var string
     */
    public $wx_make_sign = '';

    /**
     * 微信的回调地址
     *
     * @var string
     */
    public $wx_notify_url = '';

    /**
     * 微信支付接口
     *
     * @var string
     */
    public $payUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
     *
     * @var string
     */
    public $ali_key = '';

    /**
     * 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
     *
     * @var string
     */
    public $ali_notify_url = '/Wine/PayCallback/alipay_notify';

    /**
     * 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
     *
     * @var string
     */
    public $ali_return_url = 'Wine/Orders/alipay_return';

}