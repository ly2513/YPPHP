<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:21
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers;

use App\Libraries\SensitiveFilter;
use YP\Core\YP_Controller as Controller;
use YP\Config\Services;
use YP\Libraries\Thrift\YP_ThriftClient;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 框架默认控制器
 *
 * Class Home
 *
 * @package App\Controllers
 */
class Home extends Controller
{
    /**
     * 框架首页信息
     */
    public function index()
    {
        //        $XHPROF_ROOT  = dirname(ROOT_PATH) . '/xhprof/xhprof_lib/utils/';
        $time         = microtime(true) * 1000;
        $elapsed_time = number_format(($time - START_TIME), 0);
        $this->assign('view_path', 'app/Views/' . $this->controller . '/' . $this->method . $this->extension);
        $this->assign('controller_path', 'app/Controller/' . $this->controller . '.php');
        $this->assign('evn', ENVIRONMENT);
        $this->assign('elapsed_time', $elapsed_time);
        $this->assign('version', VERSION);
        //        $this->assign('doc_url', 'https://ly2513.gitbooks.io/youpin/content/');
        //        $xhprof_data = xhprof_disable();
        //        include_once $XHPROF_ROOT . "xhprof_lib.php";
        //        include_once $XHPROF_ROOT . "xhprof_runs.php";
        //        $xhprof_runs = new \XHProfRuns_Default();
        //        $run_id      = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
        $this->display();
    }

    public function check()
    {
        $param   = file_get_contents('php://input');
        $param   = json_decode($param, true);
        $content = $param['content'];
        $rtn     = intval($param['rtn']);
        $words   = false;
        //避免频繁查库，设置缓存，10分钟查一次
        if ($words == false) {
            $words = \BlackModel::select('word')->where('id', '>', 0)->get()->toArray();
            $words = $words ? array_filter(array_column($words, 'word')) : [];
            $words = $words ? array_unique($words) : [];
        }
        if ($rtn) {
            $wordDst = false;
            if ($wordDst == false) {
                $replaceArr = [];
                foreach ($words as $k) {
                    $replaceArr[$k] = str_repeat('*', mb_strlen($k, 'UTF-8'));
                }
            }
            $wordDst = strtr(strtolower($content), $replaceArr);
            $example = new SensitiveFilter();
            $example->mapList1();
            $sensitive = $example->searchFromMap1($content); //敏感词处理
            P($sensitive);

            $rtnData   = ['content' => $wordDst, 'sensitive' => $sensitive['sensitive']];
            call_back(0, $rtnData);
        } else if (isset($param['content'])) {

            if (!$content) {
                $rtnData['match_num'] = 0;
                call_back(0, $rtnData);
            } else {
                $blackList = "/" . implode('|', $words) . "/i";
                $matches   = [];
                @preg_match_all($blackList, $content, $matches);
                $matchNum             = count($matches[0]);
                $rtnData['match_num'] = $matchNum;
                call_back(0, $rtnData);
            }
        } else {
            call_back(1, '', '参数有误！');
        }
    }

