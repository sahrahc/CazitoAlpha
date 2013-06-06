<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Dto/GameStatusDto.php');

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
