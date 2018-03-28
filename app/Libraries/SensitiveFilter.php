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
    public $map = null;
//        public $map = [];
    /**
     * SensitiveFilter constructor.
     */
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
                $tmp = $this->map;
                for ($i = 0; $i < $len; $i++) {
                    $nowWord = mb_substr($word, $i, 1, 'UTF-8');
                    $nowMap  = $this->map->get($nowWord);
                    if (!is_null($nowMap)) {
                        $this->map = $nowMap;
                    } else {
                        $newMap = new SensitiveMap();
                        $newMap->put('isEnd', 0);
                        $this->map->put($nowWord, $newMap);
                        $this->map = $newMap;
                    }
                    $i == ($len - 1) and $this->map->put('isEnd', 1);
                }
                $this->map = $tmp;
        // 传址
//        $map = &$this->map;
//        for ($i = 0; $i < $len; $i++) {
//            $strWord = mb_substr($word, $i, 1, 'UTF-8');
//            // 已存在
//            if (isset($map[$strWord])) {
//                $i == ($len - 1) ? $map[$strWord]['end'] = 1 : '';
//            } else {
//                // 不存在
//                $map[$strWord]['end'] = $i == ($len - 1) ? 1 : 0;
//            }
//            // 传址
//            $map = &$map[$strWord];
//        }
    }

    /**
     * @param $strWord
     *
     * @return array
     */
    public function searchFromMap3($strWord)
    {

        $len       = mb_strlen($strWord);
        $tmp       = $map = $this->map;
        $str       = '';
        $result    = $replace = [];
        $st = $rep_str = $ret_str = '';
        for ($i = 0; $i < $len; $i++) {
            $nowWord = mb_substr($strWord, $i, 1);
            $nowMap  = $map->get($nowWord);
            if (!is_null($nowMap)) {
                $st .= $nowWord;
                $rep_str .= '*';
                $str .= $nowWord;
                if ($nowMap->get('isEnd')) {
                    $ret_str .= $rep_str;
                    array_push($result, $str);
                    $str2 = str_repeat('*', mb_strlen($str, 'UTF-8'));
                    array_push($replace, $str2);
                    $str = '';
                    $map = $tmp;
                } else {
                    $map = $nowMap;
                }
            } else {
                //                if ($words) {
                //                    $ret_str .= $words;
                //                } else {
                $ret_str .= $nowWord;
                //                }
                $rep_str = '';
                $st      = '';
                $str     = '';
                $map     = $tmp;
            }
        }
        return ['result' => $result, 'replace' => $replace];

//        $len    = mb_strlen($strWord);
//        $tmp    = $map = $this->map;
//        $str    = '';
//        $result = $replace = [];
//        for ($i = 0; $i < $len; $i++) {
//            $nowWord = mb_substr($strWord, $i, 1);
//            $nowMap  = $map->get($nowWord);
//            if (!is_null($nowMap)) {
//                $str .= $nowWord;
//                if ($nowMap->get('isEnd')) {
//                    array_push($result, $str);
//                    $str  = '';
//                    $map  = $tmp;
//                    $str2 = str_repeat('*', mb_strlen($str, 'UTF-8'));
//                    array_push($replace, $str2);
//                } else {
//                    $map = $nowMap;
//                }
//            } else {
//                if (!empty($str)) {
//                    $i--;
//                }
//                $str = '';
//                $map = $tmp;
//            }
//        }
//        return ['result' => $result, 'replace' => $replace];

//        $len = mb_strlen($strWord, 'UTF-8');
//        $tmp = $map = $this->map;
//        $str    = '';
//        $result = $replace = [];
//        for ($i = 0; $i < $len; $i++) {
//            $word = mb_substr($strWord, $i, 1, 'UTF-8');
//            if (!isset($map[$word])) {
//                // reset hashmap
////                $map = $this->map;
//                $str ? $i-- : '';
//                $str = '';
//                $map = $tmp;
////                continue;
//            }
//            $str .= $word;
//
//            if ($map[$word]['end']) {
//                array_push($result, $str);
//                $str  = '';
//                $map  = $tmp;
//                $str2 = str_repeat('*', mb_strlen($str, 'UTF-8'));
//                array_push($replace, $str2);
////                return true;
//            }
//            $map = $map[$word];
//        }
//        return ['result' => $result, 'replace' => $replace];
//        return false;
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
        $s_time = microtime(true);
        $data   = \BlackModel::select('word')->where('id', '>', 0)->get()->toArray();
        $data   = array_column($data, 'word');
        $e_time = microtime(true);
        echo '<pre>';
        echo 'time : ' . ($e_time - $s_time) . PHP_EOL;
        foreach ($data as $v) {
            $str = trim($v);
            $this->addWordToMap($str);
        }
    }

    /**
     * 设置map
     */
    public function setSensitiveFilter()
    {
        if (is_null($this->map)) {
            $this->map = new SensitiveMap();
            $this->map->put('isEnd', 0);
        }
        return true;
    }

    /**
     * 返回map
     *
     * @return null
     */
    public function getSensitiveFilter()
    {
        return $this->map;
    }
}