    public function checkSensitiveTest()
    {
        $s_time  = microtime(true);
        $example = new SensitiveFilter();
        $content = '5、 草莓a牛奶事实真相#如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人个视频的主要内容与货拉拉没有任何关联，下图中，左图为原始视频截图，右图为伪造截图，新闻标题是经PS而来。5、 事实真相如何呢？事实上原始视频是湖%@#*南经视在2017年1月26日的新闻视频，但这个视频的主要内容与货拉拉没有任何关联，下图中，左sb图为原始视频截图，右图5、 草莓牛奶事实真相如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人个视频的主要内容与货拉拉没有任艹操你妈何关联，下图中，频的主要内容与货拉拉没有任何关联，下图中，左sb图为原始视频截图，右图5、 草莓牛奶事实真相如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人自由西藏学生运动个视频的主要内容与货拉拉没有任艹操你妈何关联，下图中，左图为原始视频截图，右图为伪造截图，新闻标题是经PS而来。5、 事实真相如何呢？事实上原始视频是湖南经视在2017年1月26日的新闻视频，但这个视频的主要内容与货拉拉没有任何关联，下图中，左图为原始中国支配下的朝鲜经济视频截图，右图为伪造截图，新闻标题是经PS而来。we的为伪造截图，新闻标题是经PS而来。we的';
        /** $content=preg_replace("/[~`!@#$%^&*()_+|\=-}{[]\":;'?><,./？》《，。、“”：；‘’＝—（）｜…￥·！]+/",'',$content); * */
        $punctuation = [
            '~',
            '`',
            '!',
            '！',
            '#',
            '￥',
            '$',
            '%',
            '%',
            '^',
            '……',
            '&',
            '*',
            '（',
            '（',
            '）',
            ')',
            '_',
            '——',
            '-',
            '-',
            ';',
            '；',
            '',
            '，',
            '《',
            '<',
            '>',
            '》',
            '?',
            '？',
            '【',
            '】',
            '{',
            '}',
            '|',
            '\\',
            '@'
        ];
        $content     = str_replace($punctuation, '', $content);
        $example->mapList();
        $result3 = $example->searchFromMap3($content);
        $e_time  = microtime(true);
        echo 'time : ' . ($e_time - $s_time) . PHP_EOL;
        $content = str_replace($result3['result'], $result3['replace'], $content);
        $e1_time = microtime(true);
        echo 'time : ' . ($e1_time - $s_time) . PHP_EOL;
        call_back(0, $content);
    }

    public function checkTest()
    {
        $s_time  = microtime(true);
        $example = new SensitiveFilter();
        $content = '5、 草莓a牛奶事实真相#如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人个视频的主要内容与货拉拉没有任何关联，下图中，左图为原始视频截图，右图为伪造截图，新闻标题是经PS而来。5、 事实真相如何呢？事实上原始视频是湖%@#*南经视在2017年1月26日的新闻视频，但这个视频的主要内容与货拉拉没有任何关联，下图中，左sb图为原始视频截图，右图5、 草莓牛奶事实真相如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人个视频的主要内容与货拉拉没有任艹操你妈何关联，下图中，频的主要内容与货拉拉没有任何关联，下图中，左sb图为原始视频截图，右图5、 草莓牛奶事实真相如何呢？事实上原始视频是湖南经视中国人在2017年1中国人月26日的新闻视频，但这中国男人自由西藏学生运动个视频的主要内容与货拉拉没有任艹操你妈何关联，下图中，左图为原始视频截图，右图为伪造截图，新闻标题是经PS而来。5、 事实真相如何呢？事实上原始视频是湖南经视在2017年1月26日的新闻视频，但这个视频的主要内容与货拉拉没有任何关联，下图中，左图为原始中国支配下的朝鲜经济视频截图，右图为伪造截图，新闻标题是经PS而来。we的为伪造截图，新闻标题是经PS而来。we的';
        /** $content=preg_replace("/[~`!@#$%^&*()_+|\=-}{[]\":;'?><,./？》《，。、“”：；‘’＝—（）｜…￥·！]+/",'',$content); * */
        $punctuation = [
            '~',
            '`',
            '!',
            '！',
            '#',
            '￥',
            '$',
            '%',
            '%',
            '^',
            '……',
            '&',
            '*',
            '（',
            '（',
            '）',
            ')',
            '_',
            '——',
            '-',
            '-',
            ';',
            '；',
            '',
            '，',
            '《',
            '<',
            '>',
            '》',
            '?',
            '？',
            '【',
            '】',
            '{',
            '}',
            '|',
            '\\',
            '@'
        ];
        $content     = str_replace($punctuation, '', $content);
        //                $example->mapList2();
        $example->mapList();
        echo '<pre>';
        //        var_dump($example->getSensitiveFilter());
        //        die;
        //        print_r($example->getSensitiveFilter());
        $result = $example->searchFromMap($content); //敏感词处理
        //        $result2           = $example->searchFromMap2($content);
        //        $result3 = $example->searchFromMap3($content);
        //        $data['match_num'] = count($result);
        //        $data['data']      = $result;
        //        print_r($result);
        //        print_r($result2);
        //        print_r($result3);
        $e_time = microtime(true);
        echo 'time : ' . ($e_time - $s_time) . PHP_EOL;
        print_r($result);
        //        $content = str_replace($result['sensitive'], $result['content'], $content);
        //        $content = str_replace($result['result'], $result['replace'], $content);
        echo $result['content'];
        $e1_time = microtime(true);
        echo 'time : ' . ($e1_time - $s_time) . PHP_EOL;
        die;
        call_back(0, $content);
    }

