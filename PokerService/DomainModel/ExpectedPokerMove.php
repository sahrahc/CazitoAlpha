<?php

class ExpectedPokerMove {

    public $gameInstanceId;
    public $playerId;
    public $expirationDate;
    public $callAmount;
    public $checkAmount;
    public $raiseAmount;
    private $log;

    /*     * ****************************************************************************** */

    /**
     * Define the first move on a newly started game instance.
     * @global type $dateTimeFormat
     * @global type $playExpiration
     * @global type $practiceExpiration
     * @param type $firstPlayerId
     * @param type $tableMin
     */
    public static function InitFirstMove($gameInstance, $tableMin) {
        global $playExpiration;
        global $practiceExpiration;
        $expirationDateTime = new DateTime();
        if ($this->isPractice == 1) {
            $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
        } else {
            $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
        }

        $raiseAmount = $tableMin * 2;
        $pokerMove = new ExpectedPokerMove();
        $pokerMove->gameInstanceId = $this->id;
        $pokerMove->playerId = $gameInstance->firstPlayerId;
        $pokerMove->expirationDate = $expirationDateTime;
        $pokerMove->callAmount = $tableMin;
        $pokerMove->raiseAmount = $raiseAmount;
        $pokerMove->Insert();

        return $pokerMove;
    }

    /**
     * After a player makes a move, this operation is called to retrieve 
     * the constraints on the next player's
      moves. The next move is saved so that it can be expired.
     * Restrictions: updateNextPlayerIdAndTurn must have been called before.
     * @return ExpectedPokerMove
     */
    public static function FindNextExpectedMove($gameInstance, $curTurn) {
        global $playExpiration;
        global $practiceExpiration;
        $pokerMove = new ExpectedPokerMove();

        $gameInstance->_getNextPlayerIdAndTurn($curTurn);
        $expirationDateTime = new DateTime();
        if ($gameInstance->isNextPlayerVirtual == 1) {
            $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
        } else {
            $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
        }

        // 2 - parse out the next move
        // no need to set identifier, auto incrementing id
        $pokerMove->gameInstanceId = $gameInstance->id;
        $pokerMove->playerId = $gameInstance->nextPlayerId;
        $pokerMove->expirationDate = $expirationDateTime;

        // find move sizes - instance has not updated yet.
        // see if check allowed ---------------------------------------
        // Rule: not allowed on first round except for player who placed blind bet.
        $pokerMove->checkAmount = null;
        if ($gameInstance->lastInstancePlayNumber >= $gameInstance->numberPlayers - 1) {
            $pokerMove->checkAmount = 0;
        }
        // call size  -----------------------------------------------
        $pokerMove->callAmount = $gameInstance->lastBetSize;

        // see how much raise is enabled by ---------------------------
        // Rule: first player on first round can only raise by 2*bigblind, but that is taken
        // care of on initFirstMove.
        $pokerMove->raiseAmount = $gameInstance->tableMinimum + $gameInstance->lastBetSize;

        $pokerMove->Insert();
        return $pokerMove;
    }

    /**
     * Retrieves the next move from the queue. Log warning if more than one found.
     * @param type $gInstanceId
     * @return ExpectedPokerMove
     */
    public static function GetExpectedMoveForInstance($gInstanceId) {
        $result = executeSQL("SELECT * FROM NextPokerMove WHERE GameInstanceId = $gInstanceId
                AND IsDeleted = 0 ORDER BY ExpirationDate DESC LIMIT 1", __FUNCTION__ . "
                : Error selecting from NextPokerMove for instance $gInstanceId");
        $row = mysql_fetch_array($result);
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $pokerMove = new ExpectedPokerMove();
        $pokerMove->id = $row["Id"];
        $pokerMove->gameInstanceId = $row["GameInstanceId"];
        $pokerMove->isPractice = $row["IsPractice"];
        $pokerMove->playerId = $row["PlayerId"];
        $pokerMove->turnNumber = $row["TurnNumber"];
        $pokerMove->expirationDate = $row["ExpirationDate"];
        $pokerMove->isEndGameNext = $row["IsEndGameNext"];
        $pokerMove->callAmount = $row["CallAmount"];
        $pokerMove->checkAmount = $row["CheckAmount"];
        $pokerMove->raiseAmount = $row["RaiseAmount"];
        return $pokerMove;
    }

    public static function GetExpiredPokerMoves($expirationDateTime) {
        //$currentTimeString = $statusDateTime->format($dateTimeFormat);
        // check if expiration
        $result = executeSQL("SELECT m.*, s.IsVirtual
            FROM NextPokerMove m LEFT JOIN PlayerState s
            ON m.gameInstanceId = s.GameInstanceId AND m.PlayerId = s.PlayerId
            WHERE ExpirationDate <= '$expirationDateTime'
                ORDER BY GameInstanceId, ExpirationDate DESC", __FUNCTION__ . "
                 : ERROR selecting all of NextPokerMove");
        // only the last record for every game instance id is processed
        // this won't be needed when only one move is stored (out of database)
        $i = 0;
        $expectedPokerMoves = null;
        echo mysql_num_rows($result) . " rows found. <br />";
        while ($row = mysql_fetch_array($result)) {
            $expectedPokerMoves[$i] = new ExpectedPokerMove();
            $expectedPokerMoves[$i]->gameInstanceId = $row["GameInstanceId"];
            $expectedPokerMoves[$i]->playerId = $row["PlayerId"];
            $expectedPokerMoves[$i]->expirationDate = $row['ExpirationDate'];
            $expectedPokerMoves[$i]->checkAmount = $row["CheckAmount"];
            $expectedPokerMoves[$i]->callAmount = $row["CallAmount"];
            $expectedPokerMoves[$i]->raiseAmount = $row["RaiseAmount"];
        }
        return $expectedPokerMoves;
    }

    public function Insert() {
        global $dateTimeFormat;
        $checkAmt = 0;
        if (is_null($this->checkAmount)) {
            $checkAmt = "null";
        }
        $expirationString = $this->expirationDate->format($dateTimeFormat);

        executeSQL("INSERT INTO ExpectedPokerMove (GameInstanceId, PlayerId, 
                ExpirationDate, CallAmount, CheckAmount, RaiseAmount)
                VALUES ($this->gameInstanceId, 
                $this->playerId, '$expirationString',
                $this->callAmount, $checkAmt,
                $this->raiseAmount)", __FUNCTION__ . "
                :ERROR - Error inserting next move for instance
                $this->gameInstanceId");
    }

    public function Delete() {
        executeSQL("DELETE FROM NextPokerMove WHERE playerId = $this->playerId
            AND GameInstanceId = $this->gameInstanceId", __FUNCTION__ . "
                :ERROR - Error deleting move for player $this->playerId and
                and game instance $this->gameInstanceId");
    }

}

?>
