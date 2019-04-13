<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/11
 * Time: 16:56
 */

namespace hq\mq;

class MqConsumerConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string 交换机
     */
    private $exchange;

    /**
     * @var string 路由
     */
    private $route;

    /**
     * @var string 列队
     */
    private $queue;

    /**
     * @var array
     */
    private $operations;

    /**
     * MqConsumerConfig constructor.
     * @param string $name
     * @param string $exchange
     * @param string $appName
     */
    public function __construct(string $name, string $exchange, string $appName)
    {
        $this->name = $name;
        $this->exchange = $exchange;
        $this->setRoute($exchange);
        $this->setQueue($appName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */ 
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param string $exchange
     */
    public function setRoute(string $exchange): void
    {
        $this->route = $exchange . '.*';
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @param string $appName
     */
    public function setQueue(string $appName): void
    {
        $this->queue = $appName . '.' . $this->name;
    }

    /**
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param array $operations
     * @throws \Exception
     */
    public function setOperations(array $operations): void
    {
        foreach ($operations as $item) {
            if (isset($this->operations[$item['route']])) {
                throw new \Exception('路由重复！');
            }
            $queue = $item['queue'] ?? $this->queue;
            $operation = new MqOperation($this->getExchange(), $queue, $item['route'], $item['class'], $item['method']);

            $this->operations[$item['route']] = $operation;

        }
    }
}