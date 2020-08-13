<?php
declare(strict_types=1);

namespace KafkaService\Contract;

use KafkaService\Exceptions\KafkaException;
use KafkaService\KafkaConfig\KafkaConsumerConfig;

/**
 * consumer interface
*/
interface ConsumerInterface
{
    /**
     * @return void
     * @throws KafkaException
    */
    public function run():void;
}