<?php
declare(strict_types=1);

namespace KafkaService\KafkaConfig;


class KafkaConfig
{
   /**
    * metadata.broker.list
    * @var string
   */
   public $brokerList ='node1.com:9094,node2.com":9094';

   /**
    * topic name
    * @var string
   */
   public $topicName = "demo";

   /**
    * api.version.request
    * @var string
   */
   public $apiVersionRequest = 'false';

   /**
    * broker.version.fallback
    * @var string
   */
   public $brokerVersionFallback='0.9.0.1';
   /**
    * session.timeout.ms
    * @var int
   */
   public $sessionTimeoutMs = '35000';

   /**
    * sasl.kerberos.keytab
    * @var string
   */
   public $keyTabFile = '/opt/conf/demo@demo.com.keytab';

   /**
    * sasl.kerberos.principal
    * @var string
   */
   public $kerberosPrincipal = 'demo@demo.com';
   /**
    * security.protocol
    * @var string
   */
   public $securityProtocol = 'sasl_plaintext';

   /**
    * sasl.kerberos.kinit.cmd
    * @var string
   */
   public $kinitCmd = 'kinit -r 5d -k -t "%{sasl.kerberos.keytab}" %{sasl.kerberos.principal}';

   /**
    * log level
    * @var integer
   */
   public $logLevel = LOG_DEBUG;

   /**
    * mechanism
    * @var string
   */
   public $mechanism = 'GSSAPI';

   /**
    * group id
    * @var string
   */
   public $groupId = "demo";

   /**
    * commit
    * @var bool
   */
   public $commit = false;

   /**
    * auto offset reset
    * @var string
   */
   public $offsetReset = 'largest';

   /**
    * sasl.kerberos.min.time.before.relogin
    * @var string
   */
   public $kerberosReloginTime = '3600000';
}