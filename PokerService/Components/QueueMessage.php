<?php
/* * ************************************************************************************* */

/*
 * Queue messages
 */
class QueueMessage {

    public $eventType;
    public $eventData;
	// game session required to validate stale messages/abandoned tables on client
	public $gameSessionId;
    
    function __construct($eventType, $eventData, $gameSessionId) {
        $this->eventType = $eventType;
        $this->eventData = $eventData;
		$this->gameSessionId = $gameSessionId;
    }

}

?>
