<?php
declare(strict_types=1);

namespace KafkaService\Log;



use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Processor\UidProcessor;

class KafkaLog extends Logger
{
    private $_line_formate = '{"date": "%datetime%", "level": "%level_name%", "channel": "%channel%", "extra": "%extra%", "msg": {%message%}}'."\n";

    private $_log_dir ="/opt/log";

    /**
     * 初始化
     */
    public function __construct(string $name,array $configHandlers,array $handlers = [],array $processors = [],?DateTimeZone $timezone = null,$line_formate =null)
    {
        parent::__construct($name,$handlers,$processors,$timezone);
        foreach($configHandlers as $logname => $handler){
            $stream = new StreamHandler($this->_log_dir."/".$logname.".log");
            //格式
            $line_formate = $line_formate?? $this->_line_formate;
            $stream->setFormatter(new LineFormatter($line_formate));
            //buffer
            if($handler['buffer'] > 0){
                $stream = new BufferHandler($stream, $handler['buffer'], Logger::DEBUG, true, true);
            }
            //过滤器
            $stream = new FilterHandler($stream, explode(",",$handler['levelList']));
            $this->pushHandler($stream);
        }
        //添加logid
        $this->pushProcessor(new UidProcessor(24));
    }

}