<?php

function printQueueMessage($envelope, $queue) {
    echo "Message for : " . $envelope->getRoutineKey() . " at " . 
            $envelope->getTimeStamp() . "<br />";
    echo $envelope->getBody() . "\n";
    return false; // stop
}
?>
