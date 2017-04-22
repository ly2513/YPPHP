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
     * A convenience/compatibility method for logging events through
     * the Log system.
     *
     * Allowed log levels are:
     *  - emergency
     *  - alert
     *  - critical
     *  - error
     *  - warning
     *  - notice
     *  - info
     *  - debug
     *
     * @param string     $level
     * @param string     $message
     * @param array|null $context
     *
     * @return mixed
     */
    function log_message(string $level, string $message, array $context = [])
    {
        // When running tests, we want to always ensure that the
        // TestLogger is running, which provides utilities for
        // for asserting that logs were called in the test code.
        if (ENVIRONMENT == 'testing') {
            $logger = new \YP\Core\YP_Log(new \Config\Log());

            return $logger->log($level, $message, $context);
        }

        return Services::log(true)->log($level, $message, $context);
    }
}

