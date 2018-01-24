<?php
/**
 * User: yongli
 * Date: 17/4/24
 * Time: 15:21
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Controllers;

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
}
