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
     * Used to force a page to be accessed in via HTTPS.
     * Uses a standard redirect, plus will set the HSTS header
     * for modern browsers that support, which gives best
     * protection against man-in-the-middle attacks.
     *
     * @see https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
     *
     * @param int               $duration How long should the SSL header be set for? (in seconds)
     *                                    Defaults to 1 year.
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     */
    /**
     * @param int           $duration
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
        // If the session library is loaded, we should regenerate
        // the session ID for safety sake.
        if (class_exists('Session', false)) {
            Services::session(null, true)->regenerate();
        }
        $uri = $request->uri;
        $uri->setScheme('https');
        $uri = \YP\Core\YP_Uri::createURIString($uri->getScheme(), $uri->getAuthority(true), $uri->getPath(),
            // Absolute URIs should use a "/" for an empty path
            $uri->getQuery(), $uri->getFragment());
        // Set an HSTS header
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
     * Performs simple auto-escaping of data for security reasons.
     * Might consider making this more complex at a later date.
     *
     * If $data is a string, then it simply escapes and returns it.
     * If $data is an array, then it loops over it, escaping each
     * 'value' of the key/value pairs.
     *
     * Valid context values: html, js, css, url, attr, raw, null
     *
     * @param string|array $data
     * @param string       $context
     * @param string       $encoding
     *
     * @return $data
     */
    /**
     * @param        $data
     * @param string $context
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
            // Provide a way to NOT escape data since
            // this could be called automatically by
            // the View library.
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
            // @todo Optimize this to only load a single instance during page request.
            $escaper = new \Zend\Escaper\Escaper($encoding);
            $data    = $escaper->$method($data);
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

