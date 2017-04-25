<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Debug;

/**
 * Class Timer 定时器
 *
 * @package YP\Debug
 */
class YP_Timer
{
    /**
     * 存放所有的定时器
     *
     * @var array
     */
    protected $timers = [];

    /**
     * 启动计时器运行
     * 可以对该方法进行多个调用，以便可以测量多个执行点
     *
     * @param string     $name 定时器的名称
     * @param float|null $time
     *
     * @return YP_Timer
     */
    public function start(string $name, float $time = null): self
    {
        $this->timers[strtolower($name)] = [
            'start' => !empty($time) ? $time : microtime(true),
            'end'   => null,
        ];

        return $this;
    }

    /**
     * 停止一个运行的定时器
     * 如果在调用timers()方法之前没有停止该定时器，它将会在这自动停止。
     *
     * @param string $name
     *
     * @return YP_Timer
     */
    public function stop(string $name):self
    {
        $name = strtolower($name);
        if (empty($this->timers[$name])) {
            throw new \RuntimeException('Cannot stop timer: invalid name given.');
        }
        $this->timers[$name]['end'] = microtime(true);

        return $this;
    }

    /**
     * 返回记录计时器的持续时间
     *
     * @param string $name     定时器名称
     * @param int    $decimals 小数点位数
     *
     * @return float|null
     */
    public function getElapsedTime(string $name, int $decimals = 4)
    {
        $name = strtolower($name);
        if (empty($this->timers[$name])) {
            return null;
        }
        $timer = $this->timers[$name];
        if (empty($timer['end'])) {
            $timer['end'] = microtime(true);
        }

        return (float)number_format($timer['end'] - $timer['start'], $decimals);
    }

    /**
     * 获得所有的定时器
     * 返回定时器的数组，并为你预先计算时间
     *
     * @param int $decimals 小数点位数
     *
     * @return array
     */
    public function getTimers(int $decimals = 4)
    {
        $timers = $this->timers;
        foreach ($timers as &$timer) {
            if (empty($timer['end'])) {
                $timer['end'] = microtime(true);
            }
            $timer['duration'] = (float)number_format($timer['end'] - $timer['start'], $decimals);
        }

        return $timers;
    }

    /**
     * 检测是否存在指定的定时器
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name)
    {
        return array_key_exists(strtolower($name), $this->timers);
    }
}