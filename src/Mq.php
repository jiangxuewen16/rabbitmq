<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/8
 * Time: 17:07
 */

namespace hq\mq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Mq
{
    private $config = array(
        'host' => '127.0.0.1',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'vhost' => '/',

        'exchange_type' => 'topic',     //默认topic类型
        'exchange_key' => '',

        'passive' => false,     //查询某一个队列是否已存在，如果不存在，不想建立该队列
        'durable' => true,      //是否持久化
        'auto_delete' => false, //是否自动删除

        'exclusive' => false,   //队列的排他性
        'no_local' => false,
        'no_ack' => false,       //是否需不需要应答
        'nowait' => false,      //该方法需要应答确认
        'consumer_tag' => ''

    );

    public const ALLOW_EXCHANGE_TYPE = ['topic', 'direct', 'fanout', 'header'];

    /**
     * @var AMQPStreamConnection 连接
     */
    private $connection;

    /**
     * @var AMQPChannel 消息通道
     */
    private $channel;

    /**
     * Mq constructor.
     * @param array $config 配置信息
     */
    private function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        ['host' => $host, 'port' => $port, 'user' => $user, 'password' => $password, 'vhost' => $vhost] = $this->config;
        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
    }

    /**
     * 连接
     * @param array $config
     * @return Mq
     */
    public static function conn(array $config = []): self
    {
        return new self($config);
    }

    /**
     * 发送消息
     * @param string $routingKey
     * @param string $data json字符串
     * @param array $properties
     * @return $this
     * @throws \Exception
     */
    public function send(string $routingKey, string $data, array $properties = []): self
    {
        if (empty($routingKey) || empty($data)) {
            throw new \Exception('routingKey 和 data 不能为空！');
        }
        /*if (!in_array($type, self::ALLOW_EXCHANGE_TYPE)) {        //todo:目前只支持topic
            throw new BaseException('不支持的交换机类型');
        }*/
        ['exchange_key' => $exchange, 'exchange_type' => $exchangeType, 'passive' => $passive, 'durable' => $durable,
            'auto_delete' => $autoDelete] = $this->config;

        if (empty($exchange)) {
            $exchange = explode('.', $routingKey);
            $exchange = $exchange[0] . '.' . $exchange[1];
        }

        $this->channel->exchange_declare($exchange, $exchangeType, $passive, $durable, $autoDelete);
        $msg = new AMQPMessage($data, $properties);
        $this->channel->basic_publish($msg, $exchange, $routingKey);
        return $this;
    }

    /**
     * 接收消息
     * @param array $routingList
     * @param $callback
     * @return $this
     * @throws \ErrorException
     */
    public function receive(array $routingList, $callback): self
    {
        ['exchange_type' => $exchangeType, 'exclusive' => $exclusive, 'no_ack' => $noAck, 'nowait' => $nowait,
            'passive' => $passive, 'durable' => $durable, 'auto_delete' => $autoDelete,
            'consumer_tag' => $consumerTag, 'no_local' => $noLocal] = $this->config;

        foreach ($routingList as $route) {
            $this->channel->exchange_declare( $route->getExchange(), $exchangeType, $passive, $durable, $autoDelete);
            [$qName, ,] = $this->channel->queue_declare($route->getQueue(), $passive, $durable, $exclusive, $autoDelete);
            $this->channel->queue_bind($qName, $route->getExchange(), $route->getRoute());
            $this->channel->basic_consume($qName, $consumerTag, $noLocal, $noAck, $exclusive, $nowait, $callback);
        }

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        return $this;
    }

    /**
     * 关闭连接
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}