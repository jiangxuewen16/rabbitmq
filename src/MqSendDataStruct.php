<?php


namespace hq\mq;


abstract class MqSendDataStruct
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}