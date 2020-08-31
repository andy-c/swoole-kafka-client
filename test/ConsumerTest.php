<?php
declare(strict_types=1);

namespace KafkaService\Test;


use KafkaService\Consumer;
use KafkaService\Exceptions\KafkaException;
use PHPUnit\Framework\TestCase;

class ConsumerTest extends TestCase
{
    public function testConsumer(){
        $consumer = $this->getMockBuilder(Consumer::class)
                  ->setConstructorArgs([$this->kafkaConfig])
                  ->setMethods(['run'])
                  ->getMock();
        $consumer->expects($this->once())
                 ->method('run');

        $this->expectException(KafkaException::class);
    }
}