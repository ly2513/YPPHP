<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:32
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

class YP_Message
{
    /**
     * HTTP 请求头数组
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 请求头参数名称映射
     *
     * @var array
     */
    protected $headerMap = [];

    /**
     * 协议的版本
     *
     * @var
     */
    protected $protocolVersion;

    /**
     * 有效协议的版本列表
     *
     * @var array
     */
    protected $validProtocolVersions = ['1.0', '1.1', '2'];

    /**
     * 消息的主体
     *
     * @var
     */
    protected $body;

    /**
     * 返回消息的主体
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 设置当前消息主体内容
     *
     * @param $data
     *
     * @return $this
     */
    public function setBody(&$data)
    {
        $this->body = $data;

        return $this;
    }
    
    /**
     * 将数据追加到当前消息体
     *
     * @param $data
     *
     * @return YP_Message
     */
    public function appendBody($data): self
    {
        $this->body .= (string)$data;

        return $this;
    }

    /**
     * 将$_SERVER中的头部信息存入到$headers
     */
    public function populateHeaders()
    {
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : getenv('CONTENT_TYPE');
        if (!empty($contentType)) {
            $this->setHeader('Content-Type', $contentType);
        }
        unset($contentType);
        foreach ($_SERVER as $key => $val) {
            if (sscanf($key, 'HTTP_%s', $header) === 1) {
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));
                if (array_key_exists($key, $_SERVER)) {
                    $this->setHeader($header, $_SERVER[$key]);
                } else {
                    $this->setHeader($header, '');
                }
                // 存放请求头映射关系
                $this->headerMap[strtolower($header)] = $header;
            }
        }
    }

    /**
     * 返回包含所有头部信息数组
     *
     * @return array
     */
    public function getHeaders(): array
    {
        // 如果没有定义头文件，但用户请求它，那么它很可能希望它被填充
        if (empty($this->headers)) {
            $this->populateHeaders();
        }

        return $this->headers;
    }

    /**
     * 返回一个头部对象。如果存在同名的多个标头，则将返回头部对象的数组
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function getHeader($name)
    {
        $orig_name = $this->getHeaderName($name);
        if (!isset($this->headers[$orig_name])) {
            return null;
        }

        return $this->headers[$orig_name];
    }

    /**
     * 判断是否存在某个头部
     *
     * @param $name
     *
     * @return bool
     */
    public function hasHeader($name): bool
    {
        $orig_name = $this->getHeaderName($name);

        return isset($this->headers[$orig_name]);
    }

    /**
     * 用逗号分隔符将单个头部串联起来,并返回这个串联的字符串
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        $orig_name = $this->getHeaderName($name);
        if (!array_key_exists($orig_name, $this->headers)) {
            return '';
        }
        // 如果头部数组中含有一个以上的值,将返回数组的第一个值
        if (is_array($this->headers[$orig_name])) {
            return $this->headers[$orig_name][0]->getValueLine();
        }

        return $this->headers[$orig_name]->getValueLine();
    }

    /**
     * 设置一个头部参数
     *
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function setHeader(string $name, $value)
    {
        if (!isset($this->headers[$name])) {
            $this->headers[$name]               = new \YP\Libraries\YP_Header($name, $value);
            $this->headerMap[strtolower($name)] = $name;

            return $this;
        }
        if (!is_array($this->headers[$name])) {
            $this->headers[$name] = [$this->headers[$name]];
        }
        if (isset($this->headers[$name])) {
            $this->headers[$name] = new \YP\Libraries\YP_Header($name, $value);
        } else {
            $this->headers[$name][] = new \YP\Libraries\YP_Header($name, $value);
        }

        return $this;
    }

    /**
     * 从头部数组中移除指定的头部参数
     *
     * @param string $name
     *
     * @return $this
     */
    public function removeHeader(string $name)
    {
        $orig_name = $this->getHeaderName($name);
        unset($this->headers[$orig_name]);
        unset($this->headerMap[strtolower($name)]);

        return $this;
    }

    /**
     * 向头部数组添加指定的头部参数
     *
     * @param string $name
     * @param        $value
     *
     * @return $this
     */
    public function appendHeader(string $name, $value)
    {
        $orig_name = $this->getHeaderName($name);
        $this->headers[$orig_name]->appendValue($value);

        return $this;
    }

    /**
     * Adds an additional header value to any headers that accept
     * multiple values (i.e. are an array or implement ArrayAccess)
     *
     * @param string $name
     * @param        $value
     *
     * @return string
     */
    public function prependHeader(string $name, $value)
    {
        $orig_name = $this->getHeaderName($name);
        $this->headers[$orig_name]->prependValue($value);

        return $this;
    }

    /**
     * 获得HTTP请求的协议版本号
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * 设置HTTP协议版本
     *
     * @param string $version
     *
     * @return $this
     */
    public function setProtocolVersion(string $version)
    {
        if (!is_numeric($version)) {
            $version = substr($version, strpos($version, '/') + 1);
        }
        if (!in_array($version, $this->validProtocolVersions)) {
            throw new \InvalidArgumentException('Invalid HTTP Protocol Version. Must be one of: ' . implode(', ',
                    $this->validProtocolVersions));
        }
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * 取出指定的头部名称
     *
     * @param $name
     *
     * @return string
     */
    protected function getHeaderName($name): string
    {
        $lower_name = strtolower($name);

        return isset($this->headerMap[$lower_name]) ? $this->headerMap[$lower_name] : $name;
    }
}