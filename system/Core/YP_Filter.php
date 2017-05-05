<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 23:50
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

use YP\Core\YP_IncomingRequest as IncomingRequest;
use YP\Core\YP_Response as Response;
use YP\Core\YP_FilterInterface as FilterInterface;

class YP_Filter
{

    /**
     * 将用于检查的处理过的过滤器
     *
     * @var array
     */
    protected $filters = [
        'before' => [],
        'after'  => []
    ];

    /**
     * 配置文件
     *
     * @var
     */
    protected $config;

    /**
     * 请求对象
     *
     * @var YP_Request
     */
    protected $request;

    /**
     * 响应对象
     *
     * @var YP_Response
     */
    protected $response;

    /**
     * 我们是否已经对过滤器列表做了初步的处理。
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * YP_Filter constructor.
     *
     * @param                    $config
     * @param YP_IncomingRequest $request
     * @param YP_Response        $response
     */
    public function __construct($config, IncomingRequest $request, Response $response)
    {
        $this->config   = $config;
        $this->request  =& $request;
        $this->response =& $response;
    }

    /**
     * 为指定的URI和位置运行所有筛选器
     *
     * @param string $uri
     * @param string $position
     *
     * @return YP_Request|YP_Response
     */
    public function run(string $uri, $position = 'before')
    {
        $this->initialize($uri);
        foreach ($this->filters[$position] as $alias => $rules) {
            if (is_numeric($alias) && is_string($rules)) {
                $alias = $rules;
            }
            if (!array_key_exists($alias, $this->config->aliases)) {
                throw new \InvalidArgumentException("'{$alias}' filter must have a matching alias defined.");
            }
            $class = new $this->config->aliases[$alias]();
            if (!$class instanceof FilterInterface) {
                throw new \RuntimeException(get_class($class) . ' 必须实现 YP\Core\YP_FilterInterface 该接口.');
            }
            if ($position == 'before') {
                $result = $class->before($this->request);
                if ($result instanceof Request) {
                    $this->request = $result;
                    continue;
                }
                // 如果响应对象被发送回来，然后发送并退出
                if ($result instanceof Response) {
                    $result->send();
                    exit(EXIT_ERROR);
                }
                if (empty($result)) {
                    continue;
                }

                return $result;
            } elseif ($position == 'after') {
                $result = $class->after($this->request, $this->response);
                if ($result instanceof Response) {
                    $this->response = $result;
                    continue;
                }
            }
        }

        return $position == 'before' ? $this->request : $this->response;
    }

    /**
     * 通过我们的配置对象名单提供给准备使用过滤器，
     * 包括获取URI的面具，适当的正则表达式，去除那些我们可以从基于HTTP方法的可能性
     *
     * @param string|null $uri
     *
     * @return YP_Filter
     */
    public function initialize(string $uri = null): self
    {
        if ($this->initialized === true) {
            return $this;
        }
        $this->processGlobals($uri);
        $this->processMethods();
        $this->processFilters($uri);
        $this->initialized = true;

        return $this;
    }

    /**
     * 获得处理过滤的数组
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * 全局处理Uri
     *
     * @param string|null $uri
     */
    protected function processGlobals(string $uri = null)
    {
        if (!isset($this->config->globals) || !is_array($this->config->globals)) {
            return;
        }
        // 处理前
        if (isset($this->config->globals['before'])) {
            // Take any 'except' routes into consideration
            foreach ($this->config->globals['before'] as $alias => $rules) {
                if (!is_array($rules) || !array_key_exists('except', $rules)) {
                    continue;
                }
                $rules = $rules['except'];
                foreach ($rules as $path) {
                    // 准备正则表达式
                    $path = str_replace('/*', '*', $path);
                    $path = trim(str_replace('*', '.+', $path), '/ ');
                    // 如果路径与URI不匹配将继续匹配
                    if (preg_match('/' . $path . '/', $uri, $match) !== 1) {
                        continue;
                    }
                    unset($this->config->globals['before'][$alias]);
                    break;
                }
            }
            $this->filters['before'] = array_merge($this->filters['before'], $this->config->globals['before']);
        }
        // 处理后
        if (isset($this->config->globals['after'])) {
            // Take any 'except' routes into consideration
            foreach ($this->config->globals['after'] as $alias => $rules) {
                if (!is_array($rules) || !array_key_exists('except', $rules)) {
                    continue;
                }
                $rules = $rules['except'];
                if (is_string($rules)) {
                    $rules = [$rules];
                }
                foreach ($rules as $path) {
                    // 准备正则表达式
                    $path = str_replace('/*', '*', $path);
                    $path = trim(str_replace('*', '.+', $path), '/ ');
                    // 如果路径与URI不匹配将继续匹配
                    if (preg_match('/' . $path . '/', $uri, $match) !== 1) {
                        continue;
                    }
                    unset($this->config->globals['after'][$alias]);
                    break;
                }
            }
            $this->filters['after'] = array_merge($this->filters['after'], $this->config->globals['after']);
        }
    }

    /**
     * 处理方法
     */
    protected function processMethods()
    {
        if (!isset($this->config->methods) || !is_array($this->config->methods)) {
            return;
        }
        // 基于CLI的请求,不会设置请求方法
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';
        if (array_key_exists($method, $this->config->methods)) {
            $this->filters['before'] = array_merge($this->filters['before'], $this->config->methods[$method]);

            return;
        }
    }

    /**
     * 处理过滤器
     *
     * @param string|null $uri
     */
    protected function processFilters(string $uri = null)
    {
        if (!isset($this->config->filters) || !count($this->config->filters)) {
            return;
        }
        $uri     = trim($uri, '/ ');
        $matches = [];
        foreach ($this->config->filters as $alias => $settings) {
            // 处理前
            if (isset($settings['before'])) {
                foreach ($settings['before'] as $path) {
                    // 准备正则表达式
                    $path = str_replace('/*', '*', $path);
                    $path = trim(str_replace('*', '.+', $path), '/ ');
                    if (preg_match('/' . $path . '/', $uri) !== 1) {
                        continue;
                    }
                    $matches[] = $alias;
                }
                $this->filters['before'] = array_merge($this->filters['before'], $matches);
                $matches                 = [];
            }
            // 处理后
            if (isset($settings['after'])) {
                foreach ($settings['after'] as $path) {
                    // 准备正则表达式
                    $path = str_replace('/*', '*', $path);
                    $path = trim(str_replace('*', '.+', $path), '/ ');
                    if (preg_match('/' . $path . '/', $uri) !== 1) {
                        continue;
                    }
                    $matches[] = $alias;
                }
                $this->filters['after'] = array_merge($this->filters['after'], $matches);
            }
        }
    }

}