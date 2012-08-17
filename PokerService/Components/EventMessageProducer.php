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
function queueMessage($playerId, $msg) {
    global $log;
    global $rabbitmq_default_host;
    global $rabbitmq_default_exchange;
    $HOST = $rabbitmq_default_host;
    $USER = 'guest';
    $PASS = 'guest';
    $VHOST = $rabbitmq_default_vhost;
    $EXCHANGE = $rabbitmq_default_exchange;
    $QUEUE = '/queue/' . $playerId;

    $ss = new StompSender($HOST, $USER, $PASS, $VHOST, $EXCHANGE);
    $ss->send($msg, $QUEUE);
    $log->debug("Queued message $msg to $playerId");
}

?>
