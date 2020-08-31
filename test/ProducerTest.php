<?php
declare(strict_types=1);

namespace KafkaService\Test;


use KafkaService\Exceptions\KafkaException;
use KafkaService\KafkaConfig\KafkaConfig;
use KafkaService\Producer;
use PHPUnit\Framework\TestCase;

class ProducerTest extends TestCase
{
    protected $kafkaConfig = null;

    public function setUp()
    {
        $this->kafkaConfig = new KafkaConfig();
    }

    public function testProducer(){
        $producer = new Producer($this->kafkaConfig);
        $producer->send("test","test","test");
        $this->expectException(KafkaException::class);
    }

    public function tearDown()
    {
       $this->kafkaConfig = null;
    }
}