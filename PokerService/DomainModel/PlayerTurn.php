<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

include_once(dirname(__FILE__) . '/../Dto/PlayerStatusDto.php');

/* * ************************************************************************************* */

/**
 * The status of a player action while being processed. Player action includes practice players and forced plays when a player's time is up to make a play.
 */
class PlayerTurn {

    public $action;
    public $gameInstanceStatus;
    public $playerInstanceStatus;
    // associations
    public $statusDateTime;
    public $playerActionResultDto;
    private $log;

    public function __construct($pAction, $gInstStatus, $statusDT) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->action = $pAction;
        $this->gameInstanceStatus = $gInstStatus;

        // initialize playerstatus
        $this->playerInstanceStatus = EntityHelper::getPlayerInstance($this->gameInstanceStatus->id, $this->action->playerId);

        $this->statusDateTime = $statusDT;
    }

    /*     * ****************************************************************************** */
    /* private methods */

    /**
     * Validates the move is valid. Returns the move id (so it can be deleted if successfully processed),otherwise throws exception.
     * @return int Move identifier
     */
    private function validateMove() {
        global $log;
        $nextMove = EntityHelper::getNextMoveForInstance($this->action->gameInstanceId);
        $exceptionMsg = null;

        // variables to keep objects easier to access
        $action = $this->action;
        $gameInstanceId = $this->action->gameInstanceId;

        if (is_null($nextMove)) {
            $msg = __FUNCTION__ . ": No moves expected for game instance $gameInstanceId but 
                   player id $action->playerId $action->pokerActionType";
            $log->warn($msg);
            $exceptionMsg = $msg;
            //throw new Exception($exceptionMsg);
        }
        if ($action->playerId != $nextMove->playerId) {
            $msg = __FUNCTION__ . ": Wrong player attempting move for instance
                    $action->gameInstanceId actual player id $action->playerId but expected
                    player id $nextMove->playerId";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($action->pokerActionType == PokerActionType::CALLED AND
                $action->actionValue != $nextMove->callAmount) {

            $msg = __FUNCTION__ . ": Call amount is wrong for instance $action->gameInstanceId
                    by $action->playerId actual amount $action->actionValue expected
                    $nextMove->callAmount";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($action->pokerActionType == PokerActionType::CHECKED AND
                is_null($nextMove->checkAmount)) {

            $msg = __FUNCTION__ . ": Check is not allowed for instance $action->gameInstanceId
                    but attempted by $action->playerId";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($action->pokerActionType == PokerActionType::RAISED AND
                $action->actionValue != $nextMove->raiseAmount) {

            $msg = __FUNCTION__ . ": Raise amount is wrong for instance $action->gameInstanceId
                    by $action->playerId actual amount $action->actionValue but expected
                    $nextMove->raiseAmount";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if (!is_null($exceptionMsg)) {
            //throw new Exception($exceptionMsg);
        }
        return $nextMove->id;
    }

    /**
     * Get the player id and turn that should make the next play after current. This method updates the game instance status.
     */
    private function updateNextPlayerIdAndTurn($curTurn) {
        if (is_null($curTurn)) {
            $curTurn = -1;
        }
        $instanceId = $this->gameInstanceStatus->id;
        $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual FROM PlayerState
                WHERE GameInstanceId = $instanceId AND TurnNumber > $curTurn
                ORDER BY TurnNumber LIMIT 1", __FUNCTION__ . "
                : Error selecting from PlayerState instance id $instanceId and
                turn greater than $curTurn");
        if (mysql_num_rows($result) == 0) {
            $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual
                    FROM PlayerState WHERE GameInstanceId = $instanceId AND TurnNumber >= 0
                    ORDER BY TurnNumber LIMIT 1", __FUNCTION__ . "
                    : Error selecting from PlayerState instance id $instanceId and turn >=0");
        }
        if (mysql_num_rows($result) == 0) {
            throw new Exception(__FUNCTION__ . ": ERROR - Next PlayerState not found for 
                    instance id $instanceId AND current seat $curTurn");
        }
        $row = mysql_fetch_array($result);
        // recursively find the next player
        $nextTurn = $row['TurnNumber'];
        if ($row['Status'] != PlayerStatusType::FOLDED &&
                $row['Status'] != PlayerStatusType::LEFT) {
            $nextPlayerId = $row['PlayerId'];
            $isVirtual = $row['IsVirtual'];
            $this->gameInstanceStatus->nextPlayerId = $nextPlayerId;
            $this->gameInstanceStatus->nextTurnNumber = $nextTurn;
            $this->gameInstanceStatus->isNextPlayerVirtual = $isVirtual;
        } else {
            // increment play counters
            $this->playerInstanceStatus->playerPlayNumber +=1;
            $this->gameInstanceStatus->lastInstancePlayNumber +=1;
            $this->updateNextPlayerIdAndTurn($nextTurn);
        }
    }

    private function checkGameEnd() {
        // convenience variables
        $gameInstanceId = $this->gameInstanceStatus->id;
        $instancePlayNumber = $this->gameInstanceStatus->lastInstancePlayNumber;
        $numberPlayers = $this->gameInstanceStatus->gameInstanceSetup->numberPlayers;
        // see if current player's move triggered and end date before calculating the next.
        // 1 - if only on user remaining (status - not folded) then end game
        $folded = PlayerStatusType::FOLDED;
        $left = PlayerStatusType::LEFT;
        $result = executeSQL("SELECT count(1) FROM PlayerState WHERE GameInstanceId =
            $gameInstanceId AND Status != '$folded' AND Status != '$left'", __FUNCTION__ . "
            : Error selecting PlayerState instance $gameInstanceId");
        $row = mysql_fetch_array($result);
        if ($row[0] <= 1) {
            return true;
        }
        // if next player and previous player play is fourth, end game.
        // instance has been updated yet by the calling function
        if ($instancePlayNumber >= $numberPlayers * 4) {
            return true;
        }
        return false;
    }

    /**
     * After a player makes a move, this operation is called to retrieve the constraints on the next player's
      moves. The next move is saved so that it can be expired.
     * Restrictions: updateNextPlayerIdAndTurn must have been called before.
     * @return NextPokerMove
     */
    private function findNextMove() {
        global $log;
        global $dateTimeFormat;
        global $playExpiration;
        global $practiceExpiration;
        $nextPokerMove = new NextPokerMove();

        // convenience variables
        $gameInstanceId = $this->gameInstanceStatus->id;
        $instancePlayNumber = $this->gameInstanceStatus->lastInstancePlayNumber;
        $nextTurnNumber = $this->gameInstanceStatus->nextTurnNumber;
        $isPractice = $this->gameInstanceStatus->gameInstanceSetup->isPractice;
        $numberPlayers = $this->gameInstanceStatus->gameInstanceSetup->numberPlayers;

        $expirationDateTime = new DateTime();
        if ($this->gameInstanceStatus->isNextPlayerVirtual == 1) {
            $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
        } else {
            $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
        }
        $expirationString = $expirationDateTime->format($dateTimeFormat);

        // 2 - parse out the next move
        // no need to set identifier, auto incrementing id
        $nextPokerMove->gameInstanceId = $gameInstanceId;
        $nextPokerMove->isPractice = $isPractice;
        $nextPokerMove->playerId = $this->gameInstanceStatus->nextPlayerId;
        $nextPokerMove->turnNumber = $nextTurnNumber;
        $nextPokerMove->expirationDate = $expirationDateTime;
        $nextPokerMove->isEndGameNext = 0;

        // additional variables to make it easier to get the data
        $playerPlayNumber = $this->playerInstanceStatus->playerPlayNumber;
        $lastBetSize = $this->gameInstanceStatus->lastBetSize;

        // find move sizes - instance has not updated yet.
        // see if check allowed ---------------------------------------
        // Rule: not allowed on first round except for player who placed blind bet.
        $nextPokerMove->checkAmount = null;
        if ($instancePlayNumber >= $numberPlayers - 1) {
            $nextPokerMove->checkAmount = 0;
        }
        // call size  -----------------------------------------------
        $nextPokerMove->callAmount = $lastBetSize;

        // see how much raise is enabled by ---------------------------
        // Rule: first player on first round can only raise by 2*bigblind, but that is taken
        // care of on savefirstexpectedmove.
        $nextPokerMove->raiseAmount = $this->gameInstanceStatus->gameInstanceSetup->tableMinimum + $lastBetSize;

        $checkAmt = 0;
        if (is_null($nextPokerMove->checkAmount)) {
            $checkAmt = "null";
        }
        executeSQL("INSERT INTO NextPokerMove (GameInstanceId, IsPractice, PlayerId, 
                TurnNumber, ExpirationDate, IsEndGameNext, CallAmount, CheckAmount,
                RaiseAmount, IsDeleted) VALUES ($nextPokerMove->gameInstanceId, $isPractice,
                $nextPokerMove->playerId, $nextPokerMove->turnNumber, '$expirationString',
                $nextPokerMove->isEndGameNext, $nextPokerMove->callAmount, $checkAmt,
                $nextPokerMove->raiseAmount, 0)", __FUNCTION__ . "
                :ERROR - Error inserting next move for instance
                $nextPokerMove->gameInstanceId");

        return $nextPokerMove;
    }

    /*     * ****************************************************************************** */
    /* public methods */

    /**
     * Processes a player's action including practice virtual player, which includes the following steps:
     * 1) Validate the user's intended move
     * 2) Update the player state: status, stake, playerPlayNumber, lastPlayAmount
     * 3) Update game instance: nextPlayerId and nextTurnNumber, potSize, lastBetSize, lastInstancePlayNumber
     * 4) Find next move, increase player # and community cards shown
     * @return PlayerActionResultDto
     */
    function applyPlayerAction() {
        $currentMoveId = $this->validateMove();

        // fields that automatically update
        // update player status, player number and last play amount
        $this->playerInstanceStatus->status = $this->action->pokerActionType;
        $this->playerInstanceStatus->playerPlayNumber += 1;
        if (!is_null($this->action->actionValue)) {
            $this->playerInstanceStatus->lastPlayAmount = $this->action->actionValue;
        }
        // update instance last play number;
        $this->gameInstanceStatus->lastInstancePlayNumber +=1;

        // fields that need to be calculated
        // update the player stake and game instance potsize and lastbetsize
        if ($this->action->pokerActionType == PokerActionType::CALLED ||
                $this->action->pokerActionType == PokerActionType::BLIND_BET ||
                $this->action->pokerActionType == PokerActionType::RAISED) {
            $this->playerInstanceStatus->stake -= $this->action->actionValue;
            $this->gameInstanceStatus->potSize += $this->action->actionValue;
            $this->gameInstanceStatus->lastBetSize = $this->action->actionValue;
        }
        if ($this->action->pokerActionType == PokerActionType::CHECKED) {
            $this->playerInstanceStatus->lastPlayAmount = null;
        }
        // update player status
        $this->playerInstanceStatus->saveStatus($this->statusDateTime);

        // remove from current move from queue
        executeSQL("UPDATE NextPokerMove SET IsDeleted = 1 WHERE ID = $currentMoveId
                ", __FUNCTION__ . ": Error setting deleted flag on NextPokerMove id
                $currentMoveId");

        // current play may have triggered game end
        if ($this->checkGameEnd()) {
            $nextMove = new NextPokerMove();
            $nextMove->gameInstanceId = $this->gameInstanceStatus->id;
            $nextMove->isEndGameNext = 1;
            return $nextMove;
        }
        // update the next player id and turn number
        $curTurn = $this->playerInstanceStatus->playerInstanceSetup->turnNumber;
        $this->updateNextPlayerIdAndTurn($curTurn);

        return $this->findNextMove();
    }

    /**
     * Called by timer if player did not make a move
     * Same as processActionFindNext but there was no action. Skipping a turn increments playnumber in instance and player state
     * 1) No need to validate move
     * 2) Increment timeout and set status to skipped for the player
     * 3) Find next player
     */
    function skipTurn($moveId) {

        // increment time out
        $this->playerInstanceStatus->numberTimeOuts += 1;
        // fields that automatically update
        // update player status and player number - last play amount doesn't change
        if ($this->playerInstanceStatus->status != PlayerStatusType::LEFT) {
            $this->playerInstanceStatus->status = PlayerStatusType::SKIPPED;
        }
        $this->playerInstanceStatus->playerPlayNumber +=1;

        // update instance last play number;
        $this->gameInstanceStatus->lastInstancePlayNumber +=1;

        // stake, game instance pot size and last bet size does not change.
        // update player status
        $this->playerInstanceStatus->saveStatus($this->statusDateTime);

        // remove from current move from queue
        executeSQL("UPDATE NextPokerMove SET IsDeleted = 1 WHERE ID = $moveId
                ", __FUNCTION__ . ": Error setting deleted flag on NextPokerMove id
                $moveId");

        // skipped user may have been last
        if ($this->checkGameEnd()) {
            $nextMove = new NextPokerMove();
            $nextMove->gameInstanceId = $this->gameInstanceStatus->id;
            $nextMove->isEndGameNext = 1;
            return $nextMove;
        }
        // update the next player id and turn number
        $curTurn = $this->playerInstanceStatus->playerInstanceSetup->turnNumber;
        $this->updateNextPlayerIdAndTurn($curTurn);

        // get the next move (sets the game result if game end also)
        return $this->findNextMove();
    }

}

?>
