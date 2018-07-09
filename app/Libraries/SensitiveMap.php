<?php
/**
 * User: yongli
 * Date: 2018/3/23
 * Time: 下午5:14
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Libraries;

/**
 * 敏感词映射类
 *
 * Class SensitiveMap
 *
 * @package App\Libraries
 */
class SensitiveMap
{
    /**
     * 获取词
     *
     * @param $key
     *
     * @return null
     */
    public function get($key)
    {
        return isset($this->$key) ? $this->$key : null;
    }

    /**
     * 修改词
     *
     * @param $key
     * @param $value
     */
    public function put($key, $value)
    {
        $this->$key = $value;
    }

}