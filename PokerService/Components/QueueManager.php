<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');

// Include Application Scripts
include_once(dirname(__FILE__) . '/../../../libraries/helper/DataHelper.php');
include_once(dirname(__FILE__) . '/../Config.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger(__FILE__);

/* * ********************************************************************** */

class QueueManager {

    /**
     * Use connection per service operation.
     * FIXME: Using default exchange
     * @return AMQPConnection 
     */
    public static function getPlayerConnection() {
        global $amqp_port;
        global $amqp_host;
        global $amqp_vhost;
        global $stomp_exchange;

        $conn = new AMQPConnection();
        /* $conn->setPort($amqp_port);
          $conn->setHost($amqp_host);
          $conn->setVHost($amqp_vhost);
          $conn->setLogin('guest');
          $conn->setPassword('guest');
         */
        if ($conn->connect()) {
            return $conn;
        }

        return null;
    }

    public static function getPlayerExchange($ch) {
        global $amqp_exchange;
        
        $ex = new AMQPExchange($ch);
        $ex->setName($amqp_exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //$ex->setFlags(AMQP_DURABLE | AMQP_PASSIVE);
        $ex->setFlags(AMQP_DURABLE);
        $ex->declare();
        return $ex;
    }

    public static function getPlayerChannel($conn) {
        $ch = new AMQPChannel($conn);
        return $ch;
    }

    /*     * ********************************************************************** */

    public static function disconnect($conn) {
        $conn->disconnect();
    }

    /*     * ********************************************************************** */

    /**
     * A queue should be purged when a user leaves a table so that 
     * messages from the other session do not interfere another session.
     * @param type $userId 
     */
    public static function purgeUserQueue($q) {
        //$q = self::getOrAddUserQueue($userId, $ch);
        $q->purge();
    }

    /*     * ********************************************************************** */

    /**
     * Create a queue for use by a user with the appropriate
     * expiration and size configurations.
     */
    public static function addOrResetPlayerQueue($playerId, $ch) {
        global $amqp_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        //$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
        $q->setName('p' . $playerId);
        $q->declare();
        $q->purge();
        $q->bind($amqp_exchange, 'p' . $playerId);
        return $q;
    }

    /*     * ********************************************************************** */

    /**
     * get an existing queue
     */
    public static function getPlayerQueue($playerId, $ch) {
        global $amqp_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('p' . $playerId);
        //$q->declare();
        $q->bind($amqp_exchange, 'p' . $playerId);
        return $q;
    }

    public static function queueMessage($ex, $playerId, $msg) {    
        //$conn = self::getPlayerConnection();
        //$ch = self::getPlayerChannel($conn);
        //$ex = self::getPlayerExchange($ch);
      $ex->publish($msg, 'p' . $playerId);
    }
    
    /**
     * Communicates the result of a cheating action.
     * @param type $targetPId
     * @param type $gSessionId
     * @param type $infoType
     * @param type $dto 
     */
    public static function communicateCheatingInfo($ex, $targetPId, $gSessionId, $infoType, $dto) {
        global $dateTimeFormat;

        $localTime = date($dateTimeFormat);
        $message = new EventMessage($gSessionId,
                        $targetPId, $infoType, date($dateTimeFormat),
                        $dto);
        self::queueMessage($ex, $targetPId, json_encode($message));
    }

    /**
     * Communicates that a cheating event happened and details such as target player if any,
     * at what time it expires or at what time the lock expires
     */
    public static function communicateCheatingEvent($ex, $actionPId, $gSessionId, $eventType, $log) {
        global $dateTimeFormat;

        $message = new EventMessage($gSessionId,
                        $actionPId, $eventType, date($dateTimeFormat),
                        $log);
        self::queueMessage($ex, $actionPId, json_encode($message));
    }

    /*     * ********************************************************************** */
    /*
     * Queue for the user/client to send requests to the server.
     * Will replace the REST services
     * May create additional queues to partition workload to server
     * The client wil need to be given the server it goes to.
     */

    public static function addPokerServiceQueue() {
        
    }

}

?>
