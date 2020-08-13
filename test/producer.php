<?php
declare(strict_types=1);

use KafkaService\KafkaConfig\KafkaConfig;
use KafkaService\Producer;
use RdKafka\Conf;

require_once "../vendor/autoload.php";
$kafkaProducerConfig = new KafkaConfig();

$producer = new Producer($kafkaProducerConfig);

//send message
$producer->send($kafkaProducerConfig->topicName,"test",'12131313');