<?php
/**
 * User: yongli
 * Date: 17/4/26
 * Time: 14:36
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
use Config\Email;

class YP_Email
{
    /**
     * email对象
     *
     * @var
     */
    private $mail;

    /**
     * email配置
     *
     * @var
     */
    private $mailConf;

    /**
     * 错误信息
     *
     * @var null
     */
    public $errorInfo = NULL;

    /**
     * YP_Email constructor.
     */
    public function __construct()
    {
        // 邮件配置
        $this->mailConf = new Email();
        $this->mailConf = (array)$this->mailConf;
        $this->initMailer();
    }

    /**
     * 初始化邮件对象
     */
    private function initMailer()
    {
        $this->mail = new \PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPAuth   = TRUE;
        $this->mail->SMTPSecure = $this->mailConf['connectType'];
        $this->mail->Host       = $this->mailConf['host'];
        $this->mail->Username   = $this->mailConf['username'];
        $this->mail->Password   = $this->mailConf['password'];
        $this->mail->Port       = $this->mailConf['port'];
        $this->mail->CharSet    = $this->mailConf['charset'];
        $this->mail->isHTML     = $this->mailConf['isHTML'];
        $this->mail->AltBody    = $this->mailConf['AltBody'];
        //回复邮件设置
        $this->mail->From     = $this->mailConf['username'];
        $this->mail->FromName = $this->mailConf['name'];
        $this->mail->addCC('');
        $this->mail->addBCC('');

        $this->mail->errorInfo = NULL;
    }

    /**
     * 发送邮件
     *
     * @param        $message
     * @param array  $user
     * @param string $title
     *
     * @return bool
     */
    public function sendEmail($message, array $user, $title = '优品未来')
    {
        //循环处理用户
        $this->mail->clearAddresses();

        foreach($user as $val) {
            $this->mail->addAddress($val);
        }

        $this->mail->Subject = $title;
        $this->mail->Body    = $message;

        try{
            if( !$this->mail->send()){
                $this->errorInfo = $this->mail->ErrorInfo;
                return FALSE;
            }
            return TRUE;
        }catch(Exception $e){
            $this->errorInfo = $e->getMessage();
            return FALSE;
        }
    }


}