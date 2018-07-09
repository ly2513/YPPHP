<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 10:54
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace App\Controllers;

use YP\Core\YP_Controller as Controller;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;

class Welcome extends Controller
{
    /**
     * jieba分词测试用例
     * jieba分词组件支持三种分词模式：
     * 1）默认精确模式，试图将句子最精确地切开，适合文本分析；
     * 2）全模式，把句子中所有的可以成词的词语都扫描出来，但是不能解决歧义。（需要充足的字典）
     * 搜寻引擎模式，在精确模式的基础上，对长词再次切分，提高召回率，适合用于搜索引擎分詞。
     * 支持繁体断词
     * 支持自定义詞典
     * @link https://github.com/fukuball/jieba-php
     */
    public function testJieBa()
    {
        //        ini_set('memory_limit', '1024M');
        Jieba::init();
        Finalseg::init();
        $seg_list = Jieba::cut("怜香惜玉也得要看对象啊！");
        P($seg_list);
        $seg_list = Jieba::cut("我来到北京清华大学", true);
        P($seg_list); #全模式
        $seg_list = Jieba::cut("我来到北京清华大学", false);
        P($seg_list); #默認精確模式
        $seg_list = Jieba::cut("他来到了网易杭研大厦");
        P($seg_list);
        $seg_list = Jieba::cutForSearch("小明硕士毕业于中国科学院计算所，后在日本京都大学深造"); #搜索引擎模式
        P($seg_list);
    }

}
