<?php
declare(strict_types=1);

namespace KafkaService\Contract;

use KafkaService\Exceptions\KafkaException;

/**
 * producer interface
*/
interface ProducerInterface
{
    /**
     * send the message to broker
     * @param $topicName
     * @param $message
     * @param $key
     *
     *
     * @return void
     * @throws KafkaException
    */
    public function send(string $topicName,string $message,string $key):void;
}