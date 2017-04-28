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

    private $socket = null;

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
        $this->initParam();
    }

    /**
     * 初始化参数
     */
    public function initParam()
    {
        $this->getAllParam();
        // 得到json的验证数据
        $this->json = json_decode(json_encode($this->socket));
        $this->json = $this->json ? $this->json : new \STDclass();
        $this->request = $this->socket;
        $this->socket    = new Input($this->socket, \ArrayObject::STD_PROP_LIST);
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

    public static function &get_instance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new YP_Input();
        }

        return self::$instance;
    }

    /**
     * 获得所有的参数
     */
    protected function getAllParam()
    {
        static $_PUT = [];
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
            $_POST = [];
            $param = file_get_contents('php://input');
            if ($param = json_decode($param, true)) {
                $_POST = array_merge($_POST, $param);
            }
        }
        // 将所有的所有的数据存放到$_POST
        $this->socket = array_merge($_POST, $_GET, $_PUT, $_DELETE);
        //         $this->stringToUtf8($_POST);
        $_POST = [];
    }

//    private function stringToUtf8(&$param)
//    {
//        if (is_null($param)) {
//            return [];
//        }
//        if (!is_array($param)) {
//            return [];
//        }
//        $params = [];
//        if (!is_null($param)) {
//            foreach ($param as $key => $value) {
//                if (is_array($value)) {
//                    $params[$key] = $this->stringToUtf8($value);
//                }
//                // 获得当前的编码
//                $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
//                // 将当前的字符串转换为utf8编码格式
//                $value = mb_convert_encoding($value, 'UTF-8', $encode);
//                //                P($value);
//                $params[$key] = $value;
//            }
//        }
//        P($params);
//
//        return $params;
//    }

//    public function getRequestParam()
//    {
//        return $this->request;
//    }

}

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

    public function __get($key)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }

    /**
     * 将 Request 字段转化递归转化为数组直接使用
     */
    public function toArray()
    {
        $finalData = [];
        $data      = $this->getArrayCopy();
        foreach ($data as $k => $val) {
            if ($val instanceof Input) {
                $data[$k] = $val->toArray();
            }
        }

        return $data;
    }
}