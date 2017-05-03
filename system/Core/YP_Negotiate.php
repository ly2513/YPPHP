<?php
/**
 * User: yongli
 * Date: 17/5/2
 * Time: 11:31
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Core;

class YP_Negotiate
{
    /**
     * 请求实例
     *
     * @var null|YP_IncomingRequest
     */
    protected $request;

    /**
     * YP_Negotiate constructor.
     *
     * @param YP_IncomingRequest|null $request
     */
    public function __construct(YP_IncomingRequest $request = null)
    {
        if (!is_null($request)) {
            $this->request = $request;
        }
    }

    /**
     * 存储请求实例
     *
     * @param YP_IncomingRequest $request
     *
     * @return YP_Negotiate
     */
    public function setRequest(YP_IncomingRequest $request):self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * 通过支持的内容类型,来确定最佳的内容类型
     *
     * @param array $supported   支持的类型
     * @param bool  $strictMatch TRUE: 返回空字符串时,没有找到最佳的类型;FALSE: 返回第一个支持的类型
     *
     * @return string
     */
    public function media(array $supported, bool $strictMatch = false): string
    {
        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept'), true, $strictMatch);
    }

    /**
     * 通过支持的字符编码类型,来确定最佳的字符编码
     *
     * @param array $supported
     *
     * @return string
     */
    public function charset(array $supported): string
    {
        $match = $this->getBestMatch($supported, $this->request->getHeaderLine('accept-charset'), false, true);
        // 没匹配成功,使用utf-8作为默认编码
        if (empty($match)) {
            return 'utf-8';
        }

        return $match;
    }

    /**
     * 通过支持的encoding类型,来确定最佳的encoding
     *
     * @param array $supported
     *
     * @return string
     */
    public function encoding(array $supported = []): string
    {
        array_push($supported, 'identity');

        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-encoding'));
    }

    /**
     * 通过支持的language类型,来确定最佳的language
     *
     * @param array $supported
     *
     * @return string
     */
    public function language(array $supported): string
    {
        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-language'));
    }

    /**
     * 是否将一个应用程序支持的值与给定的接受*头字符串进行比较
     *
     * @param array       $supported
     * @param string|null $header       报头字符串
     * @param bool        $enforceTypes 如果TRUE，将比较媒体类型和子类型。
     * @param bool        $strictMatch  如果TRUE，将返回空字符串不匹配。
     *                                  如果FALSE，将返回第一个支持的元素。
     *
     * @return string
     */
    protected function getBestMatch(
        array $supported,
        string $header = null,
        bool $enforceTypes = false,
        bool $strictMatch = false
    ): string
    {
        if (empty($supported)) {
            throw new \InvalidArgumentException('You must provide an array of supported values to all Negotiations.');
        }
        if (empty($header)) {
            return $strictMatch ? '' : $supported[0];
        }
        $acceptable = $this->parseHeader($header);
        // 如果没有可接受的值，返回所支持的第一个值。
        if (empty($acceptable)) {
            return $supported[0];
        }
        foreach ($acceptable as $accept) {
            // 如果可以接受的q为零，跳过它
            if ($accept['q'] == 0) {
                continue;
            }
            // 如果可接受的值是“任何值”，返回第一可用
            if ($accept['value'] == '*' || $accept['value'] == '*/*') {
                return $supported[0];
            }
            // 返回第一个可接受的值
            foreach ($supported as $available) {
                if ($this->match($accept, $available, $enforceTypes)) {
                    return $available;
                }
            }
        }

        // 如果没匹配上,将返回第一个支持的元素
        return $strictMatch ? '' : $supported[0];
    }

    /**
     * 解析多个报头值
     *
     * @param string $header
     *
     * @return array
     */
    public function parseHeader(string $header)
    {
        $results    = [];
        $acceptable = explode(',', $header);
        foreach ($acceptable as $value) {
            $pairs = explode(';', $value);
            $value = $pairs[0];
            unset($pairs[0]);
            $parameters = [];
            foreach ($pairs as $pair) {
                $param = [];
                preg_match('/^(?P<name>.+?)=(?P<quoted>"|\')?(?P<value>.*?)(?:\k<quoted>)?$/', $pair, $param);
                $parameters[trim($param['name'])] = trim($param['value']);
            }
            $quality = 1.0;
            if (array_key_exists('q', $parameters)) {
                $quality = $parameters['q'];
                unset($parameters['q']);
            }
            $results[] = [
                'value'  => trim($value),
                'q'      => (float)$quality,
                'params' => $parameters
            ];
        }
        // 排序首先得到最高的结果
        usort($results, function ($a, $b) {
            if ($a['q'] == $b['q']) {
                $a_ast = substr_count($a['value'], '*');
                $b_ast = substr_count($b['value'], '*');
                // '*/*' 的优先级比'text/*' 低, 并且 'text/*' 的优先级比 'text/plain' 低
                if ($a_ast > $b_ast) {
                    return 1;
                }
                // 如果总数是相同的，但其中一个元素比其他具有多个参数，它具有更高的优先级。
                if ($a_ast == $b_ast) {
                    return count($b['params']) - count($a['params']);
                }

                return 0;
            }

            // q值越大,优先级越高
            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        return $results;
    }

    /**
     * 匹配类型
     *
     * @param array  $acceptable
     * @param string $supported
     * @param bool   $enforceTypes
     *
     * @return bool
     */
    protected function match(array $acceptable, string $supported, bool $enforceTypes = false)
    {
        $supported = $this->parseHeader($supported);
        if (is_array($supported) && count($supported) == 1) {
            $supported = $supported[0];
        }
        // 精准匹配
        if ($acceptable['value'] == $supported['value']) {
            return $this->matchParameters($acceptable, $supported);
        }
        // 通过negotiateMedia()可以比较types/sub-types
        if ($enforceTypes) {
            return $this->matchTypes($acceptable, $supported);
        }

        return false;
    }

    /**
     * 检查两个接受值相匹配的“values”，看看他们的'params'是相同的
     *
     * @param array $acceptable
     * @param array $supported
     *
     * @return bool
     */
    protected function matchParameters(array $acceptable, array $supported): bool
    {
        if (count($acceptable['params']) != count($supported['params'])) {
            return false;
        }
        foreach ($supported['params'] as $label => $value) {
            if (!isset($acceptable['params'][$label]) || $acceptable['params'][$label] != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * 比较可接受的媒体类型('types/subtypes')。
     *
     * @param array $acceptable
     * @param array $supported
     *
     * @return bool
     */
    public function matchTypes(array $acceptable, array $supported): bool
    {
        list($aType, $aSubType) = explode('/', $acceptable['value']);
        list($sType, $sSubType) = explode('/', $supported['value']);
        // 如果类型不匹配，就完成了
        if ($aType != $sType) {
            return false;
        }
        // 如果有星号，返回TRUE(types/*)
        if ($aSubType == '*') {
            return true;
        }

        // 否则，子类型型也必须匹配
        return $aSubType == $sSubType;
    }
}