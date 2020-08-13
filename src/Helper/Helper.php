<?php
declare(strict_types=1);

namespace KafkaService\Helper;

use KafkaService\Log\KafkaLog as Log;

class Helper
{
    /**
     * static log
     */
    private static $log = null;

    /**
     * log instance
     */
    public static function getLogger(){
        $handler = [
            "error" =>[
                'buffer' => 50,
                'levelList' =>"ERROR,WARNING,ALERT,CRITICAL,EMERGENCY"
            ],
            "info" =>[
                'buffer' => 0,
                'levelList' =>"INFO,DEBUG"
            ]
        ];
        if(!self::$log){
            self::$log = new Log("eureka-v1",$handler);
        }
        return self::$log;
    }
}