<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

include_once(dirname(__FILE__) . '/../Components/EventMessageProducer.php');

/* * ************************************************************************************** */

/**
 * A virtual casino table with a fixed number of seats. A table exits even if there are no users currently playing. Casino tables are named entities so that users may go back to the same table across sessions.
 * Seat numbers map to specific UI locations by convention. They are stored at the casino table level and the game instance level, as tables may be adjusted to hold different number of players.
 * A player's assigned seat is stored at the table and at the player game level because users who take a seat while a game is being played must wait until the game ends. The seat information while assigning, offering and taking a seat is at the casino table level.
 */
class CasinoTable {

    public $id;
    public $name;
    public $tableMinimum;
    public $numberSeats;
    public $lastUpdateDateTime;
    public $currentGameSessionId;
    public $sessionStartDateTime;
    // associated and derived objects - loaded as needed.
    //public $playerDtos;
    public $isSessionStale;
    // log
    private $log;

    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
    }

    /*     * ***************************************************************************** */
    // private methods */

    /**
     * Find the player who is reserving or occupying a specific seat at a casino table.
     * @param int $seatNumber The seat number being checked
     * @return playerId The player id or null if no player is taking or reserving that seat.
     */
    private function isSeatTakenOrReservedBy($seatNumber) {
        if (is_null($seatNumber)) {
            return null;
        }
        $result = executeSQL("SELECT Id FROM Player WHERE CurrentCasinoTableId = $this->id
                AND (CurrentSeatNumber = $seatNumber || ReservedSeatNumber = $seatNumber)"
                , __FUNCTION__ .
                ": ERROR selecting FROM Player id $this->id, seatnumber $seatNumber");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            return $row[0];
        }
        return null;
    }

    /**
     * A casino table holds the list of players seating or reserving a seat in an instance of the casino table. This method returns the key within that list. This function can be used to verify whether a player is at a table or not.
     * @param type $playerId
     */
    private function getPlayerKey($playerId, $playerDtos) {
        for ($i = 0; $i < count($playerDtos); $i++) {
            if ($playerDtos[$i]->playerId == $playerId) {
                return $i;
                break;
            }
        }
        return null;
    }

    /**
     * Gets all the players at the table. FIXME: coupling of data and business rules to optimize calls to the database. Caching layer should make data location transparent.
     */
    function loadPlayers() {
        $result = executeSQL("SELECT * FROM Player WHERE CurrentCasinoTableId = $this->id
            ORDER BY CurrentSeatNumber", __FUNCTION__ . ": ERROR selecting from CasinoTable id $this->id");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $players[$i] = new PlayerDto($row["Id"], $row["Name"], $row["ImageUrl"],
                            $row["CurrentSeatNumber"], $row["BuyIn"]);
            $players[$i]->casinoTableId = $this->id;
            $players[$i]->isVirtual = $row["IsVirtual"];
            $players[$i]->reservedSeatNumber = $row["ReservedSeatNumber"];
            $i++;
        }
        return $players;
    }

    /*     * ****************************************************************************** */
    // Public methods with no data access

    /**
     * Find the lowest numbered seat that is not taken or reserved.
     * @return int
     */
    function findAvailableSeat($playerDtos) {
        $takenSeats = null;
        for ($i = 0, $j = 0; $i < count($playerDtos); $i++) {
            // collect reserved seats
            if ($playerDtos[$i]->reservedSeatNumber != null) {
                $takenSeats[$j++] = $playerDtos[$i]->reservedSeatNumber;
            }
            // collect occupied seats
            if ($playerDtos[$i]->currentSeatNumber != null) {
                $takenSeats[$j++] = $playerDtos[$i]->currentSeatNumber;
            }
        }
        // return first seat if no seats are taken.
        if (is_null($takenSeats)) {
            return 0;
        }
        sort($takenSeats);
        // edge case: first seat empty
        if ($takenSeats[0] != 0) {return 0;}
        
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

    /**
     * Calculates the amount of the first and second blind based on table minimums and current game play.
     * @return array{int, int}
     */
    function findBlindBetAmounts() {
        global $defaultTableMin;
        $blind1 = $this->tableMinimum / 2;
        if (is_null($blind1)) {
            $blind1 = $defaultTableMin;
        }
        $blind2 = $blind1 * 2;
        $this->log->Debug(__FUNCTION__ . "Blind 1 is $blind1 and Blind2 is $blind2");
        return array($blind1, $blind2);
    }

    /**
     * Creates a new game session on the casino table.
     * @param timestamp $statusDT
     */
    function createAndSaveGameSession($statusDT) {
        $nextSessionId = getNextSequence('GameSession', 'Id');
        executeSQL("INSERT INTO GameSession (Id, TableMinimum, NumberSeats, StartDateTime,
                    IsPractice) VALUES ($nextSessionId, $this->tableMinimum, $this->numberSeats,
                    '$statusDT', 0)", __FUNCTION__ .
                ": Error inserting into GameSession with generated id $nextSessionId");
        executeSQL("UPDATE CasinoTable SET CurrentGameSessionId = $nextSessionId,
            SessionStartDateTime = '$statusDT', LastUpdateDateTime = '$statusDT'
            WHERE Id = $this->id", __FUNCTION__ .
                ": Error updating casino's session with generated id $nextSessionId");
        $this->currentGameSessionId = $nextSessionId;
        $this->sessionStartDateTime = $statusDT;
        $this->lastUpdateDateTime = $statusDT;
        return new GameSession($this->id, $nextSessionId);
    }

    /**
     * Reserve a seat for a user
     * Validation: verify seat being offered is taken already and user does not have another seat taken or reserved.
     * @param int $seatNumber
     * @param int $playerId
     * @return bool
     */
    function reserveAndOfferSeat($seatNumber, $playerId, $statusDT) {
        global $dateTimeFormat;

        $occupantPlayerId = $this->isSeatTakenOrReservedBy($seatNumber);
        if (!is_null($occupantPlayerId) && $playerId != $occupantPlayerId) {
            throw new Exception("Player $occupantPlayerId already has seat $seatNumber
                    reserved so player id $playerId cannot take it");
        }

        $playerDtos = $this->loadPlayers();

        // if player has a different seat, log error
        $key = $this->getPlayerKey($playerId, $playerDtos);
        if (is_null($key)) {
            throw new Exception("Player $playerId cannot reserve any seats because player is
                    not at table $this->id");
        }

        $currentSeat = $playerDtos[$key]->currentSeatNumber;
        $reservedSeat = $playerDtos[$key]->reservedSeatNumber;
        if ($currentSeat != null && $currentSeat != $seatNumber) {
            throw new Exception("Player $playerId already has seat $currentSeat and cannot
                    take $seatNumber");
        }
        if ($reservedSeat != null && $reservedSeat != $seatNumber) {
            throw new Exception("Player $playerId already has seat $reservedSeat reserved and
                    cannot take $seatNumber");
        }
        $playerDtos[$key]->reservedSeatNumber = $seatNumber;
        try {
            executeSQL("UPDATE Player SET ReservedSeatNumber = $seatNumber,
					LastUpdateDateTime = '$statusDT' WHERE ID =
                    $playerId", __FUNCTION__ . "
                    : Error updating Player id $playerId to reserved seat number $seatNumber");
        } catch (Exception $e) {
            $playerDtos[$key]->reservedSeatNumber = null;
            return false;
        }
        // TODO: place message in queue
        $actionType = EventType::SEAT_OFFER;

        $message = new EventMessage($this->currentGameSessionId, $playerId, $actionType,
                        $statusDT, $actionType, $seatNumber);
        //$message->eventData = $seatNumber;
        queueMessage($playerId, json_encode($message));

        return true;
    }

    /**
     * Converts a reserved seat into a current seat.
     * @param type $seatNumber
     * @param type $playerId
     */
    function takeSeat($seatNumber, $playerId, $playerDtos, $statusDT) {
        if (is_null($seatNumber)) {
            throw new Exception("Missing parameter - Player $playerId cannot reserve empty seat
                    at table $this->id");
        }

        // verify seat is reserved
        $key = $this->getPlayerKey($playerId, $playerDtos);
        if (is_null($key)) {
            throw new Exception("Player $playerId cannot reserve any seats because player is
                    not at table $this->id");
        }

        $occupantPlayerId = $this->isSeatTakenOrReservedBy($seatNumber);
        if ($playerId != $occupantPlayerId) {
            throw new Exception("Player $occupantPlayerId already has seat $currentSeat
                    reserved so player id $playerId cannot take it");
        }

        $currentSeat = $playerDtos[$key]->currentSeatNumber;
        $reservedSeat = $playerDtos[$key]->reservedSeatNumber;

        if ($currentSeat != null && $currentSeat != $seatNumber) {
            throw new Exception("The player $playerId already has seat $currentSeat and cannot
                    take $seatNumber");
        }
        // note that the player may take a seat even if he did not reserve it.
        if ($reservedSeat != null && $reservedSeat != $seatNumber) {
            throw new Exception("The player $playerId already has reserved seat $reservedSeat
                    and cannot take $seatNumber");
        }
        // nothing to do, player already has a seat.
        /* FIXME: not working, why?
          if ($currentSeat == $seatNumber) {
          return $playerDtos[$key];
          } */
        // Validation Complete --------------------------------------------------------------
        $playerDtos[$key]->currentSeatNumber = $seatNumber;
        $playerDtos[$key]->reservedSeatNumber = null;
        try {
            executeSQL("UPDATE Player SET CurrentSeatNumber = $seatNumber, 
                    ReservedSeatNumber = null, LastUpdateDateTime = '$statusDT' WHERE Id =
                    $playerId", __FUNCTION__ .
                    ": Error updating Player id $playerId to seat number $seatNumber");
        } catch (Exception $e) {
            $playerDtos[$i]->reservedSeatNumber = $seatNumber;
            $playerDtos[$i]->currentSeatNumber = null;
            return null;
        }
        return $playerDtos[$key];
    }

    /**
     * Remove a player from a table and vacate his or her seat
     * @param int $playerId
     * @return int Vacated Seat
     */
    function leaveCurrentTable($playerId, $statusDT) {
        // must delete the PlayerState if one exists
        // TODO: additional business rules when leaving a game.
        executeSQL("UPDATE PlayerState SET status = 'Left', LastUpdateDateTime = '$statusDT'
					WHERE PlayerId = $playerId", __FUNCTION__ . ":
                    Error deleting player state from previous table for player id $playerId");

        $result = executeSQL("SELECT * FROM Player WHERE Id = $playerId", __FUNCTION__ . "
                : Error selecting from player");
        $vacatedSeat = null;
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $casinoTableId = $row["CurrentCasinoTableId"];
            // verify player is on table, otherwise stop.
            $this->log->warn(__FUNCTION__ . ": Player id $playerId leaving table
                        $casinoTableId when managing casino table id $this->id");
            $vacatedSeat = $row["CurrentSeatNumber"];
            // update Player and set his seat and reserved seat to null and casino id to null;
            executeSQL("UPDATE Player SET LastUpdateDateTime = '$statusDT',
                CurrentCasinoTableId = null, CurrentSeatNumber = null
                WHERE Id = $playerId", __FUNCTION__ .
                    ": ERROR updating player $playerId who leaves casino table casinoTableId");
            return $vacatedSeat;
        }
        return null;
    }

    /**
     * Gets the next player in the waiting list. If none return null.
     */
    function findNextWaitingPlayer() {
        $result = executeSQL("SELECT Id from Player WHERE CurrentCasinoTableId = $this->id
                AND CurrentSeatNumber is NULL AND ReservedSeatNumber is NULL
				ORDER BY WaitStartDateTime", __FUNCTION__ . ":
                    Error selecting player without a seat for casino table $this->id");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        return $row[0];
    }

    /**
     * To be used for inactive or timed out players or players who show up on another casino
     * table.
     * The player is not necessarily ejected from $this casino table
     * @param type $playerId
     */
    function ejectPlayer($playerId, $statusDT) {
        // FIXME: should this go somewhere else?
        $vacatedSeat = $this->leaveCurrentTable($playerId, $statusDT);
        $waitingPlayerId = $this->findNextWaitingPlayer();
        if ($waitingPlayerId && $vacatedSeat) {
            $this->reserveAndOfferSeat($vacatedSeat, $playerId, $statusDT);
        }
    }

    function getWaitingListSize() {
        $result = executeSQL("SELECT COUNT(1) FROM Player WHERE CurrentCasinoTableId =
                $this->id AND CurrentSeatNumber is null", __FUNCTION__ . ": Error select
                    count of waiting list on casino id $this->id");
        $row = mysql_fetch_array($result);
        return $row[0];
    }

    function communicateUserJoined($dto, $playerDtos, $waitingListSize) {
        $playerStatusDtos = PlayerStatusDto::mapPlayerDtos(array($dto), PlayerStatusType::WAITING);
        $this->log->debug(__FUNCTION__ . ": Waiting list size " . $waitingListSize);
        $playerStatusDtos[0]->waitingListSize = $waitingListSize;
        // dynamically adding this
        $eventType = EventType::USER_JOINED;
        
        for ($i = 0; $i < count($playerDtos); $i++) {
            if ($playerDtos[$i]->playerId != $dto->playerId) {
                $message = new EventMessage($this->currentGameSessionId,
                                $playerDtos[$i]->playerId, $eventType, $this->lastUpdateDateTime,
                                $playerStatusDtos);
                //$message->eventData = $playerStatusDtos;
                queueMessage($playerDtos[$i]->playerId, json_encode($message));
            }
        }
    }

    function communicateUserLeft($dto, $playerDtos, $waitingListSize) {
        $playerStatusDtos = PlayerStatusDto::mapPlayerDtos(array($dto), PlayerStatusType::LEFT);
        $this->log->debug(__FUNCTION__ . ": Waiting list size " . $waitingListSize);
        $playerStatusDtos[0]->waitingListSize = $waitingListSize;
        $eventType = EventType::USER_LEFT;
        
        for ($i = 0; $i < count($playerDtos); $i++) {
            if ($playerDtos[$i]->playerId != $dto->playerId) {
                $message = new EventMessage($this->currentGameSessionId,
                                $playerDtos[$i]->playerId, $eventType, $this->lastUpdateDateTime,
                                $playerStatusDtos);
                //$message->eventData = $playerStatusDtos;
                queueMessage($playerDtos[$i]->playerId, json_encode($message));
            }
        }
    }

    function communicateGameStarted($instanceSetupDto, $playerDtos) {
        $eventType = EventType::GAME_STARTED;
        $instanceId = $instanceSetupDto->gameInstanceId;

        for ($i = 0; $i < count($playerDtos); $i++) {
            $playerId = $playerDtos[$i]->playerId;
            if ($playerId != $instanceSetupDto->userPlayerId) {
                $instanceSetupDto->userPlayerHand = EntityHelper::getUserHand($playerId, $instanceId);

                $message = new EventMessage($this->currentGameSessionId,
                                $playerId, $eventType, $this->lastUpdateDateTime,
                                $instanceSetupDto);
                //$message->eventData = $instanceSetupDto;
                queueMessage($playerId, json_encode($message));
            }
        }
    }

    function communicateSeatTaken($dto, $playerDtos, $waitingListSize) {
        $playerStatusDtos = PlayerStatusDto::mapPlayerDtos(array($dto), PlayerStatusType::WAITING);
        $this->log->debug(__FUNCTION__ . ": Waiting list size " . $waitingListSize);
        $playerStatusDtos[0]->waitingListSize = $waitingListSize;
        $eventType = EventType::SEAT_TAKEN;
        
        for ($i = 0; $i < count($playerDtos); $i++) {
            //if ($playerDtos[$i]->playerId != $dto->playerId) {
                $message = new EventMessage($this->currentGameSessionId,
                                $playerDtos[$i]->playerId, $eventType, $this->lastUpdateDateTime,
                                $playerStatusDtos);
                //$message->eventData = $playerStatusDtos;
                queueMessage($playerDtos[$i]->playerId, json_encode($message));
            //}
        }
    }

}

?>
