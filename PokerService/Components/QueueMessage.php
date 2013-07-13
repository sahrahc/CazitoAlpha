<?php
/* * ************************************************************************************* */

/*
 * Queue messages
 */
class QueueMessage {

    public $eventType;
    public $eventData;
    
    function __construct($eventType, $eventData) {
        $this->eventType = $eventType;
        $this->eventData = $eventData;
    }

}

?>
