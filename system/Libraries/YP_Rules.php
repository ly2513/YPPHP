<?php
/**
 * User: yongli
 * Date: 17/4/29
 * Time: 22:07
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

/**
 * Class YP_Rules 路由库
 *
 * @package YP\Libraries
 */
class YP_Rules
{
    /**
     * 做纯字符检测
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function alpha(string $str = null): bool
    {
        return ctype_alpha($str);
    }

    /**
     * 检测字符是否含有空格
     *
     * @param string|null $value
     *
     * @return bool TRUE:表示有,FALSE:表示没有
     */
    public function alpha_space(string $value = null): bool
    {
        if ($value === null) {
            return true;
        }

        return (bool)preg_match('/^[A-Z ]+$/i', $value);
    }

    /**
     * 字符串中是否含有下划线和破折号
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function alpha_dash(string $str = null): bool
    {
        return (bool)preg_match('/^[a-z0-9_-]+$/i', $str);
    }

    /**
     *  对字母和数字字符检测
     *
     * @param string|null $str
     *
     * @return bool TRUE:字符串全部是字母和(或者)数字,FALSE:含有除字母、数字之外的字符
     */
    public function alpha_numeric(string $str = null): bool
    {
        return ctype_alnum((string)$str);
    }

    /**
     * 检测字符串中是否含有空格
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function alpha_numeric_spaces(string $str = null): bool
    {
        return (bool)preg_match('/^[A-Z0-9 ]+$/i', $str);
    }

    /**
     * 校验带有小数点的数值
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function decimal(string $str = null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
    }

    /**
     * 整数校验
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function integer(string $str = null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]+$/', $str);
    }

    /**
     * 校验字符是否为纯数字
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function is_natural(string $str = null): bool
    {
        return ctype_digit((string)$str);
    }

    /**
     * 校验是否为除0外的自然数
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function is_natural_no_zero(string $str = null): bool
    {
        return ($str != 0 && ctype_digit((string)$str));
    }

    /**
     * 校验是否为数值
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function numeric(string $str = null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

    }

    /**
     * 将值与正则表达式进行匹配
     *
     * @param string|null $str
     * @param string      $pattern
     * @param array       $data 其他字段/值对
     *
     * @return bool
     */
    public function regex_match(string $str = null, string $pattern, array $data): bool
    {
        if (substr($pattern, 0, 1) != '/') {
            $pattern = "/{$pattern}/";
        }

        return (bool)preg_match($pattern, $str);
    }

    /**
     * 校验是否为时区
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function timezone(string $str = null): bool
    {
        return in_array($str, timezone_identifiers_list());
    }

    /**
     * 是否为有效的Base64字符串
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function valid_base64(string $str = null): bool
    {
        return (base64_encode(base64_decode($str)) === $str);
    }

    /**
     * 校验是否为正确的格式化邮箱地址
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function valid_email(string $str = null): bool
    {
        if (function_exists('idn_to_ascii') && $atpos = strpos($str, '@')) {
            $str = substr($str, 0, ++$atpos) . idn_to_ascii(substr($str, $atpos));
        }

        return (bool)filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    /**
     * 校验逗号分隔的电子邮件地址列表
     * 例如:
     * valid_emails[liyong@qq.com,liyong@163.com]
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function valid_emails(string $str = null): bool
    {
        if (strpos($str, ',') === false) {
            return $this->valid_email(trim($str));
        }
        foreach (explode(',', $str) as $email) {
            if (trim($email) !== '' && $this->valid_email(trim($email)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 校验是否为IP地址
     *
     * @param string|null $ip    IP地址
     * @param string|null $which IP协议('ipv4' or 'ipv6')
     * @param array       $data
     *
     * @return bool
     */
    public function valid_ip(string $ip = null, string $which = null, array $data): bool
    {
        switch (strtolower($which)) {
            case 'ipv4':
                $which = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $which = FILTER_FLAG_IPV6;
                break;
            default:
                $which = null;
                break;
        }

        return (bool)filter_var($ip, FILTER_VALIDATE_IP, $which);
    }

    /**
     * 检查URL
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function valid_url(string $str = null): bool
    {
        if (empty($str)) {
            return false;
        } elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches)) {
            if (empty($matches[2])) {
                return false;
            } elseif (!in_array($matches[1], ['http', 'https'], true)) {
                return false;
            }
            $str = $matches[2];
        }
        $str = 'http://' . $str;

        return (filter_var($str, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * 校验一个值是否匹配数组$data中某个字段
     *
     * @param string|null $str   值
     * @param string      $field 字段名称
     * @param array       $data  所属的数组
     *
     * @return bool
     */
    public function differs(string $str = null, string $field, array $data): bool
    {
        return array_key_exists($field, $data) ? ($str !== $data[$field]) : false;
    }

