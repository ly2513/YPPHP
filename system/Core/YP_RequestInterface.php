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
 * 请求接口类
 *
 * Interface YP_RequestInterface
 *
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
    
    /**
     * 获得用户的IP地址
     *
     * @return string
     */
    public function getIPAddress(): string;

    /**
     * 校验IP地址
     *
     * @param string      $ip IP地址
     * @param string|null $which IP协议: ipv4、ipv6
     *
     * @return bool
     */
    public function isValidIP(string $ip, string $which = null): bool;
}
