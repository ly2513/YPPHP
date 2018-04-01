<?php
/**
 * Created by IntelliJ IDEA.
 * User: dylan.li
 * Date: 2018/3/23
 * Time: 下午1:56
 */
namespace App\Libraries;

class SensitiveFilter
{
    /**
     * 词库
     *
     * @var null
     */
    public $map = [];

    public $tmpMap = null;

    // 匹配过滤的特殊字符
    public $disturbList = [
        '&',
        '*',
        '!',
        '@',
        ' ',
        '$',
        '%',
        '^',
        '(',
        ')',
        '-',
        '+',
        '?',
        '.',
        '|',
        '/',
        ',',
        '<',
        '>',
        '（',
        '）',
        '~',
        '`',
        '[',
        ']',
        ':',
        ';'
    ];

    public function __construct()
    {
        $this->setSensitiveFilter();
    }

    /**
     * 添加敏感词
     *
     * @param $word
     */
    public function addWordToMap($word)
    {
        $len = mb_strlen($word);
        // 传址
        $map = &$this->map;
        for ($i = 0; $i < $len; $i++) {
            $strWord = mb_substr($word, $i, 1, 'UTF-8');
            // 已存在
            if (isset($map[$strWord])) {
                $i == ($len - 1) ? $map[$strWord]['end'] = 1 : '';
            } else {
                // 不存在
                $map[$strWord]['end'] = $i == ($len - 1) ? 1 : 0;
            }
            // 传址
            $map = &$map[$strWord];
        }
    }

    /**
     * 添加敏感词到词库中
     *
     * @param $word
     */
    public function addWordToMap1($word)
    {
        $len = mb_strlen($word);
        $tmp = $this->tmpMap;
        for ($i = 0; $i < $len; $i++) {
            $nowWord = mb_substr($word, $i, 1, 'UTF-8');
            $nowMap  = $this->tmpMap->get($nowWord);
            if (!is_null($nowMap)) {
                $this->tmpMap = $nowMap;
            } else {
                $newMap = new SensitiveMap();
                $newMap->put('isEnd', 0);
                $this->tmpMap->put($nowWord, $newMap);
                $this->tmpMap = $newMap;
            }
            $i == ($len - 1) and $this->tmpMap->put('isEnd', 1);
        }
        $this->tmpMap = $tmp;
    }

