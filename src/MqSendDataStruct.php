<?php


namespace hq\mq;


class MqSendDataStruct
{
    //用于无法定义结构的未知结构数据
    private $_data = '';

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    final public function getData(): string
    {
        return $this->_data;
    }

    /**
     * @param string $data
     */
    final public function setData(string $data): void
    {
        $this->_data = $data;
    }


}