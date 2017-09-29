<?php
/**
 * User: yongli
 * Date: 17/9/29
 * Time: 16:20
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

/**
 * Interface YP_RequestInterface
 * @package YP\Core
 */
interface YP_RequestInterface
{
    /**
     * 获取请求方法
     *
     * @param bool $upper
     *
     * @return string
     */
    public function getMethod($upper = false): string;

    /**
     * 遍历$_SERVER超全局数组
     *
     * @param null $index
     * @param null $filter
     *
     * @return mixed
     */
    public function getServer($index = null, $filter = null);

}