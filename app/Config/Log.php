<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 11:04
 * Email: 626375290@qq.com
 * Copyright: 川雪工作室
 */
namespace Config;

/**
 * 日志配置类
 *
 * Class Log
 *
 * @package Config
 */
class Log
{

    /**
     * 错误日志记录阈值
     *
     * 0 = Disables logging, Error logging TURNED OFF
     * 1 = Emergency Messages  - System is unusable
     * 2 = Alert Messages      - Action Must Be Taken Immediately
     * 3 = Critical Messages   - Application component unavailable, unexpected exception.
     * 4 = Runtime Errors      - Don't need immediate action, but should be monitored.
     * 5 = Debug               - Detailed debug information.
     * 6 = Warnings            - Exceptional occurrences that are not errors.
     * 7 = Notices             - Normal but significant events.
     * 8 = Info                - Interesting events, like user logging in, etc.
     *
     * @var int
     */
    public $threshold = [
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8
    ];

    /**
     * 错误日志的路基
     *
     * @var string
     */
    public $path = '';

    /**
     * 日志的日期格式
     *
     * @var string
     */
    public $dateFormat = 'Y-m-d H:i:s';

    /**
     * 日志处理程序
     *
     * @var array
     */
    public $handlers = [
        // 被处理的日记级别
        'YP\Libraries\LogHandlers\YP_FileHandler' => [
            // 处理程序将处理的日志级别
            'handles'         => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],
            // 日志目录
            'path'            => CACHE_PATH . 'Logs/',
            // 日志文件扩展名称
            'fileExtension'   => 'log',
            // 文件的访问权限
            'filePermissions' => 0644,
        ],
    ];
}
