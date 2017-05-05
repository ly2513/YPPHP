<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 10:22
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
use YP\Core\YP_Request as Request;
use YP\Core\YP_Response as Response;
//use CodeIgniter\HTTP\RedirectException;
use YP\Config\Services;

if (!function_exists('P')) {
    /**
     * 打印函数
     *
     * @param      $arr   打印的数据
     * @param bool $isDie 是否打断点
     */
    function P($arr, $isDie = false)
    {
        if (is_bool($arr)) {
            var_dump($arr);
        } elseif (is_null($arr)) {
            var_dump(null);
        } else {
            if (is_cli()) {
                print_r($arr);
            } else {
                echo "<pre style='position:relative;z-index:1000;padding:10px;border-radius:5px;background:#F5F5F5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;'>" . print_r($arr,
                        true) . "</pre>";
            }
        }
        if ($isDie) {
            die();
        }
    }
}
if (!function_exists('is_cli')) {
    /**
     * 判断是否为cli模式
     *
     * @return bool
     */
    function is_cli(): bool
    {
        return (PHP_SAPI === 'cli' || defined('STDIN'));
    }
}
if (!function_exists('helper')) {
    /**
     * 加载帮助类
     *
     * @param $filenames
     */
    function helper($filenames)//: string
    {
        $loader = Services::locator(true);
        if (!is_array($filenames)) {
            $filenames = [$filenames];
        }
        foreach ($filenames as $filename) {
            if (strpos($filename, '_helper') === false) {
                $filename .= '_helper';
            }
            $path = $loader->locateFile($filename, 'Helpers');
            if (!empty($path)) {
                include $path;
            }
        }
    }
}
if (!function_exists('force_https')) {
    /**
     * 用于强制页通过HTTPS访问
     * 使用一个标准的重定向，并将现代浏览器支持HSTS报头进行设置，这在中间攻击时提供了最好的保护
     *
     * @param int           $duration SSL头应该设置多长时间?(秒) 默认为1年。
     * @param Request|null  $request
     * @param Response|null $response
     */
    function force_https(int $duration = 31536000, Request $request = null, Response $response = null)
    {
        if (is_null($request)) {
            $request = Services::request(null, true);
        }
        if (is_null($response)) {
            $response = Services::response(null, true);
        }
        if ($request->isSecure()) {
            return;
        }
        // 如果会话库已加载，为安全起见,则应重新生成会话ID
        if (class_exists('Session', false)) {
            Services::session(null, true)->regenerate();
        }
        $uri = $request->uri;
        $uri->setScheme('https');
        // 绝对的URI应该用“/”为一个空的路径
        $uri = \YP\Core\YP_Uri::createURIString($uri->getScheme(), $uri->getAuthority(true), $uri->getPath(),
            $uri->getQuery(), $uri->getFragment());
        // 设置一个HSTS报头
        $response->setHeader('Strict-Transport-Security', 'max-age=' . $duration);
        $response->redirect($uri);
        exit();
    }
}
if (!function_exists('log_message')) {
    /**
     * 记录日志信息
     *
     * @param string $level 日志级别:emergency、critical、error、warning、notice、info、debug
     * @param string $message
     * @param array  $context
     *
     * @return bool
     */
    function log_message(string $level, string $message, array $context = [])
    {
        // 在进行测试时，我们要始终确保testLogger运行
        if (ENVIRONMENT == 'test') {
            $logger = new \YP\Core\YP_Log(new \Config\Log());

            return $logger->log($level, $message, $context);
        }

        return Services::log(true)->log($level, $message, $context);
    }
}
if (!function_exists('esc')) {
    /**
     * 出于安全原因执行数据的简单自动转义。可能会考虑在以后的日子更复杂
     * 如果$data是一个字符串，那么它只是转义并返回它。如果$data是一个数组，那么它循环过来，转义每个键值/值对的“值”。
     *
     * @param        $data
     * @param string $context  有效的值为:html, js, css, url, attr, raw, null
     * @param null   $encoding
     *
     * @return mixed
     */
    function esc($data, $context = 'html', $encoding = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = esc($value, $context);
            }
        }
        if (is_string($data)) {
            $context = strtolower($context);
            // 提供一种方法来避免数据的逃避，因为这可以由视图库自动调用
            if (empty($context) || $context == 'raw') {
                return $data;
            }
            if (!in_array($context, ['html', 'js', 'css', 'url', 'attr'])) {
                throw new \InvalidArgumentException('Invalid escape context provided.');
            }
            if ($context == 'attr') {
                $method = 'escapeHtmlAttr';
            } else {
                $method = 'escape' . ucfirst($context);
            }
            // TODO 仅在页面请求时加载单个实例
            $esCaper = new \Zend\Escaper\Escaper($encoding);
            $data    = $esCaper->$method($data);
        }

        return $data;
    }
}
if (!function_exists('cache')) {
    /**
     * 提供对缓存对象的访问的便利方法
     * 如果没有提供参数，则返回该对象，否则将尝试返回缓存值
     *
     * 例如:
     *    cache()->save('foo', 'bar');
     *    $foo = cache('bar');
     *
     * @param string|null $key
     *
     * @return mixed
     */
    function cache(string $key = null)
    {
        $cache = \Config\Services::cache();
        // 参数为空,直接返回缓存对象
        if (is_null($key)) {
            return $cache;
        }

        return $cache->get($key);
    }

}
if (!function_exists('lang')) {
    /**
     * 翻译字符串,并使用国际推广的MessageFormatter对象对字符串进行格式化
     *
     * @param string      $line
     * @param array       $args
     * @param string|null $locale
     *
     * @return array|string
     */
    function lang(string $line, array $args = [], string $locale = null)
    {
        return \Config\Services::language($locale)->getLine($line, $args);
    }

}
if (!function_exists('get_rand')) {
    /**
     * 经典的概率算法，
     * $proArr是一个预先设置的数组，
     * 假设数组为：array(100,200,300，400)，
     * 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，
     * 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间，
     * 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。
     * 这样 筛选到最终，总会有一个数满足要求。
     * 就相当于去一个箱子里摸东西，
     * 第一个不是，第二个不是，第三个还不是，那最后一个一定是。
     * 这个算法简单，而且效率非常 高，
     *
     * @param $proArr
     *
     * @return int|string
     */
    function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);

        return $result;
    }
}
if (!function_exists('site_url')) {
    /**
     * 获得一个网站的URL用于视图
     *
     * @param string           $path
     * @param string|null      $scheme
     * @param \Config\App|null $altConfig
     *
     * @return string
     */
    function site_url($path = '', string $scheme = null, \Config\App $altConfig = null): string
    {
        // 通过"/"将$path数组的参数拼接起来
        if (is_array($path)) {
            $path = implode('/', $path);
        }
        // 如果提供配置使用提供的配置,否则使用默认配置
        $config = empty($altConfig) ? new \Config\App() : $altConfig;
        $base = base_url();
        // 如果没有配置indexPage,将添加indexPage
        if (!empty($config->indexPage)) {
            $path = rtrim($base, '/') . '/' . rtrim($config->indexPage, '/') . '/' . $path;
        } else {
            $path = rtrim($base, '/') . '/' . $path;
        }
        $url = new \YP\Core\YP_Uri($path);
        // 设置$scheme
        if (!empty($scheme)) {
            $url->setScheme($scheme);
        }

        return (string)$url;
    }

}
if (!function_exists('base_url')) {
    /**
     * 获得用于视图的最基本URL
     *
     * @param string      $path
     * @param string|null $scheme
     *
     * @return string
     */
    function base_url($path = '', string $scheme = null): string
    {
        // 通过"/"将$path数组的参数拼接起来
        if (is_array($path)) {
            $path = implode('/', $path);
        }
        // 我们应该使用被用户设置的URL地址否则摆脱的路径，因为我们没有办法知道的意图…
        $config = \Config\Services::request()->config;
        if (!empty($config->baseURL)) {
            $url = new \YP\Core\YP_Uri($config->baseURL);
        } else {
            $url = \Config\Services::request()->uri;
            $url->setPath('/');
        }
        unset($config);
        // 合并用户设置的路径
        if (!empty($path)) {
            $url = $url->resolveRelativeURI($path);
        }
        if (!empty($scheme)) {
            $url->setScheme($scheme);
        }

        return (string)$url;
    }

}

if (! function_exists('service'))
{
    /**
     * 允许对服务配置文件的更清洁访问
     * 总是返回类的共享实例，所以应该多次调用函数返回相同的实例
     *
     * 以下这两种方式都可以
     * $timer = service('timer')
     * $timer = \YP\Services::timer();
     *
     * @param string $name
     * @param array  ...$params
     *
     * @return mixed
     */
    function service(string $name, ...$params)
    {
        // 确认是否分享实例
        array_push($params, true);

        return Services::$name(...$params);
    }
}
