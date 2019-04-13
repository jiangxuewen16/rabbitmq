<?php
/**
 * Created by PhpStorm.
 * User: jiang
 * Date: 2019/4/11
 * Time: 20:55
 */

namespace hq\mq;


class MqOperation
{
    private $exchange;

    private $queue;

    private $route;

    private $class;

    private $method;

    /**
     * MqOperation constructor.
     * @param $exchange
     * @param $queue
     * @param $route
     * @param $class
     * @param $method
     */
    public function __construct($exchange, $queue, $route, $class, $method)
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->route = $route;
        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param mixed $exchange
     */
    public function setExchange($exchange): void
    {
        $this->exchange = $exchange;
    }

    /**
     * @return mixed
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param mixed $queue
     */
    public function setQueue($queue): void
    {
        $this->queue = $queue;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route): void
    {
        $this->route = $route;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method): void
    {
        $this->method = $method;
    }

}