<?php
/**
 * User: yongli
 * Date: 17/4/19
 * Time: 16:42
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use Config\Services;

/**
 * Class YP_Pagination 分页库
 *
 * @package YP\Libraries
 */
class YP_Pagination
{
    /**
     * 分页链接的基础的URL
     *
     * @var string
     */
    protected $base_url = '';

    /**
     * 前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * Suffix
     *
     * @var    string
     */
    protected $suffix = '';

    /**
     * 数据总条数
     *
     * @var int
     */
    protected $total_rows = 0;

    /**
     * 显示几个数字链接
     *
     * @var int
     */
    protected $num_links = 2;

    /**
     * 每页显示多少条
     *
     * @var int
     */
    public $per_page = 10;

    /**
     * 当前页
     *
     * @var int
     */
    public $cur_page = 0;

    /**
     * 使用页码标志
     *
     * @var bool
     */
    protected $use_page_numbers = false;

    /**
     * 第一页链接
     *
     * @var string
     */
    protected $first_link = '&lsaquo; First';

    /**
     * 下一页链接
     *
     * @var    string
     */
    protected $next_link = '&gt;';

    /**
     * 上一页链接
     *
     * @var    string
     */
    protected $prev_link = '&lt;';

    /**
     * 最后一页链接
     *
     * @var    string
     */
    protected $last_link = 'Last &rsaquo;';

    /**
     * URI Segment
     *
     * @var    int
     */
    protected $uri_segment = 0;

    /**
     * 完整的标签打开
     *
     * @var    string
     */
    protected $full_tag_open = '';

    /**
     * 完整的标签关闭
     *
     * @var    string
     */
    protected $full_tag_close = '';

    /**
     * 第一页标签打开
     *
     * @var    string
     */
    protected $first_tag_open = '';

    /**
     * 第一页标签关闭
     *
     * @var    string
     */
    protected $first_tag_close = '';

    /**
     * 最后一个标签打开
     *
     * @var    string
     */
    protected $last_tag_open = '';

    /**
     * 最后一个标签关闭
     *
     * @var    string
     */
    protected $last_tag_close = '';

    /**
     * 第一页URL
     *
     * @var string
     */
    protected $first_url = '';

    /**
     * Current tag open
     *
     * @var    string
     */
    /**
     * 当前标签打开
     *
     * @var string
     */
    protected $cur_tag_open = '<strong>';

    /**
     * 当前标签关闭
     *
     * @var    string
     */
    protected $cur_tag_close = '</strong>';

    /**
     * 下一页标签打开
     *
     * @var    string
     */
    protected $next_tag_open = '';

    /**
     * 下一页标签关闭
     *
     * @var    string
     */
    protected $next_tag_close = '';

    /**
     * 上一页标签打开
     *
     * @var    string
     */
    protected $prev_tag_open = '';

    /**
     * 上一页标签关闭
     *
     * @var    string
     */
    protected $prev_tag_close = '';

    /**
     * 数字标签打开
     *
     * @var    string
     */
    protected $num_tag_open = '';

    /**
     * 数字标签关闭
     *
     * @var    string
     */
    protected $num_tag_close = '';

    /**
     * 分页查询字符串标识
     *
     * @var bool
     */
    protected $page_query_string = false;

    /**
     * 当前页码参数,查询字符串分割参数
     *
     * @var string
     */
    protected $query_string_segment = 'per_page';

    /**
     * 是否显示页码
     *
     * @var bool true: 显示页码 false: 不显示
     */
    protected $display_pages = true;

    /**
     * 设置链接样式属性
     *
     * @var string
     */
    protected $_attributes = '';

    /**
     * 链接类型
     *
     * @var array
     */
    protected $_link_types = [];

    /**
     * 是否保留查询条件
     *
     * @var    bool  true: 保留 false: 不保留
     */
    protected $reuse_query_string = true;

    /**
     * 是否使用全局URL后缀标志
     *
     * @var bool true: 使用 false: 不使用
     */
    protected $use_global_url_suffix = false;

