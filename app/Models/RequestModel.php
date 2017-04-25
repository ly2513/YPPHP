<?php
/**
 * User: yongli
 * Date: 17/4/25
 * Time: 19:42
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace APP\Models;

/**
 * Class RequestModel 提供快捷的访问方式
 *
 * @package APP\Models
 */
class RequestModel
{
    /**
     * Get Param
     */
    private $get = NULL;

    /**
     * Post Param
     */
    private $post = NULL;

    /**
     * Request Param
     */
    private $request = NULL;

    /**
     * Request Socket(Json-Schema Object)
     */
    private $socket = NULL;

    /**
     * Request Socket Json Data
     *
     * 注意 此字段禁止直接使用
     */
    private $json = NULL;

    /**
     * Core/Input
     */
    private $input = NULL;

    private static $instance = NULL;

    /**
     * RequestModel constructor.
     */
    public function __construct()
    {
        $this->input =& load_class('Input', 'core');

        $this->initParam();
    }

    public function initParam()
    {
        $this->get  = $this->input->get();
        $this->post = $this->input->post();

        $content      = file_get_contents('php://input');
        $this->socket = json_decode($content, TRUE);
        $this->json   = json_decode($content);
        $this->json   = $this->json ? $this->json : new STDclass();

        $this->request = array_merge($this->post, $this->get);

        $this->get    = new Request($this->get, ArrayObject::STD_PROP_LIST);
        $this->post   = new Request($this->post, ArrayObject::STD_PROP_LIST);
        $this->socket = new Request($this->socket ? $this->socket : [], ArrayObject::STD_PROP_LIST);
    }

    public function __get($key)
    {
        switch ($key) {
            case 'get':
                return $this->get;
                break;
            case 'post':
                return $this->post;
                break;
            case 'socket':
                return $this->socket;
            case 'json':
                return $this->json;
            default:
                break;
        }
        return isset($this->request[$key]) ? $this->request[$key] : NULL;
    }

    public static function &get_instance()
    {
        if (!(self::$instance instanceof Request_model)) {
            self::$instance = new Request_model();
        }
        return self::$instance;
    }
}