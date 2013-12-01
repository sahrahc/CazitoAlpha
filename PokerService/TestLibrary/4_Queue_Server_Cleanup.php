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

// copied from Queue_Server.php
// Bind all queues to an exchange each, messages are exclusively dedicated
// try removing autodelete exchange
$ext1 = new AMQPExchange($ch);
$ext1->setName('tableExchange1');
$ext1->setType(AMQP_EX_TYPE_DIRECT);
$ext1->declare();

$ext2 = new AMQPExchange($ch);
$ext2->setName('tableExchange2');
$ext2->setType(AMQP_EX_TYPE_DIRECT);
$ext2->declare();

// Test sharing an exchange for players - not autodelete
$exP = new AMQPExchange($ch);
$exP->setName('userExchange');
$exP->setType(AMQP_EX_TYPE_DIRECT);
$exP->setFlags(AMQP_AUTODELETE);
$exP->declare();

// Create table queue /////////////////////////
$qt1 = new AMQPQueue($ch);
$qt1->setFlags(AMQP_DURABLE);
$qt1->setName('table1');
$qt1->declare();
$qt1->bind('tableExchange1', 'routing.key');

$qt2 = new AMQPQueue($ch);
$qt2->setFlags(AMQP_DURABLE);
$qt2->setName('table2');
$qt2->declare();
$qt2->bind('tableExchange2', 'routing.key');

// Create user 1 queue ///////////////////////
$q1 = new AMQPQueue($ch);
// don't use autodelete, used timeout instead
$q1->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
$q1->setName('user1');
$q1->declare();
$q1->bind('userExchange', 'user1');
// Create user 2 queue 
$q2 = new AMQPQueue($ch);
$q2->setFlags(AMQP_DURABLE | AMQP_AUTODELETE); // this is if created by user
$q2->setName('user2');
$q2->declare();
$q2->bind('userExchange', 'user2');
 
echo "Clean up starting...<br/>";
$qt1->purge();
$qt2->purge();
$q1->purge();
$q2->purge();
$qt1->delete();
$qt2->delete();
$q1->delete();
$q2->delete();

$ext1->delete('tableExchange1');
$ext2->delete('tableExchange2');
$exP->delete('userExchange');

$cnn->disconnect();
echo "Deleted queues and exchanges for table1, user1 and user2. Cleanup complete.<br/>";
?>
