<?php
declare(strict_types=1);

namespace KafkaService;


use KafkaService\Contract\ProducerInterface;
use KafkaService\Exceptions\KafkaException;
use KafkaService\Helper\Helper;
use KafkaService\KafkaConfig\KafkaConfig;
use RdKafka\Producer as RdProducer;
use RdKafka\Conf;
use RdKafka\Exception;

class Producer implements ProducerInterface
{

    /**
     * producer instance
    */
    private $producer;

    public function __construct(KafkaConfig $kafkaProducerConfig)
    {
        $conf = new Conf();
        $conf->set("api.version.request", $kafkaProducerConfig->apiVersionRequest);
        $conf->set("broker.version.fallback",$kafkaProducerConfig->brokerVersionFallback);
        $conf->set('metadata.broker.list', $kafkaProducerConfig->brokerList);
        $conf->set('session.timeout.ms', $kafkaProducerConfig->sessionTimeoutMs);
        $keyTabFile = $kafkaProducerConfig->keyTabFile;
        $accounts = $kafkaProducerConfig->kerberosPrincipal;
        if ($accounts && $keyTabFile) {
            $conf->set('security.protocol', $kafkaProducerConfig->securityProtocol);
            $conf->set('sasl.kerberos.principal', $accounts);
            $conf->set('sasl.kerberos.keytab', $keyTabFile);
            $conf->set('sasl.kerberos.kinit.cmd',$kafkaProducerConfig->kinitCmd);
            $conf->set('sasl.mechanisms',$kafkaProducerConfig->mechanism);
        }
        //init RdProducer
        $this->producer = new RdProducer($conf);
        $this->producer->addBrokers($kafkaProducerConfig->brokerList);
        $this->producer->setLogLevel($kafkaProducerConfig->logLevel);
    }

    /**
     * @inheritDoc
     */
    public function send(string $topicName , string $message,string $key): void
    {
        //send message to broker
        try{
            $this->producer->newTopic($topicName)->produce(RD_KAFKA_PARTITION_UA,0,$message,$key);
        }catch(KafkaException $e){
            Helper::getLogger()->error("kafka-send-message-exception is ".$e->getMessage());
        }catch(Exception $ex){
            Helper::getLogger()->error("kafka-send-message-exception is ".$ex->getMessage());
        }
    }
}