    /**
     * 数字分页属性
     *
     * @var string
     */
    protected $data_page_attr = 'data-yp-pagination-page';

    /**
     * 请求对象
     *
     * @var mixed|\YP\Core\YP_IncomingRequest
     */
    public $request;

    /**
     * @var mixed|\YP\Core\YP_Uri
     */
    public $uri;

    /**
     * 是否使用查询字符串
     *
     * @var string
     */
    protected $enable_query_strings = 'false';

    /**
     * url 后缀
     *
     * @var string
     */
    protected $url_suffix = '.shtml';

    /**
     * 链接的样式
     *
     * @var string
     */
    protected $anchor_class = '';

    /**
     * YP_Pagination constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->request = Services::request();
        $this->uri     = Services::uri();
        $this->initialize($params);
        log_message('info', '分页类已初始化');
    }

    /**
     * 初始化
     *
     * @param array $params 初始化参数
     *
     * @return YP_Pagination
     */
    public function initialize(array $params = []): self
    {
        if (isset($params['attributes']) && is_array($params['attributes'])) {
            $this->_parse_attributes($params['attributes']);
            unset($params['attributes']);
        }
        if (isset($params['anchor_class'])) {
            empty($params['anchor_class']) OR $attributes['class'] = $params['anchor_class'];
            unset($params['anchor_class']);
        }
        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        if ($this->enable_query_strings === true) {
            $this->page_query_string = true;
        }
        if ($this->use_global_url_suffix === true) {
            $this->suffix = $this->url_suffix;
        }

        return $this;
    }

