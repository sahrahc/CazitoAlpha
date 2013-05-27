
<?php

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

// Declare a new exchange

$ex = new AMQPExchange($ch);
$ex->setName('exchange1');
$ex->setType(AMQP_EX_TYPE_FANOUT);
$ex->declare();

// Create a new queue
//$q = new AMQPQueue('exchange1');
$q = new AMQPQueue($ch);
$q->setFlags(AMQP_DURABLE | AMQP_EXCLUSIVE | AMQP_AUTODELETE);
$q->setName('queue1');
$q->declare();

// Bind it on the exchange to routing.key
$q->bind('exchange1', 'routing.key');

// Publish a message to the exchange with a routing key
$ex->publish('message', 'routing.key');

$i = 0;
function processMessage($envelope, $queue) {
    global $i;
    echo "Message $i: " . $envelope->getBody() . "\n";
    $i++;
    if ($i > 10) {
        return false;
    }
    return false; // stop
}
// Read from the queue
$msg = $q->consume('processMessage');

?>
