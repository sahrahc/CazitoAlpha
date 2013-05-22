<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../Components/StompSender.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger(__FILE__);

/* * ************************************************************************************** */

/**
 *
 * @global type $log
 * @param type $playerId
 * @param type $msg 
 */
function queueMessage($ex, $playerId, $msg) {
    global $log;
    global $stomp_host;
    global $stomp_exchange;
    global $stomp_vhost;

    $HOST = $stomp_host;
    $USER = 'guest';
    $PASS = 'guest';
    $VHOST = $stomp_vhost;
    $EXCHANGE = $stomp_exchange;
    $QUEUE = '/queue/p' . $playerId;

    //$ss = new StompSender($HOST, $USER, $PASS, $VHOST, $EXCHANGE);
    //$ss->send($msg, $QUEUE);
    QueueManager::queueMessage($ex, $playerId, $msg);
    $log->debug("Queued message $msg to $playerId");
}

?>
