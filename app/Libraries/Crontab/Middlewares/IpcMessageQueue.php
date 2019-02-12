<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Libraries\Crontab\Middlewares;

use Libraries\Crontab\Interfaces\MiddlewareInterface;

class IpcMessageQueue implements MiddlewareInterface
{
    /**
     * 队列资源句柄
     *
     * @var result
     */
    private $handle;

    /**
     * 每次读取数据大小
     *
     * @var integer
     */
    public $readSize = 65535;

    /**
     * 是否阻塞 $blocking 为true: 等待另一个进程读取并释放足够的空间以方便发送消息；
     * false: 消息队列太大，发送消息失败，重试。可有效解决阻塞问题
     *
     * @var boolean
     */
    public $blocking = false;

    public function __construct($key, $blocking = false)
    {
        $this->handle   = msg_get_queue($key);
        $this->blocking = $blocking;
    }

    /**
     * 入队
     *
     * @param  string $key  队列key
     * @param  mixed  $data 数据
     *
     * @return boolean
     */
    public function push($key, $data)
    {
        return msg_send($this->handle, $key, $data, false, $this->blocking, $errcode);
    }

    /**
     * 出队(非阻塞式)
     *
     * @param  string $key 队列key
     *
     * @return string/false
     */
    public function pop($key)
    {
        $message = false;
        if ($this->blocking) {
            msg_receive($this->handle, $key, $type, $this->readSize, $message, false);
        } else {
            msg_receive($this->handle, $key, $type, $this->readSize, $message, false, MSG_IPC_NOWAIT);
        }
        return $message;
    }

    /**
     * 获取队列里的消息数量
     *
     * @return mixed
     */
    public function getMessageNum()
    {
        $stat = msg_stat_queue($this->handle);
        return $stat['msg_qnum'];
    }
    
    /**
     * 关闭连接
     */
    public function close()
    {
        msg_remove_queue($this->handle);
    }
}