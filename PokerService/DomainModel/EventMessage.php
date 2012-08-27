<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Dto/GameStatusDto.php');

/* * ************************************************************************************* */

class EventMessage {

    public $id;
    public $gameSessionId;
    public $targetPlayerId; // FIXME: deleted after migrating to queues
    public $eventType;
    public $eventDateTime;
    public $eventData;
    private $log;
    
    function __construct($gSessionId, $targetPlayerId, $eventType, $eventDT, $eventData) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->gameSessionId = $gSessionId;
        $this->targetPlayerId = $targetPlayerId;
        $this->eventType = $eventType;
        $this->eventDateTime = $eventDT;
        $this->eventData = $eventData;
    }

}

?>
