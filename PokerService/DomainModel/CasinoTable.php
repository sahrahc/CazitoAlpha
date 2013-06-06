<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

//include_once(dirname(__FILE__) . '/../Components/EventMessageProducer.php');
include_once(dirname(__FILE__) . '/../Components/QueueManager.php');

/* * ************************************************************************************** */

/**
 * A virtual casino table with a fixed number of seats. A table exits even if there are no users currently playing. Casino tables are named entities so that users may go back to the same table across sessions.
 * Seat numbers map to specific UI locations by convention. They are stored at the casino table level and the game instance level, as tables may be adjusted to hold different number of players.
 * A player's assigned seat is stored at the table and at the player game level because users who take a seat while a game is being played must wait until the game ends. The seat information while assigning, offering and taking a seat is at the casino table level.
 */
class CasinoTable {

    public $id;
    public $name;
    public $description;
    public $tableMinimum;
    public $numberSeats;
    public $lastUpdateDateTime;
    public $currentGameSessionId;
    public $sessionStartDateTime;
    // log
    private $log;

    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
    }

    /*     * ***************************************************************************** */
    /* gaming */

    /**
     * Calculates the amount of the first and second blind based on table minimums and current game play.
     * @return array{int, int}
     */
    public function FindBlindBetAmounts() {
        if (is_null($this->tableMinimum)) {
            throw new Exception("Empty casino table, cannot find blind bets");
        }
        $blind1 = $this->tableMinimum / 2;
        $blind2 = $blind1 * 2;
        $this->log->Debug(__FUNCTION__ . "Blind 1 is $blind1 and Blind2 is $blind2");
        return array($blind1, $blind2);
    }

    public function IsSessionStale() {
        if (!isset($_SESSION['isSessionStale'])) {
            return $_SESSION['isSessionStale'];
        }
        global $sessionExpiration;
        global $dateTimeFormat;
        $result = executeSQL("SELECT LastUpdateDateTime FROM GameInstance
                WHERE GameSessionId = $this->currentGameSessionId ORDER BY StartDateTime DESC
                ", __FUNCTION__ . ":Error selecting from GameInstance session id
                $this->currentGameSessionId");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            // expiration date time is 24 hours after the last update
            $expirationDateTime = DateTime::createFromFormat($dateTimeFormat, $row[0]);
            $expirationDateTime->add(new DateInterval($sessionExpiration)); // 24 hours
            self::log(" Last Update " . json_encode($expirationDateTime));
            $isSessionStale = new DateTime() > $expirationDateTime ? true : false;
            $_SESSION['isSessionStale'] = $isSessionStale;
            return $isSessionStale;
        }
    }


        /**
     * Creates a new game session on the casino table.
     * Note that casinoTable is updated
     * @param timestamp $statusDT
     * @return GameSession
     */
    public function ResetGameSession($playerId) {
        $statusDT = Context::GetStatusDT();
        $nextSessionId = getNextSequence('GameSession', 'Id');
        executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
            TableMinimum, NumberSeats, StartDateTime,
                    IsPractice) VALUES ($nextSessionId, $playerId,
                $this->tableMinimum, $this->numberSeats,
                    '$statusDT', 0)", __FUNCTION__ .
                ": Error inserting into GameSession with generated id $nextSessionId");
        $this->currentGameSessionId = $nextSessionId;
        $this->sessionStartDateTime = $statusDT;
        $this->lastUpdateDateTime = $statusDT;
        $this->_updateSessionForCasinoTable();
        return new GameSession($$nextSessionId);
    }

    /**
     * Get the number of players in the waiting list of a table.
     * @return int
     */
    public function GetWaitingListSize() {
        $result = executeSQL("SELECT COUNT(1) FROM Player WHERE CurrentCasinoTableId =
                $this->id AND CurrentSeatNumber is null", __FUNCTION__ . ": Error select
                    count of waiting list on casino id $this->id");
        $row = mysql_fetch_array($result);
        return $row[0];
    }

    /**
     * Gets the next player in the waiting list. If none return null.
     * @return int playerId
     */
    public function FindNextWaitingPlayer() {
        $result = executeSQL("SELECT Id from Player WHERE CurrentCasinoTableId = $this->id
                AND CurrentSeatNumber is NULL AND ReservedSeatNumber is NULL
				ORDER BY WaitStartDateTime", __FUNCTION__ . ":
                    Error selecting player without a seat for casino table $this->id");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        return EntityHelper::getPlayer($row[0]);
    }

    /*     * ***************************************************************************** */

    /**
     * Find the player who is reserving or occupying a specific seat at a casino table.
     * @param int $seatNum The seat number being checked
     * @return playerId The player id or null if no player is taking or reserving that seat.
     */
    public function IsSeatTakenOrReservedBy($seatNum) {
        if (is_null($seatNum)) {
            return null;
        }
        $result = executeSQL("SELECT Id FROM Player WHERE CurrentCasinoTableId = $this->id
                AND (CurrentSeatNumber = $seatNum || ReservedSeatNumber = $seatNum)"
                , __FUNCTION__ .
                ": ERROR selecting FROM Player id $this->id, seatnumber $seatNum");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            return $row[0];
        }
        return null;
    }

    /*     * ****************************************************************************** */
    // seat management

    /**
     * Find the lowest numbered seat that is not taken or reserved.
     * @return int
     */
    public function FindAvailableSeat($players) {
        $takenSeats = null;
        for ($i = 0, $j = 0; $i < count($players); $i++) {
            // collect reserved seats
            if ($players[$i]->reservedSeatNumber != null) {
                $takenSeats[$j++] = $players[$i]->reservedSeatNumber;
            }
            // collect occupied seats
            if ($players[$i]->currentSeatNumber != null) {
                $takenSeats[$j++] = $players[$i]->currentSeatNumber;
            }
        }
        // return first seat if no seats are taken.
        if (is_null($takenSeats)) {
            return 0;
        }
        sort($takenSeats);
        // edge case: first seat empty
        if ($takenSeats[0] != 0) {
            return 0;
        }

        $previous = 0;
        for ($i = 0; $i < count($takenSeats); $i++) {
            // this is how the gap is detected
            if ($takenSeats[$i] - 1 > $previous) {
                $emptySeat = $previous + 1;
                $this->log->debug(__FUNCTION__ . "Empty seat found " . $emptySeat);
                return $emptySeat;
            }
            $previous = $takenSeats[$i];
        }
        // edge case: last seats empty
        $seatNumber = count($takenSeats);
        if ($seatNumber < $this->numberSeats) {
            return $seatNumber;
        }
        return null;
    }

    /*     * ****************************************************************************** */

    /*
     * The newly joined player is excluded because status is sent as REST response
     * unless the user is rejoining (may have accidentally closed browser) in 
     * which case includeNewPlayerFlag is set (because queue still available).
     */

    public function CommunicateUserJoined($newPlayer, $allPlayers, $includeNewPlayer = true) {
        $ex = Context::GetQEx;
        $statusDT = Context::GetStatusDT();

        // new user info returned as player includ name and image
        $playerStatusDtos = PlayerStatusDto::mapPlayers($allPlayers, PlayerStatusType::WAITING, true);
        $this->log->debug(__FUNCTION__ . ": Waiting list size " . $this->waitingListSize);
        $playerStatusDtos[0]->waitingListSize = $this->waitingListSize;
        // dynamically adding this
        $eventType = EventType::USER_JOINED;

        for ($i = 0; $i < count($allPlayers); $i++) {
            // newly joined user to receive REST response to REST request
            if ($allPlayers[$i]->id != $newPlayer->id ||
                    ($includeNewPlayer && $allPlayers[$i]->id == $newPlayer->id)) {
                $message = new EventMessage($this->currentGameSessionId, 
                        $allPlayers[$i]->id, $eventType, $statusDT, $playerStatusDtos);
                //$message->eventData = $playerStatusDtos;
                QueueManager::QueueMessage($ex, $allPlayers[$i]->id, json_encode($message));
            }
        }
    }

    /**
     * Sends updated player states
     * @global type $dateTimeFormat
     * @param type $dto
     * @param type $playerDtos
     */
    public function CommunicateUserLeft($departedPlayer, $allPlayers) {
        $ex = Context::GetQEx;
        $waitingListSize = $this->GetWaitingListSize();

        // even though single player, send as array
        $departedPlayerStatusDtos = PlayerStatusDto::mapPlayers(array($departedPlayer), PlayerStatusType::LEFT);
        $departedPlayerStatusDtos[0]->waitingListSize = $waitingListSize;
        $eventType = EventType::USER_LEFT;

        for ($i = 0; $i < count($allPlayers); $i++) {
            // ignore user who left
            if ($allPlayers[$i]->id != $departedPlayer->id) {
                $message = new QueueMessage($eventType, $departedPlayerStatusDtos);
                //$message->eventData = $playerStatusDtos;
                QueueManager::QueueMessage($ex, $allPlayers[$i]->id, json_encode($message));
            }
        }
    }

    public function CommunicateSeatTaken($seatedPlayer, $allPlayers) {
        $ex = Context::GetQEx;
        
        $seatedPlayerStatusDtos = PlayerStatusDto::mapPlayers(array($seatedPlayer), PlayerStatusType::WAITING, true);
        $this->log->debug(__FUNCTION__ . ": Waiting list size " . $this->waitingListSize);
        $seatedPlayerStatusDtos[0]->waitingListSize = $this->waitingListSize;
        $eventType = EventType::SEAT_TAKEN;

        for ($i = 0; $i < count($allPlayers); $i++) {
            $message = new QueueMessage($eventType, $seatedPlayerStatusDtos);
            QueueManager::QueueMessage($ex, $allPlayers[$i]->id, json_encode($message));
        }
    }

    public function CommunicateSeatOffered($waitingPlayerId, $seatNum) {
        $ex = Context::GetQEx;
        
        // TODO: move this to CasinoTable communicate
        $actionType = EventType::SEAT_OFFER;

        $message = new QueueMessage($actionType, $seatNum);
        //$message->eventData = $seatNumber;
        QueueManager::QueueMessage($ex, $waitingPlayerId, json_encode($message));
    }

    /**
     * Also updates casino table
     */
    public function DeleteExpiredGameSessions($expirationDateTime) {
        executeSQL("UPDATE CasinoTable
            SET CurrentGameSessionId = null, SessionStartDateTime = null
            WHERE id in (SELECT c.id FROM CasinoTable c LEFT JOIN 
                GameSession s on s.id = c.CurrentGameSessionId
                WHERE s.LastUpdateDateTime <= $expirationDateTime", __FUNCTION__ . 
                ": Error selecting game sessions that are expired");
        executeSQL("DELETE FROM GameSession WHERE StartDateTime <=
            $expirationDateTime", __FUNCTION__ . ": Error deleting
                expired game sessions");
    }
    
    private function _updateSessionForCasinoTable() {
        $sessionStartDT = $this->sessionStartDateTime;
        $statusDT = $this->lastUpdateDateTime;

        executeSQL("UPDATE CasinoTable SET CurrentGameSessionId = $this->currentGameSessionId,
            SessionStartDateTime = '$sessionStartDT', LastUpdateDateTime = '$statusDT'
            WHERE Id = $this->id", __FUNCTION__ .
                ": Error updating casino's session with generated id $this->currentGameSessionId");
    }

}

?>
