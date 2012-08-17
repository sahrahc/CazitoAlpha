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
    public $jsonEvent; // FIXME: deleted after migrating to queues
    public $eventData;
    public $log;
    
    function __construct($gSessionId, $targetPlayerId, $eventType, $eventDT, $jsonEvent) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->gameSessionId = $gSessionId;
        $this->targetPlayerId = $targetPlayerId;
        $this->eventType = $eventType;
        $this->eventDateTime = $eventDT;
        $this->jsonEvent = $jsonEvent;
    }

    /**
     * Persists the message in the queue.
     * @return int The message identifier
     */
    function enQueue() {
        $nextId = getNextSequence('EventMessage', 'Id');
        $gameSessionId = $this->gameSessionId;

        executeSQL("INSERT INTO EventMessage (Id, GameSessionId, TargetPlayerId, EventType,
        EventDateTime, JsonEvent, IsDeleted) VALUES ($nextId, $gameSessionId,
                $this->targetPlayerId, '$this->eventType', '$this->eventDateTime', 
                '$this->jsonEvent', 0)",
                __FUNCTION__ . ": Error inserting event message with gen id $nextId");
        return $nextId;
    }

}

?>
