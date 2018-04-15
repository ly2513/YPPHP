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

/**
 * 基类控制器
 *
 * Class YP_Controller
 *
 * @package YP\Core
 */
class YP_Controller
{
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

    /**
     * 当前控制器所在的目录
     *
     * @var
     */
    protected $directory;

    /**
     * 模板数据
     *
     * @var array
     */
    protected $tempData = [];

    /**
     * 当前控制器
     *
     * @var
     */
    protected $controller;

    /**
     * 当前的url
     *
     * @var string
     */
    protected $url = '';

    /**
     * YP_Controller constructor.
     *
     * @param YP_RequestInterface $request
     * @param YP_Response         $response
     * @param YP_Log|null         $logger
     */
    public function __construct(YP_RequestInterface $request, YP_Response $response, YP_Log $logger = null)
    {
        $this->request  = is_cli() ?  $request : Services::request();
        $this->response = $response;
        $this->logger   = is_null($logger) ? Services::log(true) : $logger;
        $this->logger->info('Controller "' . get_class($this) . '" loaded.');
        if ($this->forceHTTPS > 0) {
            $this->forceHTTPS($this->forceHTTPS);
        }
        // TODO 暂时注释 加载jsonSchema
         $this->setJsonSchema();
         $this->setInput();
        $this->initTwig();
        // 初始化子类构造方法
        $this->initialization();
        $this->url = $this->_getCurrentUrl();
    }

    /**
     * 初始化控制器,用于子类使用
     */
    public function initialization()
    {
    }

    /**
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
     * 校验请求的参数
     * 如果校验失败,将错误存放到类的$error的属性上
     *
     * @param            $rules
     * @param array|null $messages
     *
     * @return bool
     */
    public function validate($rules, array $messages = null): bool
    {
        $this->validator = Services::validation();
        // 校验路由
        $success = $this->validator->withRequest($this->request)->setRules($rules, $messages)->run();
        if (!$success) {
            $this->errors = $this->validator->getErrors();
        }

        return $success;
    }

    /**
     * 初始化Twig模板引擎
     */
    protected function initTwig()
    {
        // TWig配置信息
        $config = new \Config\Twig();
        // 获得当前路由信息
        $router           = Services::router();
        $this->directory  = $router->directory();
        $controller       = explode('\\', $router->controllerName());
        $this->controller = end($controller);
        $this->method     = $router->methodName();
        $this->extension  = $config->extension ?? $this->extension;
        // 缓存目录
        $config->cache_dir = $config->cache_dir . $this->directory . $this->controller . DIRECTORY_SEPARATOR;
        $loader            = new \Twig_Loader_Filesystem($config->template_dir);
        $this->twig        = new \Twig_Environment($loader, [
            'cache'       => $config->cache_dir,
            'debug'       => $config->debug,
            'auto_reload' => $config->auto_reload,
        ]);
        $this->tempPath    = $config->template_dir;
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
        $templateName = !is_null($htmlFile) ? $htmlFile : $this->method;
        // 模板文件
        $tempFile = $this->directory . $this->controller . DIRECTORY_SEPARATOR . $templateName . $this->extension;
        // 模板路径
        $htmlPath     = $this->tempPath . $this->directory . $this->controller;
        // 自定义模板
        if (strpos($htmlFile, '/')) {
            $dirName = explode('/', $htmlFile);
            array_pop($dirName);
            $dirName  = implode('/', $dirName);
            $tempFile = rtrim($htmlFile, '/') . $this->extension;
            $htmlPath = $this->tempPath . ltrim($dirName, '/');
        }
        $tempFilePath = $this->tempPath . $tempFile;
        // 穿件模板目录
        is_dir($htmlPath) or mkdir($htmlPath, 0777, true);
        // 模板文件
        is_file($tempFilePath) or touch($tempFilePath);
        echo $this->render($tempFile, $data);
        if (!YP_DEBUG) {
            die;
        }
    }

    /**
     * 模版渲染
     *
     * @param string $template 模板名
     * @param array  $data     变量数组
     * @param bool   $return   true返回 false直接输出页面
     *
     * @return string
     */
    protected function render($template, $data = [], $return = false)
    {
        $template = $this->twig->loadTemplate($this->_getTemplateName($template));
        $data     = array_merge($this->tempData, $data);
        if ($return === true) {
            return $template->render($data);
        } else {
            return $template->display($data);
        }
    }

    /**
     * 获取模版名
     *
     * @param string $template
     *
     * @return string
     */
    private function _getTemplateName($template)
    {
        $default_ext_len = strlen($this->extension);
        if (substr($template, -$default_ext_len) != $this->extension) {
            $template .= $this->extension;
        }

        return $template;
    }

    /**
     * 分配变量到模板中
     *
     * @param $var
     * @param null $value
     */
    public function assign($var, $value = NULL)
    {
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $this->tempData[$key] = $val;
            }
        } else {
            $this->tempData[$var] = $value;
        }
    }

    /**
     * json_schema 参数验证
     */
    protected function setInput()
    {
        if (!is_object($this->input)) {
            $this->input = Services::input();
        }
    }

    /**
     * 初始化json_schema对象
     */
    protected function setJsonSchema()
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
        $this->jsonSchema->check($this->input->json);
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
    protected function callBackWithParamError($msg = '')
    {
        // set_status_header(400);
        call_back(4, [], explode("\n\r", $msg));
    }

    /**
     * 获得当前url
     */
    private function _getCurrentUrl()
    {
        return DIRECTORY_SEPARATOR . $this->directory . $this->controller . DIRECTORY_SEPARATOR . $this->method;
    }
}
