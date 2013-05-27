<?php

class CheaterMessage {

    public $log;
    public $logType;
    public $eventType;
    public $eventData;
    
    function __construct($log, $logType, $eventType, $eventData) {
        $this->log = $log;
        $this->logType = $logType;
        $this->eventType = $eventType;
        $this->eventData = $eventData;
    }
}

?>
