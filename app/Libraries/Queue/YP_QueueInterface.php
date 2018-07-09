<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 14:35
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Libraries\Queue;

/**
 * 队列接口
 *
 * Interface QueueInterface
 *
 * @package App\Libraries\Queue
 */
interface YP_QueueInterface {

    /**
     * 将消息从队列中取出
     *
     * @return mixed
     */
    public function pull();

    /**
     * 将消息推送到队列
     *
     * @return mixed
     */
    public function push();
}
