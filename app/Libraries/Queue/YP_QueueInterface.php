<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 14:35
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
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
