<?php
declare(strict_types=1);

namespace KafkaService;


use KafkaService\Contract\ConsumerInterface;
use KafkaService\Exceptions\KafkaException;
use KafkaService\KafkaConfig\KafkaConfig;
use Swoole\Coroutine;
use RdKafka\Conf;
use Swoole\Process;
use Swoole\Process\Pool;
use RdKafka\KafkaConsumer;
use KafkaService\Helper\Helper;
use \Exception;

class Consumer implements ConsumerInterface
{

    /**
     * worker number
     * @var integer
    */
    const PARTITION_COUNTS = 4;

    /**
     * kafka consumer config
     * @var KafkaConsumerConfig
    */
    private $kafkaConsumerConfig;

    /**
     * status
     * @var string
    */
    private $status;

    /**
     * running
     * @var int
    */
    const RUNNING = 0;

    /**
     * stopping
     * @var int
    */
    const STOPPING = 1;

    /**
     * stop
     * @var int
    */
    const STOPPED = 2;


    public function __construct(KafkaConfig $kafkaConsumerConfig)
    {
        $this->kafkaConsumerConfig = $kafkaConsumerConfig;
    }


    /**
     * @inheritDoc
     */
    public function run(): void
    {
        try {
            Process::daemon(false,false);
            //init pool
            $pool = new Pool(self::PARTITION_COUNTS,0,0,true);
            //set coroutine config
            Coroutine::set([
                'hook_flags' => SWOOLE_HOOK_ALL,
                'max_coroutine' =>30000,
                'stack_size' => 4096,
                'log_level'  =>SWOOLE_LOG_ERROR,
                'socket_connect_timeout' => 10,
                'socket_timeout' =>10,
                'dns_server' =>'8.8.8.8',
                'exit_condition' => function(){
                    return Coroutine::stats()['coroutine_num'] === 0;
                }
            ]);
            swoole_set_process_name("php-kafka-client-master");
            $pool->on('WorkerStart',function($pool,$workerid){
                $this->workerStart($pool,$workerid);
            });
            $pool->on('WorkerStop',function($pool,$workerid){
                $this->workerStop($pool,$workerid);
            });
            $pool->start();
        }catch(KafkaException $ex){
            Helper::getLogger()->error("kafka-client-error ".$ex->getMessage());
        }catch(\Throwable $et){
            Helper::getLogger()->error("kafka-client-error ".$et->getMessage());
        }
    }

    /**
     * worker start
     * @param $pool
     * @param $workerId
     *
     * @return void
     * @throws KafkaException
    */
    private function workerStart(Pool $pool,int $workerId):void
    {
        $this->status = self::RUNNING;
        //set worker name
        swoole_set_process_name('php-kafka-client-worker');
        //handle signal and shudwon
        $this->handleShutdownAndSignal($workerId);
        $conf = new Conf();
        //set rebalance config
        $conf->setRebalanceCb(function(KafkaConsumer $kafka,int $err,array $partitions){
            switch($err){
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    foreach ($partitions as $partition) {
                        $message = 'topic=' . $partition->getTopic() . ', partition=' . $partition->getPartition() . ', offset=' . $partition->getOffset();
                         Helper::getLogger()->info('start-consume ' . $message);
                    }
                    $kafka->assign($partitions);
                    break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    foreach ($partitions as $partition) {
                        $message = 'topic=' . $partition->getTopic() . ', partition=' . $partition->getPartition() . ', offset=' . $partition->getOffset();
                        Helper::getLogger()->info('restart consume ' . $message);
                    }
                    $kafka->assign(NULL);
                    break;
                default:
                    $er = new Exception($err);
                    Helper::getLogger()->err("fetch-error ".$er->getMessage());
            }
        });

        //set consumer config
        $conf->set("api.version.request", $this->kafkaConsumerConfig->apiVersionRequest);
        $conf->set("broker.version.fallback", $this->kafkaConsumerConfig->brokerVersionFallback);
        $conf->set('group.id', $this->kafkaConsumerConfig->groupId);
        $conf->set('metadata.broker.list', $this->kafkaConsumerConfig->brokerList);
        $commit = $this->kafkaConsumerConfig->commit;
        if ($commit) {
            $commit = 'true';
        } else {
            $commit = 'false';
        }
        $conf->set('enable.auto.commit', $commit);
        $conf->set('session.timeout.ms', $this->kafkaConsumerConfig->sessionTimeoutMs);

        $keyTabFile = $this->kafkaConsumerConfig->keyTabFile;
        $accounts = $this->kafkaConsumerConfig->kerberosPrincipal;
        if ($accounts && $keyTabFile) {
            $conf->set('sasl.kerberos.min.time.before.relogin', '3600000');
            $conf->set('security.protocol', $this->kafkaConsumerConfig->securityProtocol);
            $conf->set('sasl.kerberos.principal', $accounts);
            $conf->set('sasl.kerberos.keytab', $keyTabFile);
            $conf->set('sasl.kerberos.kinit.cmd', $this->kafkaConsumerConfig->kinitCmd);
            $conf->set('sasl.mechanisms',$this->kafkaConsumerConfig->mechanism);
        }

        $conf->set('auto.offset.reset',$this->kafkaConsumerConfig->offsetReset);

        //subscribe topic
        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(explode(",",$this->kafkaConsumerConfig->topicName));

        //start to consumer
        while(!$this->status){
            //call pending signal
            pcntl_signal_dispatch();
            if($this->status) break;
            try{
                $message = $consumer->consume(120*1000);
                switch ($message->err){
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        //TODO
                        Helper::getLogger()->info("message is ".$message->payload.' offset is '.$message->offset);
                        if($commit){
                            $consumer->commitAsync();
                        }
                        break;
                    case  RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        break;
                    case  RD_KAFKA_RESP_ERR__TIMED_OUT:
                        break;
                    default:
                        Helper::getLogger()->error("kafka-consumer-error ".$message->errstr());
                }
            }catch(KafkaException $ex){
                Helper::getLogger()->error("kafka-consumer-error ".$ex->getMessage());
            }
        }
    }

    /**
     * worker stop
     *
     * @param $pool
     * @param $workerId
     *
     * @return void
     * @throws null
    */
    private function workerStop(Pool $pool,int $workerId){
        $this->status = self::STOPPED;
        Helper::getLogger()->info("$workerId has stopped");
    }


    /**
     * handle signal and error
     *
     * @param $workerId
     *
     * @return void
     * @throws KafkaException
    */
    /**
     * long pull process
     *
     */
    private function handleShutdownAndSignal($workerId){
        //register shutdown handler
        register_shutdown_function(function() use ($workerId){
            $errors = error_get_last();
            if($errors && (
                    $errors['type'] === \E_ERROR ||
                    $errors['type'] === \E_PARSE ||
                    $errors['type'] === \E_CORE_ERROR ||
                    $errors['type'] === \E_COMPILE_ERROR ||
                    $errors['type'] === \E_RECOVERABLE_ERROR
                )){
                $mess = $errors['message'];
                $file = $errors['file'];
                $line = $errors['line'];
                $errMsg = "error occured :".$errors["type"].":".$mess."in ".$file."on the ".$line." in workerid ".$workerId;
                Helper::getLogger()->error($errMsg);
            }
        });
       pcntl_signal(SIGTERM,function($signo) use($workerId){
            $this->status = self::STOPPING;
            Helper::getLogger()->info("kafka-client stopping  ,pid is ".$workerId);
        });

    }
}