<?php
/**
 * Created by IntelliJ IDEA.
 * User: yongli
 * Date: 2018/3/29
 * Time: 下午4:27
 */
/**
 * Created by IntelliJ IDEA.
 * User: Dylan.li
 * Date: 2018/3/19
 * Time: 下午6:29
 */
namespace App\Libraries;
/**
 * @date       : 2018-02-28
 * @description:
 * 敏感词过滤类
 * 采用DFA算法
 * 装载后数据结构如下：
 *   $words = [
 *       '小' => [
 *           '日' => [
 *               '本' => false,
 *           ],
 *       ],
 *       '日' => [
 *          '本' => [
 *              '鬼' => [
 *                   '子' => false,
 *               ],
 *               '人' => false,
 *           ],
 *       ],
 *   ];
 */
class SensitiveTree
{
    /**
     * 替换码
     *
     * @var string
     */
    private $replaceCode = '*';

    /**
     * 敏感词库集合
     *
     * @var array
     */
    private $trieTreeMap = [];

    /**
     * 干扰因子集合
     *
     * @var array
     */
    private $disturbList = [];

    /**
     * Model_SensitiveTrieTree constructor.
     *
     * @param array $disturbList
     */
    public function __construct($disturbList = [])
    {
        $this->disturbList = $disturbList;
    }

    /**
     * 添加敏感词
     *
     * @param array $txtWords
     */
    /**
     * @param array $wordsList
     */
    public function addWords(array $wordsList)
    {
        foreach ($wordsList as $words) {
            $nowWords = &$this->trieTreeMap;
            $len      = mb_strlen($words);
            for ($i = 0; $i < $len; $i++) {
                $word = mb_substr($words, $i, 1);
                if (!isset($nowWords[$word])) {
                    $nowWords[$word] = false;
                }
                $nowWords = &$nowWords[$word];
            }
        }
    }

    /**
     * 查找对应敏感词
     *
     * @param       $txt
     * @param bool  $hasReplace
     * @param array $replaceCodeList
     *
     * @return array
     */
    public function search($txt, $hasReplace = false, &$replaceCodeList = [])
    {
        $wordsList = [];
        $txtLength = mb_strlen($txt);
        for ($i = 0; $i < $txtLength; $i++) {
            $wordLength = $this->checkWord($txt, $i, $txtLength);
            if ($wordLength > 0) {
                $words       = mb_substr($txt, $i, $wordLength);
                $wordsList[] = $words;
                $hasReplace && $replaceCodeList[] = str_repeat($this->replaceCode, mb_strlen($words));
                $i += $wordLength - 1;
            }
        }
        return $wordsList;
    }

    /**
     * 过滤敏感词
     *
     * @param $txt
     *
     * @return mixed
     */
    public function filter($txt)
    {
        $replaceCodeList = [];
        $wordsList       = $this->search($txt, true, $replaceCodeList);
        if (empty($wordsList)) {
            return $txt;
        }
        return str_replace($wordsList, $replaceCodeList, $txt);
    }

    /**
     * 敏感词检测
     *
     * @param $txt
     * @param $beginIndex
     * @param $length
     *
     * @return int
     */
    private function checkWord($txt, $beginIndex, $length)
    {
        $flag       = false;
        $wordLength = 0;
        $trieTree   = &$this->trieTreeMap;
        for ($i = $beginIndex; $i < $length; $i++) {
            $word = mb_substr($txt, $i, 1);
            if ($this->checkDisturb($word)) {
                $wordLength++;
                continue;
            }
            if (!isset($trieTree[$word])) {
                break;
            }
            $wordLength++;
            if ($trieTree[$word] !== false) {
                $trieTree = &$trieTree[$word];
            } else {
                $flag = true;
            }
        }
        $flag || $wordLength = 0;
        return $wordLength;
    }

    /**
     * 干扰因子检测
     *
     * @param $word
     *
     * @return bool
     */
    private function checkDisturb($word)
    {
        return in_array($word, $this->disturbList);
    }
}