<?php

/* * ************************************************************************************* */

class PlayerInstance {

    public $playerId;
    public $gameInstanceId;
    public $isVirtual;
    public $gameSessionId;
    public $lastUpdateDateTime;
    public $seatNumber;
    public $turnNumber;
    public $status;
    public $currentStake;
    public $lastPlayAmount;
    public $lastPlayInstanceNumber;
    public $numberTimeOuts;
    //public $log;
    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /*
    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
    }
*/
    /**
     * Get all the players instance setup and status for a game instance
     * @param int gInstId
     * @return PlayerInstance[]
     */
    public static function GetPlayerInstancesForGame($id, $isSessionId = false) {
        global $dateTimeFormat;
        // sorted by seat number
        if ($isSessionId) {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameSessionId = $id ORDER BY TurnNumber", __FUNCTION__ . 
                    ": ERROR loading PlayerStates with instance id $id");
        } else {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameInstanceId = $id ORDER BY TurnNumber", __FUNCTION__ . 
                    ": ERROR loading PlayerStates with instance id $id");
        }
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $playerInstances = null;
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerInstances[$i] = new PlayerInstance();
            $playerInstances[$i]->playerId = (int)$row["PlayerId"];
            $playerInstances[$i]->gameInstanceId = (int)$row["GameInstanceId"];
            $playerInstances[$i]->isVirtual = (int)$row["IsVirtual"];
            $playerInstances[$i]->gameSessionId = (int)$row["GameSessionId"];
            $playerInstances[$i]->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
            $playerInstances[$i]->seatNumber = (int)$row["SeatNumber"];
            $playerInstances[$i]->turnNumber = $row["TurnNumber"] == null ? null : (int)$row["TurnNumber"];
            $playerInstances[$i]->status = $row["Status"];
            $playerInstances[$i]->currentStake = $row["CurrentStake"] == null ? null : (int)$row["CurrentStake"];
            $playerInstances[$i]->lastPlayAmount = $row["LastPlayAmount"] == null ? null : (int)$row["LastPlayAmount"];
            $playerInstances[$i]->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"] == null ? null : (int)$row["LastPlayInstanceNumber"];
            $playerInstances[$i]->numberTimeOuts = $row["NumberTimeOuts"] == null ? null : (int)$row["NumberTimeOuts"];

            $i++;
        }
        return $playerInstances;
    }

    /**
     * Create new player state records for players who got a seat
     * @param type $gameInstance
     * @return null|\PlayerInstance
     */
    public static function GetNewPlayerStatesOnSession($gameInstance) {
        $result = executeSQL("SELECT p.Id PlayerId, 
                p.IsVirtual, p.CurrentSeatNumber, p.BuyIn
            FROM Player p 
            INNER JOIN CasinoTable c ON p.CurrentCasinoTableId = c.Id
             LEFT JOIN PlayerState s ON p.Id = s.PlayerId
            WHERE c.CurrentGameSessionId = $gameInstance->gameSessionId 
              AND p.CurrentSeatNumber IS NOT NULL AND s.PlayerId IS NULL
                ORDER BY p.CurrentSeatNumber", __FUNCTION__ . "
                : Error inserting PlayerState for new players in session $gameInstance->gameSessionId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerStates[$i] = new PlayerInstance();
            $playerStates[$i]->playerId = (int)$row["PlayerId"];
            $playerStates[$i]->gameInstanceId = $gameInstance->id;
            $playerStates[$i]->isVirtual = (int)$row["IsVirtual"];
            $playerStates[$i]->gameSessionId = (int)$gameInstance->gameSessionId;
            $playerStates[$i]->lastUpdateDateTime = Context::GetStatusDT();
            $playerStates[$i]->seatNumber = is_null($row["CurrentSeatNumber"]) ? null : (int)$row["CurrentSeatNumber"];
            // don't set turnNumber,
            $playerStates[$i]->status = PlayerStatusType::WAITING;
            // TODO: is buy in the stake?
            $playerStates[$i]->currentStake = is_null($row["BuyIn"]) ? null : (int)$row["BuyIn"];
            $playerStates[$i]->lastPlayAmount = 0;
            $playerStates[$i]->lastPlayInstanceNumber = 0;
            $playerStates[$i]->numberTimeOuts = 0;
            $i++;
        }
        return $playerStates;
    }

    public function Insert() {
        global $dateTimeFormat;
        $updateDTString = $this->lastUpdateDateTime->format($dateTimeFormat);
        // properties that can be null
        $gameInstanceId = is_null($this->gameInstanceId) ? 'null' : $this->gameInstanceId;
        $turnNumber = is_null($this->turnNumber) ? 'null' : $this->turnNumber;
        $lastPlayAmount = is_null($this->lastPlayAmount) ? 'null' : $this->lastPlayAmount;
        $lastPlayNumber = is_null($this->lastPlayInstanceNumber) ? 'null' : $this->lastPlayInstanceNumber;
        $numberTimeOuts = is_null($this->numberTimeOuts) ? 'null' : $this->numberTimeOuts;
        executeSQL("INSERT INTO PlayerState (PlayerId, GameInstanceId,
                IsVirtual, GameSessionId, LastUpdateDateTime,
                SeatNumber, TurnNumber, Status, CurrentStake,
                LastPlayAmount, LastPlayInstanceNumber, NumberTimeOuts)
                VALUES ($this->playerId, 
                $gameInstanceId, 
                $this->isVirtual,
                $this->gameSessionId,
                '$updateDTString',
                $this->seatNumber,
                $turnNumber,
                '$this->status',
                $this->currentStake,
                $lastPlayAmount,
                $lastPlayNumber,
                $numberTimeOuts
                )", __FUNCTION__ . "
                : Error inserting Player State for game instance id $this->gameInstanceId");
    }

    public function Delete() {
        $result = executeSQL("DELETE FROM PlayerState 
            WHERE PlayerId = $this->playerId
                AND GameInstanceId = $this->gameInstanceId", __CLASS__ . '-' . 
                __FUNCTION__ . ": Error deleting PlayerState for player $this->playerId
                And gameInstance $this->gameInstanceId");
        if (mysql_num_rows($result == 0)) {
            self::log()->warn("Could not find player $this->playerId and instance $this->gameInstanceId to delete");
        }
    }
    /**
     * Updating after move changes the status, stake, last play amount
     * and play number only
     */
    public function UpdateAfterMove() {
        global $dateTimeFormat;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        $lastPlayAmount = $this->lastPlayAmount;
        if (is_null($lastPlayAmount) || $lastPlayAmount == "") {
            $lastPlayAmount = "null";
        }
        executeSQL("UPDATE PlayerState SET LastUpdateDateTime = '$statusDT',
                Status = '$this->status',
                CurrentStake = $this->currentStake, 
                LastPlayAmount = $lastPlayAmount,
                LastPlayInstanceNumber = $this->lastPlayInstanceNumber
                WHERE PlayerId = $this->playerId and GameInstanceId = $this->gameInstanceId
                ", __FUNCTION__ . ": Error updating player state player id $this->playerId");
    }

    public function UpdatePlayerTurnAndReset() {
        $initialState = PlayerStatusType::WAITING;
        $this->status = $initialState;
        // stake should never be null..
        //$stake = is_null($this->currentStake) ? 0 : $this->currentStake;
        $stake = $this->currentStake;
        // no blind bets yet 
        $lastPlayAmount = 0;
        $this->lastPlayInstanceNumber = 0;
        $this->numberTimeOuts = 0;
        executeSQL("UPDATE PlayerState set GameInstanceId = $this->gameInstanceId,
            TurnNumber = $this->turnNumber,
            Status = '$initialState',
            CurrentStake = $stake, 
            LastPlayAmount = $lastPlayAmount,
            LastPlayInstanceNumber = $this->lastPlayInstanceNumber, 
            NumberTimeOuts = $this->numberTimeOuts
            WHERE PlayerId = $this->playerId
                and GameSessionId = $this->gameSessionId", __FUNCTION__ . "
                    : Error updating turn number for PlayerState player id
                    $this->playerId and session $this->gameSessionId");
    }

    public static function UpdateBlindByTurn($gameInstanceId, $amount, $turnNumber) {
        global $dateTimeFormat;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        executeSQL("UPDATE PlayerState SET CurrentStake = CurrentStake - $amount,
                Status = '" . PlayerStatusType::BLIND_BET . "',
                LastPlayAmount = $amount,
                LastUpdateDateTime = '$statusDT'
                WHERE GameInstanceId = $gameInstanceId AND TurnNumber = $turnNumber",
                __FUNCTION__ . ": Error updating PlayerState first blind bet instance
                $gameInstanceId ");
    }

    /**
     * Updates all calculated player hands portion of the PlayerStates, part of finding the winner.
     * @param type $playerHands
     * @param type $statusDT 
     */
    public static function UpdatePlayerStateHands($playerHands) {
        global $dateTimeFormat;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        for ($i = 0; $i < count($playerHands); $i++) {
            $playerId = $playerHands[$i]->playerId;
            $handType = $playerHands[$i]->pokerHandType;
            $status = PlayerStatusType::LOST;
            if ($playerHands[$i]->isWinningHand) {
                $status = PlayerStatusType::WON;
            }
            // 2+2 evaluator only
            $handInfo = $playerHands[$i]->handInfo;
            $handCategory = $playerHands[$i]->handCategory;
            $handRank = $playerHands[$i]->rankWithinCategory;
            executeSQL("UPDATE PlayerState SET HandType = '$handType', HandInfo = $handInfo,
                    HandCategory = $handCategory, HandRankWithinCategory = $handRank,
                        Status = '$status',
                    LastUpdateDateTime = '$statusDT' WHERE PlayerId = $playerId", __FUNCTION__ . "
                        :ERROR updating PlayerState player id $playerId");
        }
    }

    public function UpdatePlayerStatus($status) {
        global $dateTimeFormat;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        executeSQL("UPDATE PlayerState SET status = '$status', 
            LastUpdateDateTime = '$statusDT'
            WHERE PlayerId = $this->playerId", __FUNCTION__ . ":
            Error updating player state status for player id $this->playerId");
        
    }
    /**
     * updates player state and player also
     * @param type $playerId
     */
    public function UpdatePlayerLeftStatus() {
        global $dateTimeFormat;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        // update Player and set his seat and reserved seat to null and casino id to null;
        executeSQL("UPDATE Player SET LastUpdateDateTime = '$statusDT',
                CurrentCasinoTableId = null, CurrentSeatNumber = null,
                BuyIn = null, WaitStartDateTime = null,
                ReservedSeatNumber = null
                WHERE Id = $this->playerId", __FUNCTION__ .
                ": ERROR updating player $this->playerId who leaves casino table casinoTableId");
        $leftStatus = PlayerStatusType::LEFT;
        executeSQL("UPDATE PlayerState SET status = '$leftStatus', 
            LastUpdateDateTime = '$statusDT'
            WHERE PlayerId = $this->playerId", __FUNCTION__ . ":
            Error updating player state to left for player id $this->playerId");
    }
    
    /**
     * Get all the moves after time
     */
    public static function DeleteExpiredPlayerInstances($expirationDateTime) {
        global $dateTimeFormat;
        $endString = $expirationDateTime->format($dateTimeFormat);
        
        $result = executeSQL("SELECT * FROM PlayerState
            WHERE LastUpdateDateTime <= '$endString'
                ORDER BY LastUpdateDateTime ", __FUNCTION__ . "
                 : ERROR selecting expired player instances");
        // only the last record for every game instance id is processed
        // this won't be needed when only one move is stored (out of database)
        $i = 0;
        $playerStates = null;
        echo mysql_num_rows($result) . " rows found. <br />";
        while ($row = mysql_fetch_array($result)) {
            $playerStates[$i] = new PlayerInstance();
            $playerStates[$i]->playerId = $row["PlayerId"];
            $playerStates[$i]->gameInstanceId = $row["GameInstanceId"];
            /*
            $playerStates[$i]->isVirtual = $row["IsVirtual"];
            $playerStates[$i]->gameSessionId = $row["GameSessionId"];
            $playerStates[$i]->lastUpdateDateTime = Context::GetStatusDT();
            $playerStates[$i]->seatNumber = $row["CurrentSeatNumber"];
            $playerStates[$i]->turnNumber = $row["TurnNumber"];
            $playerStates[$i]->status = $row["Status"];
            $playerStates[$i]->currentStake = $row["CurrentStake"];
            $playerStates[$i]->lastPlayAmount = $row["LastPlayAmount"];
            $playerStates[$i]->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"];
            $playerStates[$i]->numberTimeOuts = $row["NumberTimeOuts"];
             */
            // deleting does not remove object
            $playerStates[$i]->Delete();
        }
        executeSQL("DELETE FROM PlayerState
            WHERE LastUpdateDateTime <= '$endString'", __FUNCTION__ . "
                 : ERROR deleting expired player instances");
        
        return $playerStates;
    }

    /**
     * Delete all PlayerState records for players who left
     * @param type $gameSessionId
     */
    public static function DeleteDepartedPlayerInstances($gameSessionId) {
        $leftStatus = PlayerStatusType::LEFT;
        executeSQL("DELETE PlayerState
                FROM PlayerState
                WHERE GameSessionId = $gameSessionId AND Status = '$leftStatus'", 
                __FUNCTION__ . ": Error deleting PlayerStates who left
                game session $gameSessionId");
        if (mysql_affected_rows() > 0 ){
            self::log()->warn(__FUNCTION__ . " - Deleted " . mysql_affected_rows() . "
                player states for players who left casino table.");
        }
    }

}

?>
