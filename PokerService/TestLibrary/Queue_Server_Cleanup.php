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
echo "AMQP read timeout: " . $cnn->getReadTimeout() . "<br />";
echo "AMQP write timeout: " . $cnn->getWriteTimeout() . "<br />";
echo "port: " . $cnn->getPort() . "<br />";
echo "host: " . $cnn->getHost() . "<br />";
echo "vhost: " . $cnn->getVHost() . "<br />";
echo "username: " . $cnn->getLogin() . "<br />";
echo "password: " . $cnn->getPassword() . "<br />";

// Create a channel
$ch = new AMQPChannel($cnn);

// Bind all queues to an exchange each, messages are exclusively dedicated
// try removing autodelete exchange
$ext1 = new AMQPExchange($ch);
$ext1->setName('tableExchange1');
$ext1->setType(AMQP_EX_TYPE_DIRECT);
$ext1->setFlags(AMQP_AUTODELETE);
$ext1->declare();

$ext2 = new AMQPExchange($ch);
$ext2->setName('tableExchange2');
$ext2->setType(AMQP_EX_TYPE_DIRECT);
$ext2->setFlags(AMQP_AUTODELETE);
$ext2->declare();

$ex1 = new AMQPExchange($ch);
$ex1->setName('userExchange1');
$ex1->setType(AMQP_EX_TYPE_DIRECT);
$ex1->setFlags(AMQP_AUTODELETE);
$ex1->declare();

$ex2 = new AMQPExchange($ch);
$ex2->setName('userExchange2');
$ex2->setType(AMQP_EX_TYPE_DIRECT);
$ex2->setFlags(AMQP_AUTODELETE);
$ex2->declare();

// Create table queue /////////////////////////
//$q = new AMQPQueue('exchange1');
$qt = new AMQPQueue($ch);
// the following are settings for casino tables
$qt->setFlags(AMQP_DURABLE);
$qt->setName('table1');
$qt->declare();
$qt->bind('tableExchange1', 'routing.key');

// Create user 1 queue ///////////////////////
$q1 = new AMQPQueue($ch);
$q1->setFlags(AMQP_DURABLE);// | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
$q1->setName('user1');
$q1->declare();
$q1->bind('userExchange1', 'routing.key');
// Create user 2 queue
$q2 = new AMQPQueue($ch);
$q2->setFlags(AMQP_DURABLE);// | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
$q2->setName('user2');
$q2->declare();
$q2->bind('userExchange2', 'routing.key');

echo "Clean up starting...<br/>";
$qt->purge();
$q1->purge();
$q2->purge();
$qt->delete();
$q1->delete();
$q2->delete();

$ext1->delete('tableExchange1');
$ex1->delete('userExchange1');
$ex2->delete('userExchange2');

$cnn->disconnect();
echo "Deleted queues and exchanges for table1, user1 and user2. Cleanup complete.<br/>";
?>
