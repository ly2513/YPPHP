<?php
/**
 * User: yong.li
 * Date: 2018/8/1
 * Time: 下午3:17
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Libraries\Crontab\Interfaces;

interface MiddlewareInterface
{
    /**
     * 入队
     *
     * @param  string $key  队列key
     * @param  mixed  $data 数据
     *
     * @return boolean
     */
    public function push($key, $data);

    /**
     * 出队
     *
     * @param  string $key 队列key
     *
     * @return string
     */
    public function pop($key);

    /**
     * 获取队列里的消息数量
     * @return interge
     */
    public function getMessageNum();

    /**
     * 关闭连接
     */
    public function close();
}