    /**
     * 检索词用标记的符号替换
     *
     * @param        $strWord
     * @param string $mark
     *
     * @return array
     */
    public function searchFromMap3($strWord, $mark = '*')
    {
        $len    = mb_strlen($strWord, 'UTF-8');
        $map    = $this->map;
        $str    = $rep_str = '';
        $result = $replace = [];
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($strWord, $i, 1, 'UTF-8');
            if (!isset($map[$word])) {
                // reset hashmap
                $map = $this->map;
                $str ? $i-- : '';
                $str = $rep_str = '';
            } else {
                $rep_str .= $mark;
                $str .= $word;
                if ($map[$word]['end']) {
                    array_push($result, $str);
                    $str2 = str_repeat($mark, mb_strlen($str, 'UTF-8'));
                    array_push($replace, $str2);
                    $str = '';
                    $map = $this->map;
                } else {
                    $map = $map[$word];
                }
            }
        }
        return ['sensitive' => $result, 'replace' => $replace];
    }

    /**
     * 读取测试敏感词文件
     */
    public function mapList2()
    {
        $path = dirname(APP_ROOT) . '/www/sensitive.txt';
        $fp   = fopen($path, 'r');
        while (!feof($fp)) {
            $str = trim(fgets($fp)); //逐行读取。如果fgets不写length参数，默认是读取1k。
            $this->addWordToMap($str);
        }
    }

    /**
     * 添加敏感词
     */
    public function mapList()
    {
        $data = \BlackModel::select('word')->where('id', '>', 0)->get()->toArray();
        $data = array_column($data, 'word');
        foreach ($data as $v) {
            $str = trim($v);
            $this->addWordToMap($str);
        }
    }

    /**
     * 返回map
     *
     * @return null
     */
    public function getSensitiveFilter()
    {
        return $this->tmpMap;
    }

    /**
     * 从词库中查询敏感词
     *
     * @param        $string
     * @param string $mark
     *
     * @return array
     */
    public function searchFromMap1($string, $mark = '*')
    {
        $string .= 'i';
        $len         = mb_strlen($string);
        $tmp         = $map = $this->tmpMap;
        $str         = ''; // 可疑敏感词
        $rep_str     = ''; // 替换字符
        $ret_str     = ''; // 返回结果
        $sensitive   = []; // 敏感词
        $open_filter = 0; // 开启特殊字符
        $special_str = 'T"\'~`!！#￥$%%^……&*（（）)()_——--;；《<>》?？【】{}|\@,，.。、/9⃣️ ';
        for ($i = 0; $i < $len; $i++) {
            $nowWord = mb_substr($string, $i, 1);
            $nowMap  = $map->get($nowWord);
            // 过滤特殊字符
            if (strpos($special_str, $nowWord) !== false) {
                if ($open_filter) {
                    $rep_str .= $mark;
                    continue;
                } else {
                    $rep_str .= $nowWord;
                }
            }
            if (!is_null($nowMap)) {
                $open_filter = 1; // 开启特殊字符
                $rep_str .= $mark; // 字符替换
                $str .= $nowWord;
                if ($nowMap->get('isEnd')) {
                    $ret_str .= $rep_str;
                    $rep_str = '';
                    array_push($sensitive, $str);// 匹配到的敏感词
                    if (count((array)$nowMap) == 1) {
                        $map         = $tmp;
                        $str         = '';
                        $open_filter = 0;
                    }
                } else {
                    $map = $nowMap;
                }
            } else {
                $ret_str .= $str ? $str : '';
                $map    = $tmp;
                $nowMap = $map->get($nowWord);
                if (!is_null($nowMap)) {
                    $open_filter = 1; //开启特殊字符
                    $rep_str     = $mark; //字符替换
                    $str         = $nowWord;
                    $map         = $nowMap;
                } else {
                    $ret_str .= $nowWord;
                    $rep_str     = $str = '';
                    $open_filter = 0;
                }
            }
        }
        unset($tmp, $map);
        return ['sensitive' => $sensitive, 'content' => $ret_str];
    }

    /**
     * 添加敏感词
     */
    public function mapList1()
    {
        $data = \BlackModel::select('word')->where('id', '>', 0)->get()->toArray();
        $data = array_column($data, 'word');
        foreach ($data as $v) {
            $str = trim($v);
            $this->addWordToMap1($str);
        }
    }

    /**
     * 设置map
     */
    public function setSensitiveFilter()
    {
        if (is_null($this->tmpMap)) {
            $this->tmpMap = new SensitiveMap();
            $this->tmpMap->put('isEnd', 0);
        }
        return true;
    }

    /**
     * panel 敏感词管理
     *
     * @param        $strWord
     * @param string $mark
     *
     * @return array
     */
    public function signFromMap($strWord, $mark = '*')
    {
        //        $strWord .= 'i';
        //        $len       = mb_strlen($strWord);
        //        $map       = $this->map;
        //        $str       = $rep_str = ''; //可疑敏感词
        //        $sensitive = $sign = [];
        //        $sign_key  = $open_filter = 0; // 开启特殊字符
        //        $special_str = 'T"\'~`!！#￥$%%^……&*（（）)()_——--;；《<>》?？【】{}|\@,，.。、/9⃣️ ';
        //        for ($i = 0; $i < $len; $i++) {
        //            $nowWord = mb_substr($strWord, $i, 1, 'UTF-8');
        //            $map[$nowWord] = isset($map[$nowWord]) ? $map[$nowWord] : '';
        //            echo '<pre>';
        //            print_r( $map[$nowWord]);die;
        //            // 过滤特殊字符
        //            if (strpos($special_str, $map[$nowWord]) !== false) {
        //                if ($open_filter) {
        //                    $rep_str .= $mark;
        //                    continue;
        //                } else {
        //                    $rep_str .= $nowWord;
        //                }
        //            }
        //            if ($map[$nowWord]) {
        //                $open_filter = 1; // 开启特殊字符
        //                if (empty($sign[$sign_key])) {
        //                    $sign[$sign_key][] = $i;
        //                }
        //                $rep_str .= $mark; //字符替换
        //                $str .= $nowWord;
        //                if ($map[$nowWord]['end']) {
        //                    $sign[$sign_key][] = $i;
        //                    $sign_key++;
        //                    $rep_str     = '';
        //                    $sensitive[] = $str; //匹配到的敏感词
        //                    if (count($map[$nowWord]) == 1) {
        //                        $open_filter = 0; // 关闭特殊字符
        //                        $map = $this->map;
        //                        $str = '';
        //                    }
        //                } else {
        //                    $map = $map[$nowWord];
        //                }
        //            } else {
        //                if (!empty($sign[$sign_key])) {
        //                    unset($sign[$sign_key]);
        //                }
        //                $map = $this->map;
        //                if ($map[$nowWord]) {
        //                    $rep_str = $mark; //字符替换
        //                    $str     = $nowWord;
        //                    $map     = $map[$nowWord];
        //                    $open_filter = 1; // 开启特殊字符
        //                } else {
        //                    $rep_str = $str = '';
        //                    $open_filter = 0; // 关闭特殊字符
        //                }
        //            }
        //        }
        //                $strWord .= 'i';
        //                $len         = mb_strlen($strWord);
        //                $tmp         = $this->tmpMap;
        //                $map         = $this->tmpMap;
        //                $str         = ''; //可疑敏感词
        //                $rep_str     = ''; //替换字符
        //                $ret_str     = ''; //返回结果
        //                $sensitive   = []; //敏感词
        //                $open_filter = 0; //开启特殊字符
        //                $sign        = [];
        //                $sign_key    = 0;
        //                $special_str = 'T"\'~`!！#￥$%%^……&*（（）)()_——--;；《<>》?？【】{}|\@,，.。、/9⃣️ ';
        //                for ($i = 0; $i < $len; $i++) {
        //                    $nowWord = mb_substr($strWord, $i, 1);
        //                    $nowMap  = $map->get($nowWord);
        //                    echo '<pre>';
        //                    var_dump($nowWord);
        //                    echo $nowWord;
        //                    //过滤特殊字符
        //                    if (strpos($special_str, $nowWord) !== false) {
        //                        if ($open_filter) {
        //                            $rep_str .= $mark;
        //                            continue;
        //                        } else {
        //                            $rep_str .= $nowWord;
        //                        }
        //                    }
        //                    if (!is_null($nowMap)) {
        //                        if (empty($sign[$sign_key])) {
        //                            $sign[$sign_key][] = $i;
        //                        }
        //                        $open_filter = 1; //开启特殊字符
        //                        $rep_str .= $mark; //字符替换
        //                        $str .= $nowWord;
        //                        if ($nowMap->get('isEnd')) {
        //                            $sign[$sign_key][] = $i;
        //                            $sign_key++;
        //                            $ret_str .= $rep_str;
        //                            $rep_str     = '';
        //                            $sensitive[] = $str; //匹配到的敏感词
        //                            if (count((array)$nowMap) == 1) {
        //                                $map         = $tmp;
        //                                $str         = '';
        //                                $open_filter = 0;
        //                            }
        //                        } else {
        //                            $map = $nowMap;
        //                        }
        //                    } else {
        //                        if (!empty($sign[$sign_key])) {
        //                            unset($sign[$sign_key]);
        //                        }
        //                        if ($str) {
        //                            $ret_str .= $str;
        //                        }
        //                        $map    = $tmp;
        //                        $nowMap = $map->get($nowWord);
        //                        if (!is_null($nowMap)) {
        //                            $open_filter = 1; //开启特殊字符
        //                            $rep_str     = $mark; //字符替换
        //                            $str         = $nowWord;
        //                            $map         = $nowMap;
        //                        } else {
        //                            $ret_str .= $nowWord;
        //                            $rep_str     = '';
        //                            $str         = '';
        //                            $open_filter = 0;
        //                        }
        //                    }
        //                }
        //        P($this->map);
        $len         = mb_strlen($strWord, 'UTF-8');
        $map         = $this->map;
        $str         = $rep_str = '';
        $sensitive   = $replace = [];
        $open_filter = 0; //开启特殊字符
        $special_str = 'T"\'~`!！#￥$%%^……&*（（）)()_——--;；《<>》?？【】{}|\@,，.。、/9⃣️ ';
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($strWord, $i, 1, 'UTF-8');
            echo '<pre>';
            echo $word . PHP_EOL;
//            print_r($map[$word]);
            //过滤特殊字符
//            if (strpos($special_str, $map[$word]) !== false) {
            if (strpos($special_str, $word) !== false) {
                if ($open_filter) {
                    $rep_str .= $mark;
                    continue;
                } else {
                    $rep_str .= $map[$word];
                }
            }
            if (!isset($map[$word])) {
                // reset hashmap
                $map = $this->map;
                $str ? $i-- : '';
                $str         = $rep_str = '';
                $open_filter = 0;
            } else {
                $open_filter = 1; //开启特殊字符
                $rep_str .= $mark;
                $str .= $word;
                if ($map[$word]['end']) {
                    if (count($map[$word]) == 1) {
                        $map         = $this->map;
                        $str         = '';
                        $open_filter = 0;
                    }
                    array_push($sensitive, $str);
                    $str2 = str_repeat($mark, mb_strlen($str, 'UTF-8'));
                    array_push($replace, $str2);
                    $str = '';
                    //                            $map = $this->map;
                } else {
                    $map = $map[$word];
                }
            }
        }
        //        P($sensitive);
        //        $sign_point = $this->signArray($sign);
        $sign_point = '';
        $sensitive  = array_values(array_unique($sensitive));
        $string     = substr($strWord, 0, -1);
        P($sensitive);
        //配合特殊字符
        $rep_content = $this->getSensitiveReplaceWords($sensitive, $string);
        return ['sensitive' => $sensitive, 'content' => $string, 'sign' => $sign_point, 'rs_data' => $rep_content];
    }

    /**
     * 依据dfa算法及特殊字符，找出敏感词进行特殊标记
     *
     * @param $sensitive
     * @param $content
     *
     * @author duke.wang
     * @return mixed
     */
    public function getSensitiveReplaceWords($sensitive, $content)
    {
        $wordObj = new SensitiveTree($this->disturbList);
        $wordObj->addWords($sensitive);
        $words = $wordObj->search($content);
        foreach ($words as $key => $w) {
            $content = str_replace($w, '<span style="color:red;border:1px;">' . $w . '</span>&nbsp;', $content);
        }
        return $content;
    }

    /**
     *
     *
     * @param $array
     *
     * @return array
     */
    public function signArray($array)
    {
        $ret = [];
        if (empty($array)) {
            return $ret;
        }
        $i = 0;
        foreach ($array as $v) {
            if (count($v) != 2) {
                continue;;
            }
            if ($v[0] == $v[1]) {
                $key          = count($ret);
                $ret[$key][1] = $v[0];
                $i            = $key + 1;
            } else {
                $ret[$i] = $v;
                $i++;
            }
        }
        return $ret;
    }

}