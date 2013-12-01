<?php

/**
 * Producer: generate the message
 */
require_once(dirname(__FILE__) . '/../Components/StompSender.php');

$HOST = 'localhost';
$USER = 'guest';
$PASS = 'guest';
$VHOST = '/';
$EXCHANGE = 'direct';
$QUEUE = '/queue/user2';

    $param1 = $_REQUEST["parameter1"];
    $param2 = $_REQUEST["parameter2"];
    $msg = json_encode(array("Parameter1"=>$param1, "Parameter2"=>$param2));
    
    $ss = new StompSender($HOST, $USER, $PASS, $VHOST, $EXCHANGE);
    $ss->send($msg, $QUEUE);
    
    echo "SENT: " . $msg . "\n";
?>
