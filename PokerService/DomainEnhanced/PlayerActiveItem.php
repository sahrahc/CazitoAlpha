<?php

/* Type: Object and partial response Dto.
 * Primary Table: none
 * Description: all the poker cards in a game, both community and player
 */
// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

class PlayerActiveItem {

    public $playerId;
    public $gameSessionId;
    public $gameInstanceId;
    public $itemType;
    public $startDateTime;
    public $endDateTime;
    public $lockEndDateTime;
    public $isActive;
    public $isAvailable;
    public $numberCards;
    private $log;

    function __construct($playerId, $gSessionId, $itemType, $statusDT) {
        $this->log = Logger::getLogger(__CLASS__);

        global $dateTimeFormat;
        $this->playerId = $playerId;
        $this->gameSessionId = $gSessionId;
        $this->itemType = $itemType;
        $this->startDateTime = $statusDT;
        $this->numberCards = null;
    }

    function recordItemUse() {
        global $dateTimeFormat;
        /* validate that the record can be inserted first */
        $startDT = $this->startDateTime->format($dateTimeFormat);
        $result = executeSQL("SELECT * from PlayerActiveItem where PlayerId = $this->playerId
                AND ItemType = '$this->itemType' and LockEndDateTime >= '$startDT'
                AND IsActive = 1 and GameSessionId = $this->gameSessionId",
                __FUNCTION__ . ": Error selecting PlayerActiveItem for player $this->playerId
                AND item type $this->itemType");
        if (mysql_num_rows($result) > 0 ) {
            $this->log->warn(__FUNCTION__ . "$this->playerId attempted to reset $this->itemType before it expired");
            return;
        }
        $lockString = $this->lockEndDateTime->format($dateTimeFormat);
        $endString = 'null';
        if (!is_null($this->endDateTime)) {
            $endString = $this->endDateTime->format($dateTimeFormat);
        }
        $numberCards = $this->numberCards;
        if (is_null($numberCards)) {
            $numberCards = 'null';;
        }
        $instanceId = $this->gameInstanceId;
        if (is_null($instanceId)) {
            $instanceId = 'null';
        }
        executeSQL("INSERT INTO PlayerActiveItem (PlayerId, GameSessionId, GameInstanceId, ItemType,
            StartDateTime, EndDateTime, LockEndDateTime, IsActive, IsAvailable, NumberCards)
            VALUES ($this->playerId, $this->gameSessionId, $instanceId, '$this->itemType',
                '$startDT', '$endString', '$lockString',
                $this->isActive, $this->isAvailable, $numberCards)", __FUNCTION__ . "
                : Error inserting PlayerActiveItem for player id $this->playerId and
                item type $this->itemType");
    }

}

?>
