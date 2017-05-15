<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\YP;
use YP\Config\Services;
use YP\Libraries\YP_Twig as Twig;
use YP\Core\YP_Log as Log;
use YP\Core\YP_Response as Response;
use YP\Libraries\YP_Validation as Validation;
use YP\Core\YP_IncomingRequest as IncomingRequest;

/**
 * Class Controller
 *
 * @package CodeIgniter
 */
class YP_Controller
{

    /**
     * 用于加载帮助函数
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * 请求对象
     *
     * @var Request
     */
    protected $request;

    /**
     * 响应对象
     *
     * @var Response
     */
    protected $response;

    /**
     * 日志对象
     *
     * @var Log
     */
    protected $logger;

    /**
     * 该控制器中的所有方法是否应强制HTTPS访问
     * 设置HSTS报头的秒数
     *
     * @var int
     */
    protected $forceHTTPS = 0;

    /**
     * 校验对象
     *
     * @var
     */
    protected $validator;

    /**
     * 模板对象
     *
     * @var
     */
    protected $twig;

    /**
     * 方法
     *
     * @var
     */
    protected $method;

    /**
     * 模板的后缀
     *
     * @var
     */
    protected $extension = '.html';

    /**
     * 模板目录
     *
     * @var
     */
    protected $tempPath;

    /**
     * json_schema 请求参数对象
     *
     * @var
     */
    protected $input;

    /**
     * jsonSchema对象
     *
     * @var
     */
    protected $jsonSchema;

    /**
     * 验证错误信息
     *
     * @var array
     */
    protected $errors = [];

    protected $serverObject;

    /**
     * 当前控制器所在的目录
     *
     * @var
     */
    protected $directory;

    /**
     * 当前控制器
     *
     * @var
     */
    protected $controller;

    /**
     * YP_Controller constructor.
     *
     */
    public function __construct(IncomingRequest $request, Response $response, Log $logger = null)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->logger   = is_null($logger) ? Services::log(true) : $logger;
        $this->logger->info('Controller "' . get_class($this) . '" loaded.');
        if ($this->forceHTTPS > 0) {
            $this->forceHTTPS($this->forceHTTPS);
        }
        $this->loadHelpers();
        $this->setJsonSchema();
        $this->setInput();
        $this->initialization();
        $this->initTwig();
    }

    /**
     * 初始化控制器,用于子类使用
     *
     */
    public function initialization()
    {

    }

    /**
     *
     * 该方法确保某个方法只通过https请求过来,如果不需要，那么一个重定向会回到这个方法并且HSTS报头将被发送到浏览器的请求会自动发生变换
     *
     * @param int $duration 这个链接的秒数应该被认为是安全的。只有用HSTS报头。默认值为1年
     */
    public function forceHTTPS(int $duration = 31536000)
    {
        force_https($duration, $this->request, $this->response);
    }

    /**
     * 缓存当前页码
     *
     * @param int $time
     */
    public function cachePage(int $time)
    {
        YP::cache($time);
    }

    /**
     * 加载帮助文件
     */
    protected function loadHelpers()
    {
        if (empty($this->helpers)) {
            return;
        }
        foreach ($this->helpers as $helper) {
            helper($helper);
        }
    }

    /**
     * 校验请求的参数
     * 如果校验失败,将错误存放到类的$error的属性上
     *
     * @param IncomingRequest $request
     * @param                 $rules
     * @param array|null      $messages
     *
     * @return bool
     */
    public function validate(IncomingRequest $request, $rules, array $messages = null): bool
    {
        $this->validator = Services::validation();
        // 校验路由
        $success = $this->validator->withRequest($request)->setRules($rules, $messages)->run();
        if (!$success) {
            $this->errors = $this->validator->getErrors();
        }

        return $success;
    }

    /**
     * 初始化Twig模板引擎
     */
    public function initTwig()
    {
        // TWig配置信息
        $config = new \Config\Twig();
        $config = (array)$config;
        // 获得当前路由信息
        $router           = Services::router();
        $this->directory  = $router->directory();
        $controller       = explode('\\', $router->controllerName());
        $this->controller = end($controller);
        $this->method     = $router->methodName();
        $this->extension  = $config['extension'] ?? $this->extension;
        // 模板目录
        $config['template_dir'] = $config['template_dir'];
        // 缓存目录
        $config['cache_dir'] = $config['cache_dir'] . $this->directory . $this->controller . '/';
        $this->twig          = new Twig($config);
        $this->tempPath      = $config['template_dir'];
    }

    /**
     * 视图渲染
     *
     * @param null  $htmlFile
     * @param array $data
     */
    public function display($htmlFile = null, $data = [])
    {
        // 修改模板名称
        if (!is_null($htmlFile)) {
            $this->method = $htmlFile;
        }
        $path = $htmlFile ? $htmlFile : $this->directory . $this->controller. '/' . $this->method;
        // 模板文件
        $tempFile     = $path . $this->extension;
        $tempFilePath = $htmlFile ? $tempFile :$this->tempPath . $tempFile;
        is_file($tempFilePath) or touch($tempFilePath);
        echo $this->twig->render($tempFile, $data);
        if (!YP_DEBUG) {
            die;
        }
    }

    /**
     * 分配变量到模板中
     *
     * @param      $var
     * @param null $value
     */
    public function assign($var, $value = null)
    {
        $this->twig->assign($var, $value);
        $this->twig->assign('FRONT_PATH', FRONT_PATH);
    }

    /**
     * json_schema 参数验证
     */
    public function setInput()
    {
        if (!is_object($this->input)) {
            $this->input = Services::input();
        }
    }

    /**
     * 初始化json_schema对象
     */
    public function setJsonSchema()
    {
        if (!is_object($this->jsonSchema)) {
            $this->jsonSchema = Services::schema();
        }
    }

    /**
     * 检查 API 请求参数
     */
    public function checkSchema()
    {
        \Config\Services::getObject()['schema']->check($this->input->json);
        if (!$this->jsonSchema->isValid()) {
            $error = $this->jsonSchema->error();
            $this->callBackWithParamError($error);
        }

        return true;
    }

    /**
     * 参数异常返回
     *
     * @param string $msg 异常信息, 可以不传,默认按照错误码信息返回
     */
    public function callBackWithParamError($msg = '')
    {
        // set_status_header(400);
        callBack(4, $msg);
    }

}