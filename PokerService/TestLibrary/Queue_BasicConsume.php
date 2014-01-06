<?php
include_once('../Env.php');
include_once('../Config.php');

function getQueueMessage($playerId) {
$HOST = $site;
$PORT = $amqp_port;
$USER = 'guest';
$PASS = 'guest';
$VHOST = $amqp_vhost;
$QUEUE = '/queue/' . $playerId;

try {
  $conn = new AMQPConnection($HOST, $PORT, $USER, $PASS);
  $chan = $conn->channel();
  $chan->queue_declare($QUEUE);

  $msg = $chan->basic_consume($QUEUE);
  //$msg = $chan->basic_get($QUEUE);
  if ($msg) {
    echo "Message: " . $msg->body . "\n";
    //$chan->basic_ack($msg->delivery_info['delivery_tag']);
  }

  $chan->close();
  $conn->close();
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage();
  echo "\nTrace:\n" . $e->getTraceAsString();
}
}
?>
