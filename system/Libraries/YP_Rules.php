<?php
/**
 * User: yongli
 * Date: 17/4/29
 * Time: 22:07
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

class YP_Rules
{

    /**
     * Alpha
     *
     * @param    string
     *
     * @return    bool
     */
    public function alpha(string $str=null): bool
    {
        return ctype_alpha($str);
    }

    //--------------------------------------------------------------------

    /**
     * Alpha with spaces.
     *
     * @param string $value Value.
     *
     * @return bool True if alpha with spaces, else false.
     */
    public function alpha_space(string $value = null): bool
    {
        if ($value === null)
        {
            return true;
        }

        return (bool)preg_match('/^[A-Z ]+$/i', $value);
    }

    //--------------------------------------------------------------------

    /**
     * Alpha-numeric with underscores and dashes
     *
     * @param    string
     *
     * @return    bool
     */
    public function alpha_dash(string $str=null): bool
    {
        return (bool)preg_match('/^[a-z0-9_-]+$/i', $str);
    }

    //--------------------------------------------------------------------

    /**
     * Alpha-numeric
     *
     * @param    string
     *
     * @return    bool
     */
    public function alpha_numeric(string $str=null): bool
    {
        return ctype_alnum((string)$str);
    }

    //--------------------------------------------------------------------

    /**
     * Alpha-numeric w/ spaces
     *
     * @param    string
     *
     * @return    bool
     */
    public function alpha_numeric_spaces(string $str=null): bool
    {
        return (bool)preg_match('/^[A-Z0-9 ]+$/i', $str);
    }

    //--------------------------------------------------------------------

    /**
     * Decimal number
     *
     * @param    string
     *
     * @return    bool
     */
    public function decimal(string $str=null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
    }

    //--------------------------------------------------------------------

    /**
     * Integer
     *
     * @param    string
     *
     * @return    bool
     */
    public function integer(string $str=null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]+$/', $str);
    }

    //--------------------------------------------------------------------

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     *
     * @param	string
     * @return	bool
     */
    public function is_natural(string $str=null): bool
    {
        return ctype_digit((string) $str);
    }

    //--------------------------------------------------------------------

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     *
     * @param	string
     * @return	bool
     */
    public function is_natural_no_zero(string $str=null): bool
    {
        return ($str != 0 && ctype_digit((string) $str));
    }

    //--------------------------------------------------------------------

    /**
     * Numeric
     *
     * @param    string
     *
     * @return    bool
     */
    public function numeric(string $str=null): bool
    {
        return (bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

    }

    //--------------------------------------------------------------------

    /**
     * Compares value against a regular expression pattern.
     *
     * @param string $str
     * @param string $pattern
     * @param array  $data Other field/value pairs
     *
     * @return bool
     */
    public function regex_match(string $str=null, string $pattern, array $data): bool
    {
        if (substr($pattern, 0, 1) != '/')
        {
            $pattern = "/{$pattern}/";
        }

        return (bool)preg_match($pattern, $str);
    }

    //--------------------------------------------------------------------

    /**
     * Validates that the string is a valid timezone as per the
     * timezone_identifiers_list function.
     *
     * @see http://php.net/manual/en/datetimezone.listidentifiers.php
     *
     * @param string $str
     *
     * @return bool
     */
    public function timezone(string $str=null): bool
    {
        return in_array($str, timezone_identifiers_list());
    }

    //--------------------------------------------------------------------

    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @param	string
     * @return	bool
     */
    public function valid_base64(string $str=null): bool
    {
        return (base64_encode(base64_decode($str)) === $str);
    }

    //--------------------------------------------------------------------

    /**
     * Checks for a correctly formatted email address
     *
     * @param    string
     *
     * @return    bool
     */
    public function valid_email(string $str=null): bool
    {
        if (function_exists('idn_to_ascii') && $atpos = strpos($str, '@'))
        {
            $str = substr($str, 0, ++$atpos).idn_to_ascii(substr($str, $atpos));
        }

        return (bool)filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    //--------------------------------------------------------------------

    /**
     * Validate a comma-separated list of email addresses.
     *
     * Example:
     * 	valid_emails[one@example.com,two@example.com]
     *
     * @param    string
     *
     * @return    bool
     */
    public function valid_emails(string $str=null): bool
    {
        if (strpos($str, ',') === false)
        {
            return $this->valid_email(trim($str));
        }

        foreach (explode(',', $str) as $email)
        {
            if (trim($email) !== '' && $this->valid_email(trim($email)) === false)
            {
                return false;
            }
        }

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * Validate an IP address
     *
     * @param        $ip     IP Address
     * @param string $which  IP protocol: 'ipv4' or 'ipv6'
     * @param array  $data
     *
     * @return bool
     */
    public function valid_ip(string $ip=null, string $which = null, array $data): bool
    {
        switch (strtolower($which))
        {
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

    //--------------------------------------------------------------------

    /**
     * Checks a URL to ensure it's formed correctly.
     *
     * @param string $str
     *
     * @return bool
     */
    public function valid_url(string $str=null): bool
    {
        if (empty($str))
        {
            return false;
        }
        elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches))
        {
            if (empty($matches[2]))
            {
                return false;
            }
            elseif (! in_array($matches[1], ['http', 'https'], true))
            {
                return false;
            }

            $str = $matches[2];
        }

        $str = 'http://'.$str;

        return (filter_var($str, FILTER_VALIDATE_URL) !== false);
    }
    
    
}