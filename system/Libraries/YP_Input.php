<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 19:42
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

/**
 * Class RequestModel 提供快捷的访问方式
 *
 * @package APP\Models
 */
class YP_Input
{

    /**
     * Request 参数
     */
    private $request = [];

    /**
     * 存放get、post、put、delete的参数
     *
     * @var array|Input
     */
    private $socket = [];

    /**
     * 请求Socket Json数据
     *
     * 注意 此字段禁止直接使用
     */
    private $json = null;

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * RequestModel constructor.
     */
    public function __construct()
    {
        $this->getAllParam();
        // 得到json的验证数据
        $this->json    = json_decode(json_encode($this->socket));
        $this->json    = $this->json ? $this->json : new \STDclass();
        $this->request = $this->socket;
        $this->socket  = new Input($this->socket, \ArrayObject::STD_PROP_LIST);
    }

    /**
     * 魔术方法get
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        switch ($key) {
            case 'socket':
                return $this->socket;
                break;
            case 'json':
                return $this->json;
            default:
                break;
        }

        return isset($this->request[$key]) ? $this->request[$key] : null;
    }

    /**
     * 获得YP_Input对象
     *
     * @return null|YP_Input
     */
    public static function &get_instance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new YP_Input();
        }

        return self::$instance;
    }

    /**
     * 获得所有的参数,以便支持RESTful风格的路由设计
     */
    protected function getAllParam()
    {
        static  $_PUT = [];
        static $_DELETE = [];
        // 获得put的参数
        if ('PUT' == $_SERVER['REQUEST_METHOD']) {
            $_PUT = file_get_contents('php://input');
            if ($param = json_decode($_PUT, true)) {
                $_PUT = $param;
            } else {
                parse_str(file_get_contents('php://input'), $_PUT);
            }
        }
        if ('DELETE' == $_SERVER['REQUEST_METHOD']) {
            $_DELETE = file_get_contents('php://input');
            if ($param = json_decode($_DELETE, true)) {
                $_DELETE = $param;
            } else {
                parse_str(file_get_contents('php://input'), $_DELETE);
            }
        }
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            $param = file_get_contents('php://input');
            if ($param = json_decode($param, true)) {
                $_GET = array_merge($_GET, $param);
            }
        }
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $param = file_get_contents('php://input');
            if ($param = json_decode($param, true)) {
                $_POST = [];
                $_POST = array_merge($_POST, $param);
            }
        }
        // 将所有的所有的数据存放到$_POST
        $this->socket = array_merge($_POST, $_GET, $_PUT, $_DELETE);
    }
}

/**
 * Class Input 将输出对象转化为数组
 *
 * @package YP\Libraries
 */
class Input extends \ArrayObject
{
    public function __construct(array $data, $type)
    {
        parent::__construct($data, $type);
        foreach ($data as $k => $val) {
            if (is_array($val)) {
                $this->offsetSet($k, new Input($val, $type));
            }
        }
    }

    /**
     * 魔术方法取数据
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }

    /**
     * 将 Request 字段转化递归转化为数组直接使用
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->getArrayCopy();
        foreach ($data as $k => $val) {
            if ($val instanceof Input) {
                $data[$k] = $val->toArray();
            }
        }

        return $data;
    }
}