    /**
     * 校验某个字符串的长度是否为指定的长度
     *
     * @param string|null $str
     * @param string      $val
     * @param array       $data
     *
     * @return bool
     */
    public function exact_length(string $str = null, string $val, array $data): bool
    {
        if (!is_numeric($val)) {
            return false;
        }

        return ((int)$val == mb_strlen($str));
    }

    /**
     * 校验两个字符串$str 大于 $min
     *
     * @param string|null $str
     * @param string      $min
     * @param array       $data
     *
     * @return bool
     */
    public function greater_than(string $str = null, string $min, array $data): bool
    {
        return is_numeric($str) ? ($str > $min) : false;
    }

    /**
     * 校验两个字符串大于或等于
     *
     * @param string|null $str
     * @param string      $min
     * @param array       $data
     *
     * @return bool
     */
    public function greater_than_equal_to(string $str = null, string $min, array $data): bool
    {
        return is_numeric($str) ? ($str >= $min) : false;
    }

    /**
     * 校验所给的值是否在某个数组中
     *
     * @param string|null $value 被校验的值
     * @param string      $list  数组
     * @param array       $data  其他的键值对
     *
     * @return bool
     */
    public function in_list(string $value = null, string $list, array $data): bool
    {
        $list = explode(',', $list);
        $list = array_map(function ($value) { return trim($value); }, $list);

        return in_array($value, $list, true);
    }

    /**
     * 校验某个字符串小于某个指定长度
     *
     * @param string|null $str 被校验的字符串
     * @param string      $max 指定的长度
     *
     * @return bool
     */
    public function less_than(string $str = null, string $max): bool
    {
        return is_numeric($str) ? ($str < $max) : false;
    }

    /**
     * 校验某个字符串小于等于某个长度
     *
     * @param string|null $str 被校验的字符串
     * @param string      $max 指定的长度
     *
     * @return bool
     */
    public function less_than_equal_to(string $str = null, string $max): bool
    {
        return is_numeric($str) ? ($str <= $max) : false;
    }

    /**
     * 在数据中匹配另一字段的值
     *
     * @param string|null $str   字段对应的值
     * @param string      $field 字段名称
     * @param array       $data  其他字段/值对
     *
     * @return bool
     */
    public function matches(string $str = null, string $field, array $data): bool
    {
        return array_key_exists($field, $data) ? ($str === $data[$field]) : false;
    }

    /**
     * 校验字符串长度是否小于等于某个指定的长度
     *
     * @param string|null $str 被校验的长度
     * @param string      $val 指定的长度
     * @param array       $data
     *
     * @return bool
     */
    public function max_length(string $str = null, string $val, array $data): bool
    {
        if (!is_numeric($val)) {
            return false;
        }

        return ($val >= mb_strlen($str));
    }

    /**
     * 校验字符串长度大于等于某个指定的长度
     *
     * @param string|null $str
     * @param string      $val
     * @param array       $data
     *
     * @return bool
     */
    public function min_length(string $str = null, string $val, array $data): bool
    {
        if (!is_numeric($val)) {
            return false;
        }

        return ($val <= mb_strlen($str));
    }

    /**
     * 一定要存在
     *
     * @param null $str
     *
     * @return bool
     */
    public function required($str = null): bool
    {
        return is_array($str) ? (bool)count($str) : (trim($str) !== '');
    }

    /**
     * 校验某些字段在某个数组中
     *
     * 例如: 密码(password)字段一定不能缺少
     * required_with['password']
     *
     * @param null   $str    检验的键名
     * @param string $fields 以逗号分隔字符串字段
     * @param array  $data   校验的数组
     *
     * @return bool
     */
    public function required_with($str = null, string $fields, array $data): bool
    {
        $fields = explode(',', $fields);
        // 判断某个字段是否存在
        $present = $this->required($data[$str] ?? null);
        if ($present === true) {
            return true;
        }
        // 求交集,过滤交集数组
        $requiredFields = array_intersect($fields, $data);
        $requiredFields = array_filter($requiredFields, function ($item) {
            return !empty($item);
        });

        return !(bool)count($requiredFields);
    }

    /**
     * 某些字段需要,但不在某个数组中
     *
     * 例如: 假如id,email字段需要,但不存在某个数组中
     * required_without[id,email]
     *
     * @param null   $str
     * @param string $fields
     * @param array  $data
     *
     * @return bool TRUE:表示不存在,FALSE:存在
     */
    public function required_without($str = null, string $fields, array $data): bool
    {
        $fields = explode(',', $fields);
        // 判断某个字段是否存在
        $present = $this->required($data[$str] ?? null);
        if ($present === true) {
            return true;
        }
        // 校验字段
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 校验日规则
     *
     * @param string|null $str
     *
     * @return bool
     */
    public function is_date(string $str = null)
    {
        return (bool)preg_match('/\\d{4}-\\d{2}-\\d{2}+$/', $str);
    }

}