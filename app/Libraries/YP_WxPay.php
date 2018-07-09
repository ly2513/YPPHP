<?php
/**
 * User: yongli
 * Date: 18/6/9
 * Time: 10:38
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Libraries;

/**
 * 微信小程序支付类
 *
 * Class YP_WxPay
 * 
 * @package App\Libraries
 */
class YP_WxPay
{
    /**
     * AppId和app_secret在开通微信支付后接收到的邮件里面可看到
     *
     * @var string
     */
    private $app_id = '';

    /**
     * 商户号
     *
     * @var string
     */
    private $mch_id = '';

    /**
     * 支付的签名，32位签名，微信商户后台设置
     *
     * @var string
     */
    private $makeSign = '';

    /**
     * 回调地址
     *
     * @var string
     */
    private $notifyUrl = '';// 本控制器下面的 notifyurl  方法的URL路径 记得格式 是 http://......    【这是回调】

    /**
     * 支付接口地址
     *
     * @var string
     */
    private $payUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * 选择支付方式,记得填写授权目录
     *
     * @param $params
     *
     * @return string
     */
    public function actionWxHandle($params)
    {
        //这里写插入语句
        $conf = $this->payConfig($params['order_no'], $params['price'], '订单支付', $params['open_id']);
        if (!$conf || $conf['return_code'] == 'FAIL') {
            call_back(2, '', $conf['return_msg']);
        }
        //生成页面调用参数
        $jsApiObj['appId']     = $conf['appid'];
        $jsApiObj['timeStamp'] = time() . '';
        $jsApiObj['nonceStr']  = $this->createNonceStr();
        $jsApiObj['package']   = 'prepay_id=' . $conf['prepay_id'];
        $jsApiObj['signType']  = 'MD5';
        $jsApiObj['paySign']   = $this->makeSign($jsApiObj);

        return $jsApiObj;
    }

    /**
     * 回调地址
     */
    public function actionNotifyUrl()
    {
        $xml = file_get_contents("php://input");
        $log = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $id  = $log['out_trade_no'];  //获取单号
        //这里修改状态
        exit('SUCCESS');  //打死不能去掉
    }

    /**
     * 微信JS支付参数获取
     *
     * @param $no
     * @param $fee
     * @param $body
     * @param $open_id
     *
     * @return mixed
     */
    protected function payConfig($no, $fee, $body, $open_id)
    {
        $url                      = $this->payUrl;
        $data['appid']            = $this->app_id;
        $data['body']             = $body;
        $data['mch_id']           = $this->mch_id; //商户号
        $data['nonce_str']        = $this->createNoncestr();
        $data['notify_url']       = $this->notifyUrl;           //通知url
        $data['openid']           = $open_id;
        $data['out_trade_no']     = $no; //订单号
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['total_fee']        = $fee; //金额
        $data['trade_type']       = 'JSAPI';
        $data['sign']             = $this->makeSign($data);
        $xml                      = $this->toXml($data);
        $header                   = [
            "Accept: application/json",
            "Content-Type: application/json;charset=utf-8",
        ];
        $curl                     = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($curl, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        $tmpInfo = curl_exec($curl); // 执行操作
        curl_close($curl); // 关闭CURL会话
        $arr = $this->fromXml($tmpInfo);

        return $arr;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     *
     * @param int $length
     *
     * @return string
     */
    public function createNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str   = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     *
     * @param $length
     *
     * @return null|string
     */
    public function randomKeys($length)
    {
        $pattern = '1234567890123456789012345678905678901234';
        $key     = null;
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern{mt_rand(0, 30)};    //生成php随机数
        }

        return $key;
    }

    /**
     * 将xml转为array
     *
     * @param $xml
     *
     * @return mixed
     */
    public function fromXml($xml)
    {
        //将XML转为array
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 输出xml字符
     *
     * @param $arr
     *
     * @return string
     */
    public function toXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * 生成签名
     *
     * @param $arr
     *
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    protected function makeSign($arr)
    {
        // 签名步骤一：按字典序排序参数
        ksort($arr);
        $string = urldecode(http_build_query($arr));
        // 签名步骤二：在string后加入KEY
        $string = $string . '&key=' . $this->makeSign;
        // 签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }
}
