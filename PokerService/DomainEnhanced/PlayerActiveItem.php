<?php

/*
 */

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

    function __construct($playerId, $gSessionId, $itemType) {
        $this->log = Logger::getLogger(__CLASS__);
        $statusDT = Context::GetStatusDT();
        $this->playerId = $playerId;
        $this->gameSessionId = $gSessionId;
        $this->itemType = $itemType;
        $this->startDateTime = $statusDT;
        $this->numberCards = null;
    }

    function RecordItemUse() {
        global $dateTimeFormat;
        /* validate that the record can be inserted first */
        $startDT = $this->startDateTime->format($dateTimeFormat);
        $result = executeSQL("SELECT * from PlayerActiveItem where PlayerId = $this->playerId
                AND ItemType = '$this->itemType' and LockEndDateTime >= '$startDT'
                AND IsActive = 1 and GameSessionId = $this->gameSessionId", __FUNCTION__ . ": Error selecting PlayerActiveItem for player $this->playerId
                AND item type $this->itemType");
        if (mysql_num_rows($result) > 0) {
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
            $numberCards = 'null';
            ;
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

    function SetItemToInactive() {
        // TODO: separate this into update function
        executeSQL("UPDATE PlayerActiveItem SET IsActive = 0 WHERE IsActive = 1 AND
                PlayerId = $this->playerId AND ItemType = '$this->itemType' AND GameSessionId = $this->gameSessionId"
                , __FUNCTION__ . ": Error updating item to inactive for player $this->playerId
                    session $this->gameSessionId and item $this->itemType");
    }

    function Delete() {
        executeSQL("DELETE FROM PlayerActiveItem WHERE PlayerId = $this->playerId AND
                 itemType = '$this->itemType' and GameSessionId = 
                $this->gameSessionId", __FUNCTION__ . ": Error deleting 
                    (unlocking) item for player $this->playerId AND
                 itemType = '$this->itemType' and GameSessionId = $this->gameSessionId");
    }

    public static function GetEndedItems() {
        global $dateTimeFormat;
        $statusDateTime = Context::GetStatusDT();
        /* if end datetime reached: set the IsActive to 0 */
        $result = executeSQL("SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN PlayerState ps ON i.PlayerId = ps.PlayerId 
                WHERE IsActive = 1 AND
            EndDateTime <= '$statusDateTime'", __FUNCTION__ . ": Error selecting ended
                active items");
        $i = 0;
        $endedItems = null;
        while ($row = mysql_fetch_array($result)) {
            $playerId = (int) $row["PlayerId"];
            $sessionId = (int) $row["GameSessionId"];
            $itemType = $row["ItemType"];
            $endedItems[$i] = new PlayerActiveItem($playerId, $sessionId, $itemType);
            $endedItems[$i]->lockEndDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LockEndDateTime"]);
            // the following properties are not part of the object
            $endedItems[$i]->playerSessionId = (int) $row["PlayerSessionId"];
            $endedItems[$i]->playerStatus = $row["Status"];
        }
        return $endedItems;
    }

    public static function GetLockEndedItems() {
        $statusDT = Context::GetStatusDT();
        // FIXME: should record on log
        $result = executeSQL("SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN PlayerState ps ON i.PlayerId = ps.PlayerId 
                AND ps.status != ''
                WHERE LockEndDateTime <= '$statusDT'", __FUNCTION__ . "
                    : Error selecting items past the locked date");
        $i = 0;
        $unlockedItems = null;
        while ($row = mysql_fetch_array($result)) {
            // send communication
            $sessionId = (int) $row["GameSessionId"];
            $playerId = (int) $row["PlayerId"];
            $itemType = $row["ItemType"];
            $unlockedItems[$i] = new PlayerActiveItem($playerId, $sessionId, $itemType);
            // communicate only if the user is in the same session and not left
            $unlockedItems[$i]->playerSessionId = (int) $row["PlayerSessionId"];
            $unlockedItems[$i]->playerStatus = $row["Status"];
        }
        return $unlockedItems;
    }

    /**
     * Public for testing only
     * @param type $pId
     * @param type $gSessionId
     * @param type $itemType
     * @return \PlayerActiveItem
     */
    public static function VerifyPlayerActiveItem($playerId, $gSessionId, $itemType) {
        global $dateTimeFormat;
        $result = executeSQL("SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $gSessionId AND PlayerId = $playerId AND ItemType = '$itemType' AND EndDateTime > now()
                AND IsActive = 1", __FUNCTION__ . "
                : Error selecting active item for player $playerId and session $gSessionId and
                type $itemType");
        $row = mysql_fetch_array($result);
        $activeItem = new PlayerActiveItem($playerId, $gSessionId, $itemType);
        $activeItem->startDateTime = DateTime::createFromFormat($dateTimeFormat, $row['StartDateTime']);
        $activeItem->endDateTime = DateTime::createFromFormat($dateTimeFormat, $row['EndDateTime']);
        $activeItem->lockEndDateTime = DateTime::createFromFormat($dateTimeFormat, $row['LockEndDateTime']);
        $activeItem->isActive = (int) $row['IsActive'];
        $activeItem->isAvailable = (int) $row['IsAvailable'];
        $activeItem->numberCards = (int) $row['NumberCards'];
        return $activeItem;
    }

    /**
     * Public for testing only
     * Gets the list of player Id's who have an un-ended social spotter.
     * @param GameInstance $gInstStatus
     * @return int array
     */
    public static function GetPlayersWithItemType($gSessionId, $itemType) {
        /* returns a list of player ids */
        /* possible for an item to expire in the future but set to inactive before that time */
        $leftStatus = PlayerStatusType::LEFT;
        $result = executeSQL("SELECT i.PlayerId FROM PlayerActiveItem i 
                INNER JOIN PlayerState ps on i.PlayerId = ps.PlayerId 
                AND i.GameSessionId = ps.GameSessionId
                WHERE ps.status != '$leftStatus' AND i.GameSessionId =
            $gSessionId AND ItemType = '$itemType' AND EndDateTime > now() AND IsActive = 1
                ", __FUNCTION__ . "
                : Error selecting active items for session $gSessionId");
        $playerIdList = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerIdList[$counter++] = (int) $row['PlayerId'];
        }
        return $playerIdList;
    }

}

?>
