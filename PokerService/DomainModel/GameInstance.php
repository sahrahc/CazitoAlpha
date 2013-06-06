<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');
require_once(dirname(__FILE__) . '/../Components/QueueManager.php');
/* * ************************************************************************************* */

class HighestHand {

    public $handCategory;
    public $rankWithinCategory;
    public $winningPlayerId;

}

class GameInstance {

    public $id;
    // setup
    public $gameSessionId;
    public $status;
    public $startDateTime;
    public $lastUpdateDateTime;
    public $numberPlayers;
    public $dealerPlayerId;
    public $firstPlayerId;
    public $nextPlayerId;
    public $currentPotSize;
    public $lastBetSize;
    public $numberCommunityCardsShown;
    public $lastInstancePlayNumber;
    // if game over
    public $winningPlayerId;
    public $playerHands;
    private $log;

    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
    }

    /*
     * Resets the active players data structures to account for players who 
     * left in the middle new ones taking their place. Use status only
     * Player turn numbers increase with seat number order.
     */

    public function ResetActivePlayers($isPractice = false) {
        // Delete players who left the casino table
        if ($isPractice) {
            PlayerInstance::DeleteDepartedPlayerInstances($this->id);
            // Get players with same gamesessionid and seat number
            // who don't have player state
            $newPlayerStatuses = PlayerInstance::GetNewPlayerStatesOnSession($this);
            foreach ($newPlayerStatuses as $playerStatus) {
                $playerStatus->Insert();
            }
        }
        //-------------------------------------------------------------
        // update game instance id for remaining players
        //-------------------------------------------------------------
        // Get all player instances for the game session, after adding/removing
        // game instance id updated when reset below
        $playerStatuses = PlayerInstance::GetPlayerInstancesForGame($this->id, $isSessionId = true);
        $lastDealerSN = $this->_getLastDealerSeatNumber();

        // assign turn numbers and update; note that players who just joined the table will have both an insert and an update
        $countPlayers = count($playerStatuses);
        for ($i = 0; $i < $countPlayers; $i++) {
            $turnNumber = ($i + ($countPlayers - 1) - $lastDealerSN) % $countPlayers;
            $playerStatuses[$i]->gameInstanceId = $this->id;
            $playerStatuses[$i]->turnNumber = $turnNumber;
            $playerStatuses[$i]->UpdatePlayerTurnAndReset();
        }
        return $playerStatuses;
    }

    /**
     * When a game is first started, the dealer and blinds are identified based on the dealer of the last instance.
     * Must be called after turns reset.
     * @param array(int, int) blindAmts The size of the small and large blinds.
     * @param PlayerInstance[] $pStatuses The index is the turn number because reset makes them so.
     * @param timestamp statusDT
     * @return blindBets[BetDto, Bet]
     */
    function InitInstanceWithDealerAndBlinds($blindAmts, $pStatuses) {
        $blind1 = $blindAmts[0];
        $blind2 = $blindAmts[1];

        $statusDT = Context::GetStatusDT();
        $count = count($pStatuses);
        $dealerTurn = 0 % $count;
        $blind1Turn = 1 % $count;
        $blind2Turn = 2 % $count;
        $nextPlayerTurn = 3 % $count;

        PlayerInstance::UpdateBlindByTurn($this->id, $blind1, $blind1Turn);
        PlayerInstance::UpdateBlindByTurn($this->id, $blind2, $blind2Turn);

        $this->nextPlayerId = $pStatuses[$nextPlayerTurn]->playerId;
        //$this->nextTurnNumber = $nextPlayerTurn;
        $this->dealerPlayerId = $pStatuses[$dealerTurn]->playerId;
        //$this->dealerTurnNumber = $dealerTurn;
        $this->firstPlayerId = $this->nextPlayerId;

        $this->lastBetSize = $blind2;
        $this->currentPotSize = $blind1 + $blind2;
        $this->numberPlayers = $count;
        $this->lastUpdateDateTime = $statusDT;
        $this->lastInstancePlayNumber = 2;
        $this->numberCommunityCardsShown = 3;

        $this->_updateDealerAndBlinds();
    }

    /**
     * Processes a player's action including practice virtual player, 
     * which includes the following steps:
     * 1) Update the player state: status, stake, lastPlayInstanceNumber, lastPlayAmount
     * 3) Update game instance: nextPlayerId and nextTurnNumber, potSize, lastBetSize, lastInstancePlayNumber
     * 4) Find next move, increase player # and community cards shown
     * @return ExpectedPokerMove 
     */
    function ApplyPlayerAction($playerAction) {
        // initialize playerstatus
        $playerInstanceStatus = EntityHelper::getPlayerInstance($this->id, $playerAction->playerId);

        // fields that automatically update
        // update player status, player number and last play amount
        $playerInstanceStatus->status = $playerAction->pokerActionType;
        $playerInstanceStatus->lastPlayInstanceNumber += 1;
        if (!is_null($playerAction->actionValue)) {
            $playerInstanceStatus->lastPlayAmount = $playerAction->actionValue;
        }
        // update instance last play number;
        $this->lastInstancePlayNumber +=1;

        // fields that need to be calculated
        // update the player stake and game instance potsize and lastbetsize
        if ($playerAction->pokerActionType == PokerActionType::CALLED ||
                $playerAction->pokerActionType == PokerActionType::BLIND_BET ||
                $playerAction->pokerActionType == PokerActionType::RAISED) {
            $playerInstanceStatus->stake -= $playerAction->actionValue;
            $this->potSize += $playerAction->actionValue;
            $this->lastBetSize = $playerAction->actionValue;
        }
        if ($playerAction->pokerActionType == PokerActionType::CHECKED) {
            $playerInstanceStatus->lastPlayAmount = null;
        }
        // update player status
        $playerInstanceStatus->UpdateAfterMove();

        $pokerMove = $playerAction->ConvertToPokerMove();
        $pokerMove->Delete();
        
        return $playerInstanceStatus;
    }

    /**
     * Called by timer if player did not make a move
     * Same as processActionFindNext but there was no action. Skipping a turn increments playnumber in instance and player state
     * 1) No need to validate move
     * 2) Increment timeout and set status to skipped for the player
     * 3) Find next player
     */
    function SkipTurn($pokerMove) {
        // initialize playerstatus
        $playerInstanceStatus = EntityHelper::getPlayerInstance($this->id, $pokerMove->playerId);

        // increment time out
        $playerInstanceStatus->numberTimeOuts += 1;
        // fields that automatically update
        // update player status and player number - last play amount doesn't change
        if ($playerInstanceStatus->status != PlayerStatusType::LEFT) {
            $playerInstanceStatus->status = PlayerStatusType::SKIPPED;
        }
       
        $playerInstanceStatus->lastPlayInstanceNumber +=1;

        // update instance last play number;
        $this->lastInstancePlayNumber +=1;

        // player status stake and last player amount does not change
        // game instance pot size and last bet size does not change.
        // update player status
        $playerInstanceStatus->UpdateAfterMove();

        $pokerMove->Delete();
        
        return $playerInstanceStatus;
    }
    
    /**
     * After a player makes a move, this operation is called to retrieve 
     * the constraints on the next player's
      moves. The next move is saved so that it can be expired.
     * Restrictions: updateNextPlayerIdAndTurn must have been called before.
     * @return ExpectedPokerMove
     */
    public function FindNextExpectedMove($curTurn) {
        global $playExpiration;
        global $practiceExpiration;
        $nextPokerMove = new ExpectedPokerMove();

        $this->_getNextPlayerIdAndTurn($curTurn);
        $expirationDateTime = new DateTime();
        if ($this->isNextPlayerVirtual == 1) {
            $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
        } else {
            $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
        }

        // 2 - parse out the next move
        // no need to set identifier, auto incrementing id
        $nextPokerMove->gameInstanceId = $this->id;
        $nextPokerMove->playerId = $this->nextPlayerId;
        $nextPokerMove->expirationDate = $expirationDateTime;

        // find move sizes - instance has not updated yet.
        // see if check allowed ---------------------------------------
        // Rule: not allowed on first round except for player who placed blind bet.
        $nextPokerMove->checkAmount = null;
        if ($this->lastInstancePlayNumber >= $this->numberPlayers - 1) {
            $nextPokerMove->checkAmount = 0;
        }
        // call size  -----------------------------------------------
        $nextPokerMove->callAmount = $this->lastBetSize;

        // see how much raise is enabled by ---------------------------
        // Rule: first player on first round can only raise by 2*bigblind, but that is taken
        // care of on initFirstMove.
        $nextPokerMove->raiseAmount = $this->tableMinimum + $this->lastBetSize;

        $nextPokerMove->Insert();
        return $nextPokerMove;
    }

    /**
     * Find the winner and everyone's hands at the end of the game.
     * @param type $statusDT
     */
    function FindWinner() {
        $statusDT = Context::GetStatusDT();
        $gameCards = CardHelper::getGameCardsForInstance($this->id);
        $playerHands = $gameCards->playerHands;

        $hH = new HighestHand(); // holds the highest hand found when traversing the players
        $hH->handCategory = 0;
        $hH->handRank = 0;
        $hH->winningPlayerId = 0;

        for ($i = 0; $i < count($playerHands); $i++) {
            $cards = array(
                $gameCards->communityCards[0]->cardIndex,
                $gameCards->communityCards[1]->cardIndex,
                $gameCards->communityCards[2]->cardIndex,
                $gameCards->communityCards[3]->cardIndex,
                $gameCards->communityCards[4]->cardIndex,
                $gameCards->playerHands[$i]->pokerCard1->cardIndex,
                $gameCards->playerHands[$i]->pokerCard2->cardIndex
            );
            $playerHands[$i] = CardHelper::identifyPlayerHand($cards, $playerHands[$i]);

            $hH = CardHelper::getHigherCard($hH, $playerHands[$i]);
        }

        // update plsayer hands.
        for ($i = 0; $i < count($playerHands); $i++) {
            // update hand for each player
            if ($playerHands[$i]->playerId == $hH->winningPlayerId) {
                $playerHands[$i]->isWinningHand = 1;
                $status = "Won";
            } else {
                $status = "Lost";
                $playerHands[$i]->isWinningHand = 0;
            }
        }
        // save winning player Id
        $this->lastUpdateDateTime = $statusDT;
        $this->winningPlayerId = $hH->winningPlayerId;
        $this->_updateWinner();
        $this->playerHands = $playerHands;
        PlayerInstance::UpdatePlayerStateHands($playerHands);
        /* --------------------------------------------------------------------- */
        /* mark cards that are to be seen if item is enabled */
        CheatingHelper::MarkGameCards($this);
        /* --------------------------------------------------------------------- */
    }

    /**
     * Checks whether the end of the round is reached (need to deal community
     * cards in that case)
     * Returns the round number that ended, null otherwise
     */
    public function IsRoundEnd() {
        $numberPlayers = $this->numberPlayers;
        $instancePlayNumber = $this->lastInstancePlayNumber;
        switch ($instancePlayNumber) {
            case $numberPlayers:
                return 1;
                break;
            case $numberPlayers * 2:
                return 2;
                break;
            case $numberPlayers * 3:
                return 3;
                break;
            case $numberPlayers * 4:
                return 4;
                break;
        }
        return null;
    }

    /**
     * Gets the game result if ended
     */

    /**
     * Checks whether the end of the game was reached on database.
     * @return bool
     */
    public function IsGameEnded() {
        if ($this->winningPlayerId) {
            return true;
        }
        // see if current player's move triggered and end date before calculating the next.
        // 1 - if only on user remaining (status - not folded) then end game
        $folded = PlayerStatusType::FOLDED;
        $left = PlayerStatusType::LEFT;
        $result = executeSQL("SELECT count(1) FROM PlayerState WHERE GameInstanceId =
            $this->id AND Status != '$folded' AND Status != '$left'", __FUNCTION__ . "
            : Error selecting PlayerState instance $this->id");
        $row = mysql_fetch_array($result);
        if ($row[0] <= 1) {
            return true;
        }
        // if next player and previous player play is fourth, end game.
        // instance has been updated yet by the calling function
        if ($this->instancePlayNumber >= $this->numberPlayers * 4) {
            return true;
        }
        return false;
    }

    /**
     * Create an message and send it to everyone's queue including the player who made the move.
     * @param type $isTimeOut Whether the action was by a player or triggered by timeout
     */
    function CommunicateMoveResult($gameStatusDto, $isTimeOut) {
        $ex = Context::GetQEx;

        // a timeout by a practice game is not a time out.
        $actionType = EventType::PLAYER_MOVE;
        if ($this->isPractice == 0 && $isTimeOut == 1) {
            $actionType = EventType::TIME_OUT;
        }

        // get the players;
        $players = null;
        if ($this->isPractice == 1) {
            // for practice game, get non-virtual player only
            // by convention first player on list, but not using convention
            $players = array(EntityHelper::getPracticeInstancePlayer($this->id));
        } else {
            $casinoTable = EntityHelper::getCasinoTableForSession($this->gameSessionId);
            $players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
        }
        for ($i = 0; $i < count($players); $i++) {

            if ($players[$i]->isVirtual == 1 ||
                    $players[$i] == PlayerStatusType::LEFT) {
                continue;
            }
            $message = new EventMessage($this->gameSessionId, $players[$i]->id, $actionType, $this->lastUpdateDateTime, $gameStatusDto);
            QueueManager::QueueMessage($ex, $players[$i]->id, json_encode($message));
        }
    }

    /**
     * New game instances don't have player info
     * @param type $gameInstance
     */
    public function Insert() {
        // TODO: set null?
        executeSQL("INSERT INTO GameInstance (Id, GameSessionId, Status, 
            StartDateTime, LastUpdateDateTime, CurrentPotSize, LastBetSize, 
            NumberCommunityCardsShown, LastInstancePlayNumber) VALUES (
                $this->id, 
                $this->gameSessionId, 
                $this->status, 
                '$this->startDateTime',
                '$this->lastUpdateDateTime',
                $this->currentPotSize,
                $this->lastBetSize,
                $this->numberCommunityCardsShown,
                $this->lastInstancePlayNumber)", __FUNCTION__ . ": ERROR inserting into GameInstance with generated id
                $this->id");
    }

    /**
     * Updates GameInstance and PlayerState with the results of the action.
     * @param type $statusDT
     */
    public function UpdateInstanceAfterMove() {
        $statusDT = Context::GetStatusDT();
        // update in database
        // no need to save the next player turn number
        executeSQL("UPDATE GameInstance SET LastUpdateDateTime = '$statusDT',
                NextPlayerId = $this->nextPlayerId,
                PotSize = $this->potSize,
                LastBetSize = $this->lastBetSize,
                NumberCommunityCardsShown = $this->numberCommunityCardsShown,
                LastInstancePlayNumber = $this->lastInstancePlayNumber
            WHERE Id = $this->id", __FUNCTION__ . "
                : Error updating on GameState for instance $this->id");
    }

    public static function DeleteExpiredInstances($expirationDateTime) {
        // TODO: also flush player action and game card from memory as 
        // should be done when game ended
        executeSQL("DELETE FROM ExpectedPokerMove WHERE GameInstanceId in
            (SELECT ID FROM GameInstance WHERE LastUpdateDateTime <= $expirationDateTime)",
                __FUNCTION__ . ":Error deleting expired poker moves");
        executeSQL("DELETE FROM GameInstance WHERE LastUpdateDateTime <= $expirationDateTime",
                __FUNCTION__ . ": Error deleting expired game instances");
    }
    /*     * ***************************************************************************** */
    /* update instance status */

    /**
     * Gets the seat number for the last active instance of a game session
     * @param type $gameSessionId
     */
    private function _getLastDealerSeatNumber() {
        $result = executeSQL("SELECT SeatNumber 
            FROM GameInstance i 
            INNER JOIN PlayerState ps on i.DealerPlayerId = ps.PlayerId
            WHERE i.GameSessionId = $this->gameSessionId
                ORDER BY i.Id LIMIT 1", __FUNCTION__ . "
                : Error selecting from PlayerState for last dealer in 
                game session $this->gameSessionId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        return $row[0];
    }

    /**
     * Get the player id and turn that should make the next play after current. This method updates the game instance status.
     * @param type $curTurnNum current turn number
     */
    private function _getNextPlayerIdAndTurn($curTurnNum) {
        if (is_null($curTurnNum)) {
            $curTurnNum = -1;
        }
        $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual FROM PlayerState
                WHERE GameInstanceId = $this->id AND TurnNumber > $curTurnNum
                ORDER BY TurnNumber LIMIT 1", __FUNCTION__ . "
                : Error selecting from PlayerState instance id $this->id and
                turn greater than $curTurnNum");
        if (mysql_num_rows($result) == 0) {
            $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual
                    FROM PlayerState WHERE GameInstanceId = $this->id AND TurnNumber >= 0
                    ORDER BY TurnNumber LIMIT 1", __FUNCTION__ . "
                    : Error selecting from PlayerState instance id $this->id and turn >=0");
        }
        if (mysql_num_rows($result) == 0) {
            throw new Exception(__FUNCTION__ . ": ERROR - Next PlayerState not found for 
                    instance id $this->id AND current seat $curTurnNum");
        }
        $row = mysql_fetch_array($result);

        // recursively find the next player
        $nextTurn = $row['TurnNumber'];
        if ($row['Status'] != PlayerStatusType::FOLDED &&
                $row['Status'] != PlayerStatusType::LEFT) {
            $nextPlayerId = $row['PlayerId'];
            $nextPlayerStatus = EntityHelper::getPlayerInstance($this->id, $nextPlayerId);
            //$nextPlayerStatus->isVirtual = $row['IsVirtual'];
            $this->nextPlayerId = $nextPlayerId;
            $this->nextTurnNumber = $nextTurn;
            $this->isNextPlayerVirtual = $isVirtual;
            return $nextPlayerStatus;
        } else {
            // keep looking, but increment play counters
            // is the next line correct? what's the point?
            $this->playerInstanceStatus->lastPlayInstanceNumber +=1;
            $this->lastInstancePlayNumber +=1;
            $this->_getNextPlayerIdAndTurn($nextTurn);
        }
    }

    private function _updateDealerAndBlinds() {
        executeSQL("UPDATE GameInstance SET 
            LastUpdateDateTime = '$this->lastUpdateDateTime',
            NumberPlayers = $this->numberPlayers,
            DealerPlayerId = $this->dealerPlayerId, 
            FirstPlayerId = $this->firstPlayerId,
            NextPlayerId = $this->nextPlayerId,
            CurrentPotSize = $this->currentPotSize,
            LastBetSize = $this->lastBetSize,
            NumberCommunityCardsShown = $this->numberCommunityCardsShown,
            LastInstancePlayNumber = $this->lastInstancePlayNumber
                WHERE Id = $this->id", __FUNCTION__ . ": Error updating GameInstance with
                dealer, next player and pot size instance $this->id ");
    }

    /**
     * updates the player state also
     */
    private function _updateWinner() {
        executeSQL("UPDATE GameInstance SET WinningPlayerId = $this->winningPlayerId,
                LastUpdateDateTime = '$this->lastUpdateDateTime' 
                    WHERE Id = $this->id", __FUNCTION__ . "
                : ERROR updating GameInstance id $this->id");

        // reward winner's stake
        executeSQL("UPDATE PlayerState SET Stake = Stake + $this->potSize,
                LastUpdateDateTime = '$this->lastUpdateDateTime' WHERE PlayerId =
                $this->winningPlayerId AND GameInstanceId = $this->id", __FUNCTION__ . "
                : ERROR updating winning PlayerState for game instance id $this->id");
    }

}

?>
