<?php
/**
 * There are three parts to this test. Sequencing is important when
 * testing messaging
 * 
 * 1) Queue_Client needs to be opened in browser first so the client is waiting
 * 2) Queue_Server needs to be executed so that message is generated. See
 *    browser with client update. 
 * 3) Click 'Test Send' to send message to server
 * 4) Queue_Server_Receive will show message from client being consumed.
 * 
 * If the messaging does not work, php scripts will show pending.
 * 
 * This script shows the how the queues need to be created
 * The JS script on the HTML page shows how the queues are used in the browser
 * The last script shows how to consume messages sent by browser to casino
 * table and how to clean up the casino table queue.
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

// Test exclusive exchanges
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
$q2->setFlags(AMQP_DURABLE | AMQP_AUTODELETE);
$q2->setName('user2');
$q2->declare();
$q2->bind('userExchange', 'user2');
 
// Publish a message to the exchange with a rou ting key
$exP->publish('***The message is testing send by server receipt on client by user 1***', 'user1');
$exP->publish('***The message is testing send by server receipt on server by user 2***', 'user2');
$ext1->publish('***Testing casino table 1 exclusive exchange 1 receipt client***', 'routing.key');
$ext2->publish('***Testing casino table 2 exclusive exchange 2 receipt server ***', 'routing.key');

// Read from the queue
echo "Testing message sent and receive </br>";

echo "Open Queue_Client on browser to see message being received on client <br />";
// dont use consume $msg = $q1->consume('processMessage');
// see note on Queue_Server_Receive.php
$msg=$q2->get(AMQP_AUTOACK);
if (is_null($msg)) {
    echo 'No message found...<br/>';
}
else {
    echo "Read message for user 1 as: " . $msg->getBody() . "<br /><br />";
}

echo "Open Queue_Client on browser to see message being received on client <br />";
// dont use consume $msg = $q1->consume('processMessage');
// see note on Queue_Server_Receive.php
$msg2=$qt2->get(AMQP_AUTOACK);
if (is_null($msg2)) {
    echo 'No message found...<br/>';
}
else {
    echo "Read message for user 1 as: " . $msg2->getBody() . "<br /><br />";
}

echo "<br /><br />";
echo "Queue for user1 should be gone because auto-delete.<br/>";
echo "Queue for user2 should still be there because Queue_Client is using it. <br/>";
echo "Queue for table1 and 2 should still be there because not on auto-delete.<br/>";
$cnn->disconnect();
?>
