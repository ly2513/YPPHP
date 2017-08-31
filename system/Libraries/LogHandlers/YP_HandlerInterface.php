<?php
/**
 * User: yongli
 * Date: 17/8/31
 * Time: 23:39
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries\LogHandlers;

/**
 * 日志处理接口
 *
 * Interface HandlerInterface
 * @package YP\Libraries\LogHandlers
 */
interface YP_HandlerInterface
{
    /**
     * 处理记录消息。
     * 如果处理程序返回false，则处理程序将停止执行。尚未运行的任何处理程序都不会运行。
     *
     * @param $level
     * @param $message
     *
     * @return bool
     */
    public function handle($level, $message): bool;

    /**
     * 检查处理程序是否处理此日志级别的日志项
     *
     * @param string $level
     *
     * @return bool
     */
    public function canHandle(string $level): bool;

    /**
     * 设置在日志记录时使用的首选日期格式。
     *
     * @param string $format
     *
     * @return mixed
     */
    public function setDateFormat(string $format);

}
