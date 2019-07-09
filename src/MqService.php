<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/9
 * Time: 22:23
 */

namespace hq\mq;

use ErrorException;
use Exception;
use http\Exception\RuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

class MqService
{
    protected static $config = array(
        'host' => '127.0.0.1',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',

        'exchange_type' => 'topic',     //默认topic类型
        'exchange_key' => '',

        'is_delay' => false,        //是否需要开启延迟队列
        'pre_exchange' => '',       //交换机前缀

        'passive' => false,     //查询某一个队列是否已存在，如果不存在，不想建立该队列
        'durable' => true,      //是否持久化
        'auto_delete' => false, //是否自动删除

        'exclusive' => false,   //队列的排他性
        'no_local' => false,
        'no_ack' => false,       //是否需不需要应答
        'nowait' => false,      //该方法需要应答确认
        'consumer_tag' => ''

    );

    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    public static function getConfig(): array
    {
        return self::$config;
    }

    protected static $appName = '';

    //private static $exchangeList = ['hq.order', 'hq.user'];

    protected static $consumer = [];

    protected static $delays = [];

    /**
     * @param array $data
     * @param string $routingKey 如果为空默认为死信临时缓存交换机
     * @throws \Exception
     */
    public static function send(MqSendDataStruct $data, string $routingKey = ''): void
    {
        $properties = ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT];
        $sendStr = json_encode($data->toArray());
        Mq::conn(static::$config)->send($routingKey, $sendStr, $properties)->close();
    }

    /**
     * @throws ErrorException
     * @throws Exception
     */
    public static function receive(): void
    {
        $routes = [];
        foreach (static::$consumer as $item) {
            $consumerConf = new MqConsumerConfig($item['name'], $item['exchange'], $item['exchange_type'], static::$appName);
            $consumerConf->setOperations($item['operations']);
            $routes += $consumerConf->getOperations();
        }

        //回调函数->消息处理函数
        $callback = static function ($response) use ($routes) {

            try {
                echo ' [x] ', $response->delivery_info['routing_key'], ':', $response->body, "\n";
                $responseData = json_decode($response->body, true);
                $route = $routes[$response->delivery_info['routing_key']];

                //执行消息处理操作
                call_user_func([$route->getClass(), $route->getMethod()], $responseData);

                //消息应答
                $response->delivery_info['channel']->basic_ack($response->delivery_info['delivery_tag']);
            } catch (Exception $e) {
                echo "消息处理失败[{$response->delivery_info['routing_key']}:{$response->body}:{$response->delivery_info['delivery_tag']}]：{$e->getMessage()}";
            }
        };

        $delayCallback = static function ($response) {
            try {
                echo ' [x] ', $response->delivery_info['routing_key'], ':', $response->body, "\n";
                $responseData = json_decode($response->body, true);
                $route = static::$delays[$responseData['tag']];

                //执行消息处理操作
                call_user_func([$route['class'], $route['method']], $responseData['payload']);

                //消息应答
                $response->delivery_info['channel']->basic_ack($response->delivery_info['delivery_tag']);
            } catch (Exception $e) {
                echo "消息处理失败[{$response->delivery_info['routing_key']}:{$response->body}:{$response->delivery_info['delivery_tag']}]：{$e->getMessage()}";
            }
        };


        Mq::conn(static::$config)->setAppName(self::$appName)->receive($routes, $callback, $delayCallback)->close();
    }

    /**
     * @param array $data
     * @param string $key
     */
    public static function sendDelay(array $data, string $key): void
    {
        if (!isset(static::$delays[$key])) {
            throw new RuntimeException('未定义发送key');
        }

        $delayInfo = static::$delays[$key];

        $delayConfig = new DelayConfig($delayInfo['name'], $delayInfo['expiry'], $delayInfo['class'], $delayInfo['method']);
        $properties = ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT];
        Mq::conn(static::$config)->setAppName(self::$appName)->sendDelay($delayConfig, $data, $key, $properties)->close();
    }
}