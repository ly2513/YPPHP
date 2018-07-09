<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 23:25
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace YP\Libraries\Cache;

class YP_Dummy
{
    /**
     * 初始化file缓存,除file缓存驱动外,redis、memcached在此方法中初始化
     */
    public function initialize()
    {
    }

    /**
     * 获取值
     *
     * @param string $key
     *
     * @return null
     */
    public function get(string $key)
    {
        return null;
    }

    /**
     * 检测文件是否可以写,其他处理缓存对象在此方法中会判断该驱动是否加载
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return true;
    }
}
