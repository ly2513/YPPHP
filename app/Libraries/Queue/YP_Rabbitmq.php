<?php
/**
 * User: yongli
 * Date: 17/9/22
 * Time: 14:36
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Libraries\Queue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Rabbitmq
 *
 * @package Queue
 */
class YP_Rabbitmq implements YP_QueueInterface {

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config;

    /**
     * 连接
     *
     * @var
     */
    public $connexion;

    /**
     * 消息管道
     *
     * @var
     */
    public $channel;

    /**
     * 是否需要将消息显示到命令端
     *
     * @var bool
     */
    public $show_output;

    /**
     * Rabbitmq constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // 是否必须显示输出
        $this->show_output = ! empty($config['show_output']);
        // 配置信息
        $this->config = $config ? $config : [];
        // 初始化连接
        $this->initialize($this->config);
    }

    /**
     * 初始化
     *
     * @param array $config
     */
    public function initialize(array $config = [])
    {
        // 我们检查是否有一个配置，然后初始化连接
        if (! empty($config)) {
            $this->config    = $config['rabbitmq'];
            $this->connexion = new AMQPStreamConnection($this->config['host'], $this->config['port'],
                $this->config['user'], $this->config['pass'], $this->config['vhost']);
            $this->channel   = $this->connexion->channel();
        } else {
            $this->_outputMessage('Invalid configuration file', 'error', 'x');
        }
    }

    /**
     * 在指定队列中推送消息
     *
     * @param null  $queue
     * @param null  $data
     * @param bool  $permanent
     * @param array $params
     *
     * @return bool
     */
    public function push($queue = NULL, $data = NULL, $permanent = FALSE, $params = [])
    {
        // 我们检查队列是否为空，然后声明队列
        if (! empty($queue)) {
            // 产生一个队列
            $this->channel->queue_declare($queue, FALSE, $permanent, FALSE, FALSE, FALSE, NULL, NULL);
            // 如果给定的信息是个数组，需要将其转换成JSON格式
            $data = (is_array($data)) ? json_encode($data) : $data;
            // 创建一个新的消息实例，然后将其推入选定的队列中
            $item = new AMQPMessage($data, $params);
            // 将消息推送到队列
            $this->channel->basic_publish($item, '', $queue);
            // 输出
            $this->show_output ? $this->_outputMessage('Pushing "' . $item->body . '" to "' . $queue . '" queue -> OK',
                NULL, '+') : TRUE;
        } else {
            $this->_outputMessage('You did not specify the [queue] parameter', 'error', 'x');

            return FALSE;
        }
    }

    /**
     * 从指定的队列中获取项目（此时必须用CLI命令执行）
     *
     * @param null  $queue     指定的队列
     * @param bool  $permanent 队列的模式
     * @param array $callback  自定义回调
     *
     * @return bool
     */
    public function pull($queue = NULL, $permanent = FALSE, array $callback = [])
    {
        // 检查队列是否为空，然后声明队列
        if (! empty($queue)) {
            // 再次声明队列
            $this->channel->queue_declare($queue, FALSE, $permanent, FALSE, FALSE, FALSE, NULL, NULL);
            // 未答复的限制次数
            $this->channel->basic_qos(NULL, 1, NULL);
            // 设置回调用过程的影响
            $this->channel->basic_consume($queue, '', FALSE, FALSE, FALSE, FALSE, $callback);
            // 继续CLI命令的过程，等待其他指令
            while (count($this->channel->callbacks)) {
                $this->channel->wait();
            }
        } else {
            $this->_outputMessage('You did not specify the [queue] parameter', 'error', 'x');

            return FALSE;
        }
    }

    /**
     * 锁住消息
     *
     * @param $message
     */
    public function lock($message)
    {
        $this->channel->basic_reject($message->delivery_info['delivery_tag'], TRUE);
    }

    /**
     * 发布一个消息
     *
     * @param $message
     */
    public function unlock($message)
    {
        $this->channel->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * 将消息从队列移动到另一个队列
     */
    public function move()
    {
        show_error('This method does not exist', NULL, 'RabbitMQ Library Error');
    }

    /**
     * 删除选定队列中的所有内容
     *
     * @param null $queue
     */
    public function purge($queue = NULL)
    {
        show_error('This method does not exist', NULL, 'RabbitMQ Library Error');
    }

    /**
     * 关闭通道和连接
     */
    public function __destruct()
    {
        // 关闭通道
        if (! empty($this->channel)) {
            $this->channel->close();
        }
        // 关闭连接
        if (! empty($this->connexion)) {
            $this->connexion->close();
        }
    }

    /**
     * 将信息输出到命令端
     *
     * @param $message
     * @param null   $type
     * @param string $symbol
     */
    private function _outputMessage($message, $type = NULL, $symbol = '>')
    {
        if (is_cli()) {
            switch ($type) {
                case 'error':
                    echo '[x] RabbitMQ Library Error : ' . $message . PHP_EOL;
                    break;
                default:
                    echo '[' . $symbol . '] ' . $message . PHP_EOL;
                    break;
            }
        } else {
            switch ($type) {
                case 'error':
                    show_error($message, NULL, 'RabbitMQ Library Error');
                    break;
                default:
                    echo $message . '<br>';
                    break;
            }
        }
    }
}
