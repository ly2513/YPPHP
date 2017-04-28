<?php
/**
 * User: yongli
 * Date: 17/4/26
 * Time: 13:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use Config\Services;

class YP_JsonSchema
{
    /**
     * json验证文件路径
     *
     * @var null
     */
    private $path = null;

    /**
     * 路由
     *
     * @var null
     */
    private $router = null;

    /**
     * 当前使用的控制器类
     *
     * @var null
     */
    private $class = null;

    /**
     * 当前调用的方法
     *
     * @var null
     */
    private $method = null;

    /**
     * 控制器所在的子目录
     *
     * @var string
     */
    private $directory = '';

    /**
     * json_schema校验对象
     *
     * @var null
     */
    private $validator = null;

    /**
     *
     *
     * @var null
     */
    private $schema = null;

    /**
     * 错误信息
     *
     * @var string
     */
    private $errMsg = '';

    /**
     * 错误码
     *
     * @var int
     */
    private $errCode = 0;

    /**
     * 错误不存在常量
     */
    const ERROR_NO_EXISTS = -1;

    /**
     * json_schema 校验错误常量
     */
    const ERROR_SCHEMA_ERROR = 3;

    /**
     * YP_JsonSchema constructor.
     */
    public function __construct()
    {
        $this->init();
        $this->loadSchema();
    }

    /**
     * 规定自定义的路径
     */
    private function init()
    {
        $this->router    = Services::router();
        $this->directory = $this->router->directory();
        $controller      = explode('\\', $this->router->controllerName());
        $this->class     = end($controller);
        $this->method    = $this->router->methodName();
        $basicPath       = APP_PATH . 'ThirdParty/Json_Schema/';
        $this->path      = $basicPath . $this->directory . $this->class . '/' . $this->method . '.json';
    }

    private function loadSchema()
    {
        $this->validator = new \JsonSchema\Validator();
        if (file_exists($this->path)) {
            $this->schema = file_get_contents($this->path);
        } else {
            $this->errCode = self::ERROR_NO_EXISTS;
        }
    }

    private function loadSchemaFile($path)
    {
        $fp      = fopen($path, 'r');
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp);
        }
        fclose($fp);

        return $content;
    }

    public function getSchemaPath()
    {
        return $this->path;
    }

    /**
     * 进行字段验证
     *
     * @param \StdClass $jsonData
     *
     * @return bool
     */
    public function check(\StdClass $jsonData)
    {
        if ($this->errCode == self::ERROR_NO_EXISTS) {
            $this->errMsg = 'Can Not Found Json Schema File. The ' . $this->path . ' file or directory does not exist';
        } else {
            $schemaData = json_decode($this->schema);
            if (!$schemaData) {
                $this->errCode = self::ERROR_SCHEMA_ERROR;
                $this->errMsg  = 'Invalid Json Schema Data, Please Check Schema JsonData';

                return false;
            }
            $this->validator->check($jsonData, $schemaData);
            if (!$this->validator->isValid()) {
                foreach ($this->validator->getErrors() as $error) {
                    $this->errMsg .= sprintf("%s %s\n", $error['property'], $error['message']) . "\r\n";
                }
            }
        }
    }

    /**
     * 判断当前字段是否可用
     */
    public function isValid()
    {
        return $this->errMsg ? false : true;
    }

    public function error()
    {
        return $this->errMsg;
    }
}
