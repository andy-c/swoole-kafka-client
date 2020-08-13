<?php
declare(strict_types=1);

use KafkaService\KafkaConfig\KafkaConfig;
use KafkaService\Consumer;

require_once "../vendor/autoload.php";
$kafkaConsumerConfig = new KafkaConfig();

$consumer = new Consumer($kafkaConsumerConfig);
//start to consume
$consumer->run();