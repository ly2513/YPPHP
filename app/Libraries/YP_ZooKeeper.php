<?php

/**
 * User: yongli
 * Date: 17/9/28
 * Time: 13:46
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Libraries;

/**
 * zookeeper处理类
 *
 * Class YP_Zookeeper
 *
 * @package App\Libraries
 */
class YP_Zookeeper
{

    /**
     * Zookeeper对象
     *
     * @var Zookeeper
     */
    private $zookeeper;

    /**
     * Callback container
     *
     * @var array
     */
    private $callback = [];

    /**
     * YP_Zookeeper constructor.
     *
     * @param string $address CSV list of host:port values (e.g. "host1:2181,host2:2181")
     */
    public function __construct($address)
    {
        $this->zookeeper = new Zookeeper($address);
    }

    /**
     * 设置一个节点的值, 如果该节点不存在, 则创建, 存在将覆盖该节点的现有值。
     *
     * @param $path  节点的路径
     * @param $value 节点的值
     */
    public function set($path, $value)
    {
        if (! $this->zookeeper->exists($path)) {
            $this->makePath($path);
            $this->makeNode($path, $value);
        } else {
            $this->zookeeper->set($path, $value);
        }
    }

    /**
     * 创建路径,相当于在Zookeeper上执行 "mkdir -p"
     *
     * @param $path  节点的路径
     * @param string $value 分配给每个新节点沿路径的值
     */
    public function makePath($path, $value = '')
    {
        $parts   = explode('/', $path);
        $parts   = array_filter($parts);
        $subPath = '';
        while (count($parts) > 1) {
            $subPath .= '/' . array_shift($parts);
            if (! $this->zookeeper->exists($subPath)) {
                $this->makeNode($subPath, $value);
            }
        }
    }

    /**
     * 在Zookeeper给定的路径下,创建一个节点
     *
     * @param $path   节点的路径
     * @param $value  节点的值
     * @param array $params 对于Zookeeper节点是可选参数。默认情况下，创建一个公共节点
     *
     * @return string 返回新创建的节点有效、无效的路径
     */
    public function makeNode($path, $value, array $params = [])
    {
        if (empty($params)) {
            $params = [
                       [
                        'perms'  => Zookeeper::PERM_ALL,
                        'scheme' => 'world',
                        'id'     => 'anyone',
                       ],
                      ];
        }

        return $this->zookeeper->create($path, $value, $params);
    }

    /**
     * 获取节点的值
     *
     * @param $path 节点的路径
     *
     * @return null
     */
    public function get($path)
    {
        if (! $this->zookeeper->exists($path)) {
            return null;
        }

        return $this->zookeeper->get($path);
    }

    /**
     * 列出给定路径的子节点，即当前节点中目录的名称，如果有的话
     *
     * @param $path 节点的路径
     *
     * @return mixed
     */
    public function getChildren($path)
    {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, -1);
        }

        return $this->zookeeper->getChildren($path);
    }

    /**
     * 如果给定的节点没有任何子节点, 就该删除节点
     *
     * @param $path 节点路径
     *
     * @return null
     */
    public function deleteNode($path)
    {
        if (! $this->zookeeper->exists($path)) {
            return null;
        } else {
            return $this->zookeeper->delete($path);
        }
    }

    /**
     * 监听给定的路径的节点
     *
     * @param $path     节点路径
     * @param $callback 回调方法
     *
     * @return null
     */
    public function watch($path, $callback)
    {
        if (! is_callable($callback)) {
            return null;
        }
        if ($this->zookeeper->exists($path)) {
            if (! isset($this->callback[$path])) {
                $this->callback[$path] = [];
            }
            if (! in_array($callback, $this->callback[$path])) {
                $this->callback[$path][] = $callback;

                return $this->zookeeper->get($path, [$this, 'watchCallback']);
            }
        }
    }

    /**
     * 监听事件回调
     *
     * @param $event_type 事件类型
     * @param $stat
     * @param $path
     *
     * @return mixed|null
     */
    public function watchCallback($event_type, $stat, $path)
    {
        if (! isset($this->callback[$path])) {
            return null;
        }
        foreach ($this->callback[$path] as $callback) {
            $this->zookeeper->get($path, [$this, 'watchCallback']);

            return call_user_func($callback);
        }
    }

    /**
     * 删除给定节点的 监听回调事件,当 $callback 为 null 时,删除所有的回调
     *
     * @param $path     节点路径
     * @param null $callback 回调
     *
     * @return bool|null
     */
    public function cancelWatch($path, $callback = null)
    {
        if (! isset($this->callback[$path])) {
            return null;
        }
        if (empty($callback)) {
            unset($this->callback[$path]);
            $this->zookeeper->get($path); //reset the callback
            return true;
        } else {
            $key = array_search($callback, $this->callback[$path]);
            if ($key !== false) {
                unset($this->callback[$path][$key]);

                return true;
            } else {
                return null;
            }
        }
    }
}
