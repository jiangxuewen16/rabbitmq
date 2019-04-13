<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/9
 * Time: 22:23
 */

namespace hq\mq;

use Exception;

class MqService
{
    protected static $appName = '';

    //private static $exchangeList = ['hq.order', 'hq.user'];

    /*protected static $consumer = [
        [
            'name' => 'order',
            'exchange' => 'hq.order',
            'route' => 'hq.order.*',
            'queue' => 'hq-data-cloud.order',
            'operations' => [
                ['name' => 'create', 'queue' => 'hq-data-cloud.order.create', 'route' => 'hq.order.create', 'class' => TestService::class, 'method' => 'test'],
                ['name' => 'refund', 'queue' => 'hq-data-cloud.order.refund', 'route' => 'hq.order.refund', 'class' => StatisticsSpotService::class, 'method' => 'create'],
            ]
        ],
        'user' => [
            'name' => 'user',
            'exchange' => 'hq.user',
            'route' => 'hq.user.*',
            'queue' => 'hq-data-cloud.user',
            'operations' => [
                ['name' => 'register', 'queue' => 'hq-data-cloud.user.register', 'route' => 'hq.user.register', 'class' => StatisticsSpotService::class, 'method' => 'create'],
                ['name' => 'change', 'queue' => 'hq-data-cloud.user.change', 'route' => 'hq.user.change', 'class' => StatisticsSpotService::class, 'method' => 'create'],
                ['name' => 'disable', 'queue' => 'hq-data-cloud.user.disable', 'route' => 'hq.user.disable', 'class' => StatisticsSpotService::class, 'method' => 'create'],
            ]
        ]
    ];*/

    protected static $consumer = [];

    /**
     * @param array $data
     * @param string $routingKey
     * @param array $config
     * @throws \Exception
     */
    public static function send(array $data, string $routingKey, array $config = [])
    {
        $properties = ['content_type' => 'text/plain', 'delivery_mode' => 2];
        $data = json_encode($data);
        Mq::conn($config)->send($routingKey, $data, $properties)->close();
    }

    /**
     * @param array $config
     * @throws \ErrorException
     * @throws \Exception
     */
    public static function receive($config = [])
    {
        $routes = [];
        foreach (static::$consumer as $item) {
            $consumerConf = new MqConsumerConfig($item['name'], $item['exchange'], static::$appName);
            $consumerConf->setOperations($item['operations']);
            $routes += $consumerConf->getOperations();
        }

        //回调函数->消息处理函数
        $callback = function ($response) use ($routes) {

            try {
                echo ' [x] ', $response->delivery_info['routing_key'], ':', $response->body, "\n";
                $responseData = json_decode($response->body, true);
                $route = $routes[$response->delivery_info['routing_key']];

                //执行消息处理操作
                call_user_func_array([$route->getClass(), $route->getMethod()], [$responseData]);

                //消息应答
                $response->delivery_info['channel']->basic_ack($response->delivery_info['delivery_tag']);
            } catch (Exception $e) {
                echo "消息处理失败[{$response->delivery_info['routing_key']}:{$response->body}:{$response->delivery_info['delivery_tag']}]：{$e->getMessage()}";
            }
        };
        Mq::conn($config)->receive($routes, $callback)->close();
    }

}