    public function getString()
    {
        $str = '{"ret":0,"msg":"","data":{"content":"共(产%党共产党党党货操）￥你妈哈哈哈","sensitive":["共产","共产党","操你"],"sign":"","rs_data":"%3Cspan+style%3D%22color%3Ared%3Bborder%3A1px%3B%22%3E%E5%85%B1%28%E4%BA%A7%25%E5%85%9A%3C%2Fspan%3E%26nbsp%3B%3Cspan+style%3D%22color%3Ared%3Bborder%3A1px%3B%22%3E%E5%85%B1%E4%BA%A7%E5%85%9A%E5%85%9A%E5%85%9A%3C%2Fspan%3E%26nbsp%3B%E8%B4%A7%E6%93%8D%EF%BC%89%EF%BF%A5%E4%BD%A0%E5%A6%88%E5%93%88%E5%93%88%E5%93%88"}}';
        $a   = json_decode($str, true);
        //        P($a);
        P($a['data']['rs_data']);
    }

    public function getCheck()
    {
        $param   = file_get_contents('php://input');
        $param   = json_decode($param, true);
        $content = $param['content'];
        $rtn     = intval($param['rtn']);
        if ($rtn) {
            $example = new SensitiveFilter();
            $example->mapList();
            $sensitive = $example->signFromMap($content); //敏感词处理
            $wordDst   = $sensitive['content'];
            echo '<pre>';
            echo $sensitive['rs_data'];
            $rtnData   = [
                'content'   => $wordDst,
                'sensitive' => $sensitive['sensitive'],
                'sign'      => '',//$sensitive['sign'],
                'rs_data'   => urlencode($sensitive['rs_data'])
            ];
//            echo PHP_EOL;
//            $a = '%3Cspan+style%3D%22color%3Ared%3Bborder%3A1px%3B%22%3E%E5%85%B1%28%E4%BA%A7%25%E5%85%9A%3C%2Fspan%3E%26nbsp%3B%3Cspan+style%3D%22color%3Ared%3Bborder%3A1px%3B%22%3E%E5%85%B1%E4%BA%A7%E5%85%9A%E5%85%9A%E5%85%9A%3C%2Fspan%3E%26nbsp%3B%E8%B4%A7%E6%93%8D%EF%BC%89%EF%BF%A5%E4%BD%A0%E5%A6%88%E5%93%88%E5%93%88%E5%93%88';
//            echo PHP_EOL;
//            echo urldecode($a);
            call_back(0, $rtnData);
        } else if (isset($data['content'])) {
            if (!$content) {
                $rtnData['match_num'] = 0;
                call_back(0, $rtnData);
            } else {
                $example = new SensitiveFilter();
                $example->mapList();
                $content              = $example->searchFromMap($content); //敏感词处理
                $rtnData['match_num'] = count($content['sensitive']);
                call_back(0, $rtnData);
            }
        } else {
            call_back(1, '', '参数有误！');
        }
    }

}
