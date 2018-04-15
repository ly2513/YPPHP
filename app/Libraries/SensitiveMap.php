<?php
/**
 * Created by IntelliJ IDEA.
 * User: yongli
 * Date: 2018/3/23
 * Time: 下午5:14
 */
namespace App\Libraries;

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