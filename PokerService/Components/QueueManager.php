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
     * Can be customized with different queue settings
     * Using default exchange, rename to getActiveConnection
     * @return AMQPConnection 
     */
    public static function GetQueueConnection() {
        /*global $amqp_port;
        global $amqp_host;
        global $amqp_vhost; 
        global $stomp_exchange; */

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

    /**
     * Allows customization with different settings
     * @param type $conn
     * @return \AMQPChannel
     */
    public static function GetChannel($conn) {
        $ch = new AMQPChannel($conn);
        return $ch;
    }
    
    public static function GetPlayerExchange($ch) {
        global $amqp_exchange;

        $ex = new AMQPExchange($ch);
        $ex->setName($amqp_exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //$ex->setFlags(AMQP_DURABLE | AMQP_PASSIVE);
        $ex->setFlags(AMQP_DURABLE);
        $ex->declare();
        return $ex;
    }

    
    /*     * ********************************************************************** */

    /**
     * get an existing queue for continuing game. use AddOrResetPlayerQueue
     * if the user is first joining a table so the queue is purged
     */
    public static function GetPlayerQueue($playerId, $ch) {
        global $amqp_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('p' . $playerId);
        //$q->declare();
        $q->bind($amqp_exchange, 'p' . $playerId);
        return $q;
    }

    /*     * ********************************************************************** */

    public static function DisconnectQueue($conn) {
        $conn->disconnect();
    }

    /*     * ********************************************************************** */

    /**
     * A queue should be purged when a user leaves a table so that 
     * messages from the other session do not interfere another session.
     * @param type $userId 
     */
    public static function PurgeQueue($q) {
        //$q = self::getOrAddUserQueue($userId, $ch);
        $q->purge();
    }

    /*     * ********************************************************************** */

    /**
     * Create a queue for use by a user with the appropriate
     * expiration and size configurations. Reuses an already existing
     * queue. 
     * Usage: when the player first sits in a table.
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
    */

    public static function GetTableQueue($casinoTableId, $ch) {
        global $amqp_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('t' . $casinoTableId);
        //$q->declare();
        $q->bind($amqp_exchange, 't' . $casinoTableId);
        return $q;
    }
    
    public static function QueueMessage($ex, $playerId, $msg) {
        //$conn = self::getPlayerConnection();
        //$ch = self::getPlayerChannel($conn);
        //$ex = self::getPlayerExchange($ch);
        $ex->publish($msg, 'p' . $playerId);
    }

    /*     * ********************************************************************** */
    /*
     * Queue for the user/client to send requests to the server.
     * Will replace the REST services
     * May create additional queues to partition workload to server
     * The client wil need to be given the server it goes to.
    public static function addOrResetTableQueue($casinoTableId, $ch) {
        global $amqp_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        //$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
        $q->setName('t' . $casinoTableId);
        $q->declare();
        $q->purge();
        $q->bind($amqp_exchange, 't' . $casinoTableId);
        return $q;
    }
    */

   /**
     * get an existing queue
     */
    public static function GetGameSessionQueue($tableId) {
        global $amqp_exchange;
        $qConn = self::GetQueueConnection();
        $ch = QueueManager::GetChannel($qConn);
        //$ex = QueueManager::getPlayerExchange($ch);
        //$q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('s' . $tableId);
        //$q->declare();
        $q->bind($amqp_exchange, 's' . $tableId);
        return $q;
    }

}

?>