    /**
     * 生成分页链接
     *
     * @return string
     */
    public function create_links()
    {
        // 如果数据的总条数或每页总数为零，就没有必要继续
        if ($this->total_rows == 0 OR $this->per_page == 0) {
            return '';
        }
        // 计算页面总数
        $num_pages = (int)ceil($this->total_rows / $this->per_page);
        if ($num_pages === 1) {
            return '';
        }
        // 检查用户定义的链接数量
        $this->num_links = (int)$this->num_links;
        if ($this->num_links < 0) {
            show_error('数字链接的个数必须是非负的整数');
        }
        // 保留任何现有的查询字符串项目
        if ($this->reuse_query_string === true) {
            $get = $this->request->getGet();
            // 删除控制器、方法和老式的路由
            unset($get['c'], $get['m'], $get[$this->query_string_segment]);
        } else {
            $get = [];
        }
        // 向链接中放入基础Url和第一个链接的url
        // 注意: 不要追加属性，因为它会中断连续调用
        $base_url = trim($this->base_url);
        $first_url        = $this->first_url;
        $query_string     = '';
        $query_string_sep = (strpos($base_url, '?') === false) ? '?' : '&amp;';
        // 是否使用查询字符串,所谓查询字符串就是用 '&' 符连接所有的查询参数
        if ($this->page_query_string === true) {
            // 如果自定义first_url没有指定，我们将创建一个从base_url
            if ($first_url === '') {
                $first_url = $base_url;
                // 将$_GET参数追加到链接中去
                if (!empty($get)) {
                    $first_url .= $query_string_sep . http_build_query($get);
                }
            }
            // 在数字链接插入的地方添加per_page结束查询字符串
            $base_url .= $query_string_sep . http_build_query(array_merge($get, [$this->query_string_segment => '']));
        } else {
            // 标准的url段模式
            // 在页码后添加生成保存的查询字符串
            if (!empty($get)) {
                $query_string = $query_string_sep . http_build_query($get);
                $this->suffix .= $query_string;
            }
            // Does the base_url have the query string in it?
            // If we're supposed to save it, remove it so we can append it later.
            if ($this->reuse_query_string === true && ($base_query_pos = strpos($base_url, '?')) !== false) {
                $base_url = substr($base_url, 0, $base_query_pos);
            }
            if ($first_url === '') {
                $first_url = $base_url . $query_string;
            }
            $base_url = rtrim($base_url, '/') . '/';
        }
        // 当前页码数值
        $base_page = ($this->use_page_numbers) ? 1 : 0;
        // 是否使用查询字符串
        if ($this->page_query_string === true) {
            $this->cur_page = $this->request->getGet($this->query_string_segment);
        } else {
            // 如果uri段一个都没定义,就默认为url段的最后一个
            if ($this->uri_segment === 0) {
                $this->uri_segment = $this->uri->getTotalSegments();
            }
            $this->cur_page = $this->uri->getSegment($this->uri_segment);
            // Remove any specified prefix/suffix from the segment.
            if ($this->prefix !== '' OR $this->suffix !== '') {
                $this->cur_page = str_replace([$this->prefix, $this->suffix], '', $this->cur_page);
            }
        }
        // If something isn't quite right, back to the default base page.
        if (!ctype_digit($this->cur_page) OR ($this->use_page_numbers && (int)$this->cur_page === 0)) {
            $this->cur_page = $base_page;
        } else {
            // Make sure we're using integers for comparisons later.
            $this->cur_page = (int)$this->cur_page;
        }
        // Is the page number beyond the result range?
        // If so, we show the last page.
        if ($this->use_page_numbers) {
            if ($this->cur_page > $num_pages) {
                $this->cur_page = $num_pages;
            }
        } elseif ($this->cur_page > $this->total_rows) {
            $this->cur_page = ($num_pages - 1) * $this->per_page;
        }
        $uri_page_number = $this->cur_page;
        // If we're using offset instead of page numbers, convert it
        // to a page number, so we can generate the surrounding number links.
        if (!$this->use_page_numbers) {
            $this->cur_page = (int)floor(($this->cur_page / $this->per_page) + 1);
        }
        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with.
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;
        // 输出页码
        $output = '';
        // 渲染第一页链接
        if ($this->first_link !== false && $this->cur_page > ($this->num_links + 1 + !$this->num_links)) {
            // Take the general parameters, and squeeze this pagination-page attr in for JS frameworks.
            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, 1);
            $output .= $this->first_tag_open . '<a href="' . $first_url . '"' . $attributes . $this->_attr_rel('start') . '>' . $this->first_link . '</a>' . $this->first_tag_close;
        }
        // 渲染上一页链接
        if ($this->prev_link !== false && $this->cur_page !== 1) {
            $i          = ($this->use_page_numbers) ? $uri_page_number - 1 : $uri_page_number - $this->per_page;
            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int)$i);
            if ($i === $base_page) {
                // 第一页
                $output .= $this->prev_tag_open . '<a href="' . $first_url . '"' . $attributes . $this->_attr_rel('prev') . '>' . $this->prev_link . '</a>' . $this->prev_tag_close;
            } else {
                $append = $this->prefix . $i . $this->suffix;
                $output .= $this->prev_tag_open . '<a href="' . $base_url . $append . '"' . $attributes . $this->_attr_rel('prev') . '>' . $this->prev_link . '</a>' . $this->prev_tag_close;
            }

        }
        // 渲染页码
        if ($this->display_pages !== false) {
            // 写数字链接
            for ($loop = $start - 1; $loop <= $end; $loop++) {
                $i          = ($this->use_page_numbers) ? $loop : ($loop * $this->per_page) - $this->per_page;
                $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int)$i);
                if ($i >= $base_page) {
                    if ($this->cur_page === $loop) {
                        // 当前页
                        $output .= $this->cur_tag_open . $loop . $this->cur_tag_close;
                    } elseif ($i === $base_page) {
                        // 首页
                        $output .= $this->num_tag_open . '<a href="' . $first_url . '"' . $attributes . $this->_attr_rel('start') . '>' . $loop . '</a>' . $this->num_tag_close;
                    } else {
                        $append = $this->prefix . $i . $this->suffix;
                        $output .= $this->num_tag_open . '<a href="' . $base_url . $append . '"' . $attributes . $this->_attr_rel('start') . '>' . $loop . '</a>' . $this->num_tag_close;
                    }
                }
            }
        }
        // 渲染下一页链接
        if ($this->next_link !== false && $this->cur_page < $num_pages) {
            $i          = ($this->use_page_numbers) ? $this->cur_page + 1 : $this->cur_page * $this->per_page;
            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int)$i);
            $output .= $this->next_tag_open . '<a href="' . $base_url . $this->prefix . $i . $this->suffix . '"' . $attributes . $this->_attr_rel('next') . '>' . $this->next_link . '</a>' . $this->next_tag_close;
        }
        // 渲染最后一页链接
        if ($this->last_link !== false && ($this->cur_page + $this->num_links + !$this->num_links) < $num_pages) {
            $i          = ($this->use_page_numbers) ? $num_pages : ($num_pages * $this->per_page) - $this->per_page;
            $attributes = sprintf('%s %s="%d"', $this->_attributes, $this->data_page_attr, (int)$i);
            $output .= $this->last_tag_open . '<a href="' . $base_url . $this->prefix . $i . $this->suffix . '"' . $attributes . '>' . $this->last_link . '</a>' . $this->last_tag_close;
        }
        // Kill double slashes. Note: Sometimes we can end up with a double slash
        // in the penultimate link so we'll kill all double slashes.
        $output = preg_replace('#([^:])//+#', '\\1/', $output);

        // 获得页码的html代码
        return $this->full_tag_open . $output . $this->full_tag_close;
    }

    /**
     * 创建Ajax分页
     *
     * @return string
     */
    public function create_ajax_links()
    {
        // 如果数据的总条数或每页总数为零，就没有必要继续
        if ($this->total_rows == 0 OR $this->per_page == 0) {
            return '';
        }

        // 计算页面总数
        $num_pages = (int)ceil($this->total_rows / $this->per_page);
        if ($num_pages === 1) {
            return '';
        }

        // Set the base page index for starting page number
        $base_page = $this->use_page_numbers ? 1 : 0;
        // 设置当前页
        if ($this->page_query_string === true OR $this->page_query_string === true) {
            if ( $this->request->getGet($this->query_string_segment) != $base_page) {
                $this->cur_page = $this->request->getGet($this->query_string_segment);
                // Prep the current page - no funny business!
                $this->cur_page = (int)$this->cur_page;
            }
        } else {
            if ($_REQUEST['page'] != $base_page) {
                $this->cur_page = $_REQUEST['page'];
                // Prep the current page - no funny business!
                $this->cur_page = (int)$this->cur_page;
            }
        }
        // Set current page to 1 if using page numbers instead of offset
        if ($this->use_page_numbers AND $this->cur_page == 0) {
            $this->cur_page = $base_page;
        }
        $this->num_links = (int)$this->num_links;
        if ($this->num_links < 1) {
            show_error('数字链接的个数不能是非负整数.');
        }
        if (!is_numeric($this->cur_page)) {
            $this->cur_page = $base_page;
        }
        // Is the page number beyond the result range?
        // If so we show the last page
        if ($this->use_page_numbers) {
            if ($this->cur_page > $num_pages) {
                $this->cur_page = $num_pages;
            }
        } else {
            if ($this->cur_page > $this->total_rows) {
                $this->cur_page = ($num_pages - 1) * $this->per_page;
            }
        }
        $uri_page_number = $this->cur_page;
        if (!$this->use_page_numbers) {
            $this->cur_page = floor(($this->cur_page / $this->per_page) + 1);
        }
        // Calculate the start and end numbers. These determine
        // which number to start and end the digit links with
        $start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
        $end   = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;
        // Is pagination being used over GET or POST?  If get, add a per_page query
        // string. If post, add a trailing slash to the base URL if needed
        if ($this->enable_query_strings === true OR $this->page_query_string === true) {
            $this->base_url = rtrim($this->base_url) . '&' . $this->query_string_segment . '=';
        } else {
            $this->base_url = rtrim($this->base_url, '/') . '/';
        }
        // 输出分页
        $output = '';
        // 渲染第一页链接
        if ($this->first_link !== false AND $this->cur_page > ($this->num_links + 1)) {
            $first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;
            $output .= $this->first_tag_open . '<a ' . " onclick='ajax_page(0);return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $this->first_link . '</a>' . $this->first_tag_close;
        }
        // Render the "previous" link
        if ($this->prev_link !== false AND $this->cur_page != 1) {
            if ($this->use_page_numbers) {
                $i = $uri_page_number - 1;
            } else {
                $i = $uri_page_number - $this->per_page;
            }
            if ($i == 0 && $this->first_url != '') {
                $output .= $this->prev_tag_open . '<a ' . " onclick='ajax_page(0);return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $this->prev_link . '</a>' . $this->prev_tag_close;
            } else {
                $i = ($i == 0) ? '' : $this->prefix . $i . $this->suffix;
                $output .= $this->prev_tag_open . '<a ' . " onclick='ajax_page({$i});return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $this->prev_link . '</a>' . $this->prev_tag_close;
            }

        }
        // 渲染页码
        if ($this->display_pages !== false) {
            // 写数字链接
            for ($loop = $start - 1; $loop <= $end; $loop++) {
                if ($this->use_page_numbers) {
                    $i = $loop;
                } else {
                    $i = ($loop * $this->per_page) - $this->per_page;
                }
                if ($i >= $base_page) {
                    if ($this->cur_page == $loop) {
                        $output .= $this->cur_tag_open . $loop . $this->cur_tag_close; // Current page
                    } else {
                        $n = ($i == $base_page) ? '' : $i;
                        if ($n == '' && $this->first_url != '') {
                            $output .= $this->num_tag_open . '<a ' . " onclick='ajax_page(0);return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $loop . '</a>' . $this->num_tag_close;
                        } else {
                            $n = ($n == '') ? '' : $this->prefix . $n . $this->suffix;
                            $output .= $this->num_tag_open . '<a ' . " onclick='ajax_page({$n});return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $loop . '</a>' . $this->num_tag_close;
                        }
                    }
                }
            }
        }
        // 渲染下一页链接
        if ($this->next_link !== false AND $this->cur_page < $num_pages) {
            if ($this->use_page_numbers) {
                $i = $this->cur_page + 1;
            } else {
                $i = ($this->cur_page * $this->per_page);
            }
            $ajax_p = $this->prefix . $i . $this->suffix;
            $output .= $this->next_tag_open . '<a ' . " onclick='ajax_page({$ajax_p});return false;'" . $this->anchor_class . 'href="javascript:void(0)">' . $this->next_link . '</a>' . $this->next_tag_close;
        }
        // 渲染最后一页链接
        if ($this->last_link !== false AND ($this->cur_page + $this->num_links) < $num_pages) {
            if ($this->use_page_numbers) {
                $i = $num_pages;
            } else {
                $i = (($num_pages * $this->per_page) - $this->per_page);
            }
            $ajax_p = $this->prefix . $i . $this->suffix;
            $output .= $this->last_tag_open . '<a ' . " onclick='ajax_page({$ajax_p});'" . $this->anchor_class . 'href="javascript:void(0)">' . $this->last_link . '</a>' . $this->last_tag_close;
        }
        // Kill double slashes.  Note: Sometimes we can end up with a double slash
        // in the penultimate link so we'll kill all double slashes.
        $output = preg_replace("#([^:])//+#", "\\1/", $output);
        // Add the wrapper HTML if exists
        $output = $this->full_tag_open . $output . $this->full_tag_close;

        return $output;
    }

    /**
     * 解析属性
     *
     * @param $attributes
     */
    protected function _parse_attributes($attributes)
    {
        isset($attributes['rel']) OR $attributes['rel'] = true;
        $this->_link_types = ($attributes['rel']) ? ['start' => 'start', 'prev' => 'prev', 'next' => 'next'] : [];
        unset($attributes['rel']);
        $this->_attributes = '';
        foreach ($attributes as $key => $value) {
            $this->_attributes .= ' ' . $key . '="' . $value . '"';
        }
    }

    /**
     * 添加rel属性
     *
     * @param $type
     *
     * @return string
     */
    protected function _attr_rel($type)
    {
        if (isset($this->_link_types[$type])) {
            unset($this->_link_types[$type]);

            return ' rel="' . $type . '"';
        }

        return '';
    }

}
