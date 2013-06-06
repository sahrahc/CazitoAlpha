<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

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
    public $log;

    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
    }

    /**
     * Get all the players instance setup and status for a game instance
     * @param int gInstId
     * @return PlayerInstance[]
     */
    public static function GetPlayerInstancesForGame($id, $isSessionId) {
        // sorted by seat number
        if ($isSessionId) {
            $result = executeSQL("SELECT *
                FROM PlayerState
                ORDER BY TurnNumber
                WHERE GameSessionId = $id ORDER BY TurnNumber", __FUNCTION__ . ": ERROR loading PlayerStates with instance id $id");
        } else {
            $result = executeSQL("SELECT *
                FROM PlayerState
                ORDER BY TurnNumber
                WHERE GameInstanceId = $id ORDER BY TurnNumber", __FUNCTION__ . ": ERROR loading PlayerStates with instance id $id");
        }
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $playerInstances = null;
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerInstances[$i] = new PlayerInstance();
            $playerInstances[$i]->playerId = $row["PlayerId"];
            $playerInstances[$i]->gameInstanceId = $row["GameInstanceId"];
            $playerInstances[$i]->isVirtual = $row["IsVirtual"];
            $playerInstances[$i]->gameSessionId = $row["GameSessionId"];
            $playerInstances[$i]->lastUpdateDateTime = $row["LastUpdateDateTime"];
            $playerInstances[$i]->seatNumber = $row["SeatNumber"];
            $playerInstances[$i]->turnNumber = $row["TurnNumber"];
            $playerInstances[$i]->status = $row["Status"];
            $playerInstances[$i]->currentStake = $row["Stake"];
            $playerInstances[$i]->lastPlayAmount = $row["LastPlayAmount"];
            $playerInstances[$i]->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"];
            $playerInstances[$i]->numberTimeOuts = $row["NumberTimeOuts"];

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
              AND p.currentSeatNumber IS NOT NULL AND s.PlayerId IS NULL", __FUNCTION__ . "
                : Error inserting PlayerState for new players in table $this->casinoTableId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerStates[$i] = new PlayerInstance();
            $playerStates[$i]->playerId = $row["PlayerId"];
            $playerStates[$i]->gameInstanceId = $gameInstance->id;
            $playerStates[$i]->isVirtual = $row["IsVirtual"];
            $playerStates[$i]->gameSessionId = $gameInstance->gameSessionId;
            $playerStates[$i]->lastUpdateDateTime = Context::GetStatusDT();
            $playerStates[$i]->seatNumber = $row["CurrentSeatNumber"];
            // don't set turnNumber,
            $playerStates[$i]->status = PlayerStatusType::Waiting;
            // TODO: is buy in the stake?
            $playerStates[$i]->currentStake = 0;
            $playerStates[$i]->lastPlayAmount = 0;
            $playerStates[$i]->lastPlayInstanceNumber = 0;
            $playerStates[$i]->numberTimeOuts = 0;
        }
        return $playerStates;
    }

    public function Insert() {
        // properties that can be null
        $gameInstanceId = is_null($this->gameInstanceId) ? 'null' : $this->gameInstanceId;
        $turnNumber = is_null($this->turnNumber) ? 'null' : $this->turnNumber;
        $lastPlayAmount = is_null($this->lastPlayAmount) ? 0 : $this->lastPlayAmount;
        $lastPlayNumber = is_null($this->lastPlayInstanceNumber) ? 0 : $this->lastPlayInstanceNumber;
        $numberTimeOuts = is_null($this->numberTimeOuts) ? 0 : $this->numberTimeOuts;
        executeSQL("INSERT INTO PlayerState (PlayerId, GameInstanceId,
                IsVirtual, GameSessionId, LastUpdateDateTime
                SeatNumber, TurnNumber, Status, CurrentStake
                LastPlayAmount, LastPlayInstanceNumber, NumberTimeOuts)
                VALUES ($this->playerId, 
                $gameInstanceId, 
                $this->isVirtual,
                $this->gameSessionId,
                '$this->lastUpdateDateTime',
                $this->seatNumber,
                $turnNumber,
                $this->status,
                $this->currentStake,
                $lastPlayAmount,
                $lastPlayNumber,
                $numberTimeOuts
                )", __FUNCTION__ . "
                : Error inserting first practice Player for game session id $this->id");
    }

    /**
     * Updating after move changes the status, stake, last play amount
     * and play number only
     */
    public function UpdateAfterMove() {
        $statusDT = Context::GetStatusDT();
        $lastPlayAmount = $this->lastPlayAmount;
        if (is_null($lastPlayAmount) || $lastPlayAmount == "") {
            $lastPlayAmount = "null";
        }
        executeSQL("UPDATE PlayerState SET LastUpdateDateTime = '$statusDT',
                Status = '$this->status',
                Stake = $this->currentStake, 
                LastPlayAmount = $lastPlayAmount,
                LastPlayInstanceNumber = $this->lastPlayInstanceNumber,
                WHERE PlayerId = $this->playerId and GameInstanceId = $this->gameInstanceId
                ", __FUNCTION__ . ": Error updating player state player id $this->playerId");
    }

    public function UpdatePlayerTurnAndReset() {
        $initialState = PlayerStatusType::WAITING;
        $this->status = $initialState;
        $this->currentStake = 0;
        $this->lastPlayAmount = 0;
        $this->lastPlayInstanceNumber = 0;
        $this->numberTimeOuts = 0;
        executeSQL("UPDATE PlayerState set GameInstanceId = $this->gameInstanceId,
            TurnNumber = $this->turnNumber,
            Status = '$initialState',
            CurrentStake = $this->currentStake, 
            LastPlayAmount = $this->lastPlayAmount,
            LastPlayInstanceNumber = $this->lastPlayInstanceNumber, 
            NumberTimeOuts = $this->numberTimeOuts,
            WHERE PlayerId = $this->playerId
                and GameSessionId = $this->gameSessionId", __FUNCTION__ . "
                    : Error updating turn number for PlayerState player id
                    $this->playerId and session $this->gameSessionId");
    }

    public static function UpdateBlindByTurn($gameInstanceId, $amount, $turnNumber) {
        $statusDT = Context::GetStatusDT();
        executeSQL("UPDATE PlayerState SET BlindBet = $amount,
                Stake = Stake - $amount,
                status = '" . PlayerStatusType::BLIND_BET . "',
                LastPlayAmount = $amount,
                LastUpdateDateTime = '$statusDT'
                WHERE GameInstanceId = $gameInstanceId AND TurnNumber = $turnNumber", __FUNCTION__ . ": Error updating PlayerState first blind bet instance
                $gameInstanceId ");
    }

    /**
     * Updates all calculated player hands portion of the PlayerStates, part of finding the winner.
     * @param type $playerHands
     * @param type $statusDT 
     */
    public static function UpdatePlayerStateHands($playerHands) {
        $statusDT = Context::GetStatusDT();
        for ($i = 0; $i < count($playerHands); $i++) {
            $handType = $playerHands[$i]->pokerHandType;
            $handInfo = $playerHands[$i]->handInfo;
            $handCategory = $playerHands[$i]->handCategory;
            $handRank = $playerHands[$i]->rankWithinCategory;
            $playerId = $playerHands[$i]->playerId;
            executeSQL("UPDATE PlayerState SET HandType = '$handType', HandInfo = $handInfo,
                    HandCategory = $handCategory, HandRankWithinCategory = $handRank,
                    LastUpdateDateTime = '$statusDT' WHERE PlayerId = $playerId", __FUNCTION__ . "
                        :ERROR updating PlayerState player id $playerId");
        }
    }

    /**
     * updates player state and player also
     * @param type $playerId
     */
    public function UpdatePlayerLeftStatus() {
        $statusDT = Context::GetStatusDT();
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
            Error deleting player state from previous table for player id $this->playerId");
    }

    
    /**
     * Get all the moves after time
     */
    public static function DeleteExpiredPlayerInstances($expirationDateTime) {
        $result = executeSQL("SELECT * FROM PlayerState
            WHERE LastUpdateDateTime <= '$expirationDateTime'
                ORDER BY LastUpdateDateTime ", __FUNCTION__ . "
                 : ERROR selecting expired player instances");
        // only the last record for every game instance id is processed
        // this won't be needed when only one move is stored (out of database)
        $i = 0;
        $playerStates = null;
        echo mysql_num_rows($result) . " rows found. <br />";
        while ($row = mysql_fetch_array($result)) {
            $expectedPokerMoves[$i] = new PlayerInstance();
            $playerStates[$i] = new PlayerInstance();
            $playerStates[$i]->playerId = $row["PlayerId"];
            $playerStates[$i]->gameInstanceId = $row["GameInstanceId"];
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
            // deleting does not remove object
            $playerStates[$i]->Delete();
        }
        executeSQL("DELETE FROM PlayerState
            WHERE LastUpdateDateTime <= '$expirationDateTime'", __FUNCTION__ . "
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
                WHERE GameSessionId = $gameSessionId", __FUNCTION__ . "
                    AND Status = '$leftStatus'
                : Error deleting PlayerStates who left
                game session $gameSessionId");
        $this->log->warn(__FUNCTION__ . " - Deleted " . mysql_affected_rows() . "
                player states for players who left casino table.");
    }

}

?>
