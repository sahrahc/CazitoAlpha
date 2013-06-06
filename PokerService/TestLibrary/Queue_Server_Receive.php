<?php

/**
 * There are three parts to this test, this is the last.
 * 
 * This script tests that the message send via stomp from the client
 * on the JS script are received on the server (casino table).
 * Note declarations for new and not new are the same. 
 * Check this script to see how queues and exchanges should be deleted
 */
// Create a connection
$cnn = new AMQPConnection();
$cnn->connect();
echo "port: " . $cnn->getPort() . "<br />";
echo "host: " . $cnn->getHost() . "<br />";
echo "vhost: " . $cnn->getVHost() . "<br />";
echo "username: " . $cnn->getLogin() . "<br />";
echo "password: " . $cnn->getPassword() . "<br />";

// Create a channel
$ch = new AMQPChannel($cnn);

// This is the same as for Queue_Server.php
// Bind all queues to an exchange each, messages are exclusively dedicated
// Test exclusive exchanges
$ext1 = new AMQPExchange($ch);
$ext1->setName('tableExchange1');
$ext1->setType(AMQP_EX_TYPE_DIRECT);
$ext1->declare();

// Create table queue /////////////////////////
//$q = new AMQPQueue('exchange1');
$qt = new AMQPQueue($ch);
// the following are settings for casino tables
$qt->setFlags(AMQP_DURABLE);
$qt->setName('table1');
$qt->declare();
$qt->bind('tableExchange1', 'routing.key');

// Read from the queue
echo "Testing message sent by client (Stomp JS) is received on server... <br/>";
//$msg = $qt->consume('processMessage');
// dont' use consume http://www.php.net/manual/en/amqpqueue.ack.php
// resource problems, infinite loop problems and message is not removed from queue
$msg = $qt->get(AMQP_AUTOACK);
if (is_null($msg)) {
    echo 'No message received </br>';
} else {
    echo "Message received by casino table as: " . $msg->getBody() . "<br /><br />";
    echo "Two way queue communication from client to server is successful. Don't forget to run cleanup.<br/>";
}
$cnn->disconnect();
?>
