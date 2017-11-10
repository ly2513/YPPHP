<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:22
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

/**
 * 请求头处理类
 *
 * Class YP_Header
 *
 * @package YP\Libraries
 */
class YP_Header
{
    /**
     * 头部名称
     *
     * @var null|string
     */
    protected $name;

    /**
     * 头部的值,可能存在多个值,如果是多个值就是一个数组,单个就是字符串
     *
     * @var null
     */
    protected $value;

    /**
     * YP_Header constructor.
     *
     * @param string|null $name
     * @param null        $value
     */
    public function __construct(string $name = null, $value = null)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * 返回头的名称
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取标头的原始值。这会返回一个字符串数组，取决于头是否有多值
     *
     * @return array|null|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 设置头部名称,覆盖以前的任何值。
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 设置一个头部值, 覆盖以前的任何值（S）
     *
     * @param null $value
     *
     * @return $this
     */
    public function setValue($value = null)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * 添加一个值为该报头值的列表。如果标题是一个单值的字符串，它将被转换为一个数组
     *
     * @param null $value
     *
     * @return $this
     */
    public function appendValue($value = null)
    {
        if (! is_array($this->value)) {
            $this->value = [$this->value];
        }

        $this->value[] = $value;

        return $this;
    }

    /**
     * 预备一个值作为此标头值列表。如果标题是一个单值的字符串，它将被转换为一个数组
     *
     * @param null $value
     *
     * @return $this
     */
    public function prependValue($value = null)
    {
        if (! is_array($this->value)) {
            $this->value = [$this->value];
        }

        array_unshift($this->value, $value);

        return $this;
    }

    /**
     * 检索一个逗号分隔的字符串的值为一个单一的头。
     * 注意：不是所有标头值可以适当代表使用逗号连接。这样的标题，而不是用getheader()并提供您自己的连接符时
     *
     * @return string
     */
    public function getValueLine(): string
    {
        if (is_string($this->value)) {
            return $this->value;
        } elseif (! is_array($this->value)) {
            return '';
        }

        $options = [];

        foreach ($this->value as $key => $value) {
            if (is_string($key) && ! is_array($value)) {
                $options[] = $key.'='.$value;
            } elseif (is_array($value)) {
                $key       = key($value);
                $options[] = $key.'='.$value[$key];
            } elseif (is_numeric($key)) {
                $options[] = $value;
            }
        }

        return implode(', ', $options);
    }

    /**
     * 返回整个标头字符串的表示形式，包括标题名称和所有转换为适当格式的值
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name.': '.$this->getValueLine();
    }
}
