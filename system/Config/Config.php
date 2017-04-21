<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 12:52
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;


class Config
{

    /**
     * 获得环境变量的值及匹配类的属性
     * 
     * Config constructor.
     */
    public function __construct()
    {
        $properties  = array_keys(get_object_vars($this));
        $prefix      = get_class($this);
        $shortPrefix = strtolower(substr($prefix, strrpos($prefix, '\\') + 1));

        foreach ($properties as $property)
        {
            if (is_array($this->$property))
            {
                foreach ($this->$property as $key => $val)
                {
                    if ($value = $this->getEnvValue("{$property}.{$key}", $prefix, $shortPrefix))
                    {
                        if (is_null($value)) continue;

                        if ($value === 'false')    $value = false;
                        elseif ($value === 'true') $value = true;

                        $this->$property[$key] = $value;
                    }
                }
            }
            else
            {
                if (($value = $this->getEnvValue($property, $prefix, $shortPrefix)) !== false )
                {
                    if (is_null($value)) continue;

                    if ($value === 'false')    $value = false;
                    elseif ($value === 'true') $value = true;

                    $this->$property = $value;
                }
            }
        }
    }

    /**
     * 获得环境变量的值
     *
     * @param string $property
     * @param string $prefix
     * @param string $shortPrefix
     *
     * @return null|string
     */
    protected function getEnvValue(string $property, string $prefix, string $shortPrefix)
    {
        if (($value = getenv("{$shortPrefix}.{$property}")) !== false)
        {
            return $value;
        }
        elseif (($value = getenv("{$prefix}.{$property}")) !== false)
        {
            return $value;
        }
        elseif (($value = getenv($property)) !== false && $property != 'path')
        {
            return $value;
        }

        return null;
    }

}