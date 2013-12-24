<?php

/* * ********************************************************************** */

class QueueManager {

    /**
     * Can be customized with different queue settings
     * Using default exchange, rename to getActiveConnection
     * @return AMQPConnection 
     */
    public static function GetConnection() {
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
    public static function GetChannel($amqpConnection) {
        $ch = new AMQPChannel($amqpConnection);
        return $ch;
    }
    
    public static function GetPlayerExchange($amqpChannel) {
        global $amqp_player_exchange;

        $ex = new AMQPExchange($amqpChannel);
        $ex->setName($amqp_player_exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //$ex->setFlags(AMQP_DURABLE | AMQP_PASSIVE);
        $ex->setFlags(AMQP_DURABLE);
        $ex->declareExchange();
        return $ex;
    }

    public static function GetSessionExchange($amqpChannel) {
        global $amqp_session_exchange;

        $ex = new AMQPExchange($amqpChannel);
        $ex->setName($amqp_session_exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //$ex->setFlags(AMQP_DURABLE | AMQP_PASSIVE);
        $ex->setFlags(AMQP_DURABLE);
        $ex->declareExchange();
        return $ex;
    }
    
    public static function GetChatExchange($amqpChannel) {
        global $amqp_chat_exchange;

        $ex = new AMQPExchange($amqpChannel);
        $ex->setName($amqp_chat_exchange);
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //$ex->setFlags(AMQP_DURABLE | AMQP_PASSIVE);
        $ex->setFlags(AMQP_DURABLE);
        $ex->declareExchange();
        return $ex;
    }

    /*     * ********************************************************************** */

    /**
     * A queue that already exists is reset instead of added
     * Create a queue for use by a user with the appropriate
     * expiration and size configurations. Reuses an already existing
     * queue. 
     * Usage: when the player first sits in a table.
     */
    public static function addPlayerQueue($playerId, $ch) {
        global $amqp_player_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        //$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
        $q->setName('p' . $playerId);
        $q->declareQueue();
        $q->purge();
        $q->bind($amqp_player_exchange, 'p' . $playerId);
        return $q;
    }

    /*     * ********************************************************************** */

    /* 
     * A queue that already exists is reset instead of added
     * Queue for the user/client to send requests to the server.
     * Will replace the REST services
     * May create additional queues to partition workload to server
     * The client wil need to be given the server it goes to.
    
     */ 
    public static function addGameSessionQueue($gameSessionId, $ch) {
        global $amqp_session_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        //$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
        $q->setName('s' . $gameSessionId);
        $q->declareQueue();
        $q->purge();
        $q->bind($amqp_session_exchange, 's' . $gameSessionId);
        return $q;
    }
    
    /**
     * Players can chat with each other if within the same table
     * via the sessions's chat queue
     * @global type $amqp_chat_exchange
     * @param type $gameSessionId
     * @param type $ch
     * @return \AMQPQueue
     */
    public static function addSessionChatQueue($gameSessionId, $ch) {
        global $amqp_chat_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        //$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
        $q->setName('i' . $gameSessionId);
        $q->declareQueue();
        $q->purge();
        $q->bind($amqp_chat_exchange, 'i' . $gameSessionId);
        return $q;
    }
    
    /**
     * get an existing queue for continuing game. use AddOrResetPlayerQueue
     * if the user is first joining a table so the queue is purged
     */
    public static function GetPlayerQueue($playerId, $ch) {
        global $amqp_player_exchange;
        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('p' . $playerId);
        $q->declareQueue();
        $q->bind($amqp_player_exchange, 'p' . $playerId);
        return $q;
    }

   /**
     * get an existing queue
     */
    public static function GetGameSessionQueue($gameSessionId, $ch) {
        global $amqp_session_exchange;
        //$ex = QueueManager::getPlayerExchange($ch);
        //$q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('s' . $gameSessionId);
        $q->declareQueue();
        $q->bind($amqp_session_exchange, 's' . $gameSessionId);
        return $q;
    }

    public static function GetSessionChatQueue($gameSessionId, $ch) {
        global $amqp_chat_exchange;
        //$ex = QueueManager::getPlayerExchange($ch);
        //$q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

        $q = new AMQPQueue($ch);
        $q->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
        $q->setName('i' . $gameSessionId);
        $q->declareQueue();
        $q->bind($amqp_chat_exchange, 'i' . $gameSessionId);
        return $q;
    }

    public static function SendToPlayer($ex, $playerId, $msg) {
        $ex->publish($msg, 'p' . $playerId);
    }

    /*     * ********************************************************************** */

    /**
     * A queue should be purged when a user leaves a table so that 
     * messages from the other session do not interfere another session.
     * @param type $userId 
     */
    public static function DeleteQueue($q) {
        $q->delete();
    }

    /*     * ********************************************************************** */

    public static function DisconnectQueue($conn) {
        $conn->disconnect();
    }

}

?>
