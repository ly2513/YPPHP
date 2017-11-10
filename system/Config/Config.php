<?php
/**
 * User: yongli
 * Date: 17/4/20
 * Time: 12:52
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;

/**
 * Class Config
 *
 * @package YP\Config
 */
class Config {


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
                        if (is_null($value)) { continue;
                        }

                        if ($value === 'false') {    $value = FALSE;
                        } elseif ($value === 'true') { $value = TRUE;
                        }

                        $this->$property[$key] = $value;
                    }
                }
            }
            else
            {
                if (($value = $this->getEnvValue($property, $prefix, $shortPrefix)) !== FALSE )
                {
                    if (is_null($value)) { continue;
                    }

                    if ($value === 'false') {    $value = FALSE;
                    } elseif ($value === 'true') { $value = TRUE;
                    }

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
        if (($value = getenv("{$shortPrefix}.{$property}")) !== FALSE)
        {
            return $value;
        }
        elseif (($value = getenv("{$prefix}.{$property}")) !== FALSE)
        {
            return $value;
        }
        elseif (($value = getenv($property)) !== FALSE && $property != 'path')
        {
            return $value;
        }

        return NULL;
    }

}
