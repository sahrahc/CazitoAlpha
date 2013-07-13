<?php

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

    public function __construct($id) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $id == null ? null : (int)$id;
    }

    /*
     * Resets the active players data structures to account for players who 
     * left in the middle new ones taking their place. Use status only
     * Player turn numbers increase with seat number order.
     */

    public function ResetActivePlayers($isPractice = false) {
        // Delete players who left the casino table
        if (!$isPractice) {
            PlayerInstance::DeleteDepartedPlayerInstances($this->gameSessionId);
            // Get players with same gamesessionid and seat number
            // who don't have player state
            $newPlayerStatuses = PlayerInstance::GetNewPlayerStatesOnSession($this);
            if (!is_null($newPlayerStatuses)) {
                foreach ($newPlayerStatuses as $playerStatus) {
                    $playerStatus->Insert();
                }
            }
        }
        //-------------------------------------------------------------
        // update game instance id for remaining players
        //-------------------------------------------------------------
        // Get all player instances for the game session, after adding/removing
        // game instance id updated when reset below
        $playerStatuses = PlayerInstance::GetPlayerInstancesForGame($this->gameSessionId, $isSessionId = true);
        $lastDealerSN = $this->_getLastDealerSeatNumber();

        // assign turn numbers and update; note that players who just joined the table will have both an insert and an update
        $countPlayers = count($playerStatuses);
        for ($i = 0; $i < $countPlayers; $i++) {
            // first turn is for user left to the one who placed blind
            $turnNumber = ($i + ($countPlayers - 1) - $lastDealerSN) % $countPlayers;
            $playerStatuses[$i]->gameInstanceId = $this->id;
            $playerStatuses[$i]->turnNumber = $turnNumber;
            $playerStatuses[$i]->UpdatePlayerTurnAndReset();
        }
        return PlayerInstance::GetPlayerInstancesForGame($this->id);
    }

    /**
     * When a game is first started, the dealer and blinds are identified based on the dealer of the last instance.
     * Must be called after turns reset.
     * @param array(int, int) blindAmts The size of the small and large blinds.
     * @param PlayerInstance[] $pStatuses The index is the turn number because reset makes them so.
     * @param timestamp statusDT
     */
    function InitInstanceWithDealerAndBlinds($blindAmts, $pStatuses) {
        $blind1 = $blindAmts[0];
        $blind2 = $blindAmts[1];

        $statusDT = Context::GetStatusDT();
        $count = count($pStatuses);
        // dealer is always turn 0 but need modulus because number of seats
        // may result in less turns than roles (e.g., 2 players)
        $dealerTurn = 0 % $count; // for symmetry
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

        $playerInstanceStatus->status = $playerAction->pokerActionType;

        // update instance last play number;
        $this->lastInstancePlayNumber +=1;
        $playerInstanceStatus->lastPlayInstanceNumber = $this->lastInstancePlayNumber;

        // fields that need to be calculated
        // update the player stake and game instance potsize and lastbetsize
        // for fold and checks, last play amount does not change
        if ($playerAction->pokerActionType == PokerActionType::CALLED ||
                $playerAction->pokerActionType == PokerActionType::BLIND_BET ||
                $playerAction->pokerActionType == PokerActionType::RAISED) {    
            $playerInstanceStatus->currentStake -= $playerAction->actionValue;
            $playerInstanceStatus->lastPlayAmount = $playerAction->actionValue; 
            $this->currentPotSize += $playerAction->actionValue;
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
        if ($playerInstanceStatus == PlayerStatusType::LEFT) {
            return;
        }
        // increment time out
        $playerInstanceStatus->numberTimeOuts += 1;
        // fields that automatically update
        // update player status and player number - last play amount doesn't change
        if ($playerInstanceStatus->status != PlayerStatusType::LEFT) {
            $playerInstanceStatus->status = PlayerStatusType::SKIPPED;
        }
        if ($playerInstanceStatus->numberTimeOuts >= 3) {
            $playerInstanceStatus->status = PlayerStatusType::LEFT;
        } else {
            $playerInstanceStatus->status = PlayerStatusType::SKIPPED;
        }        
        // update instance last play number;
        $this->lastInstancePlayNumber +=1;
        $playerInstanceStatus->lastPlayInstanceNumber = $this->lastInstancePlayNumber;

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
     * 
     */

    /**
     * Find the winner and everyone's hands at the end of the game.
     * @param type $statusDT
     */
    function FindWinner() {
        $statusDT = Context::GetStatusDT();
        // gets all the game cards for the instance
        $gameCards = CardHelper::getGameCardsForInstance($this->id);
        $playerHands = $gameCards->playerHands;

        $hH = new HighestHand(); // holds the highest hand found when traversing the players
        $hH->handCategory = 0;
        $hH->handRank = 0;
        $hH->winningPlayerId = 0;

        for ($i = 0; $i < count($playerHands); $i++) {
            // folded does not count
            if ($playerHands)
            $cards = array(
                $gameCards->communityCards[0]->cardIndex,
                $gameCards->communityCards[1]->cardIndex,
                $gameCards->communityCards[2]->cardIndex,
                $gameCards->communityCards[3]->cardIndex,
                $gameCards->communityCards[4]->cardIndex,
                $gameCards->playerHands[$i]->pokerCard1->cardIndex,
                $gameCards->playerHands[$i]->pokerCard2->cardIndex
            );
            //$playerHands[$i]->pokerHandType = 
            CardHelper::identifyPlayerHand($cards, $playerHands[$i]);

            $hH = CardHelper::getHigherCard($hH, $playerHands[$i]);
        }

        // update player hands.
        for ($i = 0; $i < count($playerHands); $i++) {
            // update hand for each player
            if ($playerHands[$i]->playerId == $hH->winningPlayerId) {
                $playerHands[$i]->isWinningHand = 1;
            } else {
                $playerHands[$i]->isWinningHand = 0;
            }
        }
        // save winning player Id
        $this->lastUpdateDateTime = $statusDT;
        $this->winningPlayerId = $hH->winningPlayerId;
        // update instance with winner info
        $this->_updateWinner();
        $this->playerHands = $playerHands;
        PlayerInstance::UpdatePlayerStateHands($playerHands);
        /* --------------------------------------------------------------------- */
        /* mark cards that are to be seen if item is enabled */
        PlayerVisibleCard::AddVisibleCards($this);
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
        if (!is_null($this->winningPlayerId)) {
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
        if ($this->lastInstancePlayNumber >= $this->numberPlayers * 4) {
            return true;
        }
        return false;
    }

    /**
     * Create an message and send it to everyone's queue including the player who made the move.
     * @param type $isTimeOut Whether the action was by a player or triggered by timeout
     */
    function CommunicateMoveResult($gameStatusDto) {
        $ex = Context::GetExchangePlayer();

        // a timeout by a practice game is not a time out.
        $actionType = EventType::ChangeNextTurn;
        
        // get the players;
        $players = null;
        $gameSession = EntityHelper::GetGameSession($gameStatusDto->gameSessionId);
        if ($gameSession->isPractice == 1) {
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
            $message = new QueueMessage($actionType, $gameStatusDto);
            QueueManager::SendToPlayer($ex, $players[$i]->id, json_encode($message));
        }
    }

    /**
     * New game instances don't have player info
     * @param type $gameInstance
     */
    public function Insert() {
        global $dateTimeFormat;
        
        $startDTString = $this->startDateTime->format($dateTimeFormat);
        $updateDTString = $this->lastUpdateDateTime->format($dateTimeFormat);
        // TODO: set null?
        executeSQL("INSERT INTO GameInstance (Id, GameSessionId, Status, 
            StartDateTime, LastUpdateDateTime, CurrentPotSize, LastBetSize, 
            NumberCommunityCardsShown, LastInstancePlayNumber) VALUES (
                $this->id, 
                $this->gameSessionId, 
                '$this->status', 
                '$startDTString',
                '$updateDTString',
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
        global $dateTimeFormat;
        $this->status = GameStatus::IN_PROGRESS;
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        // update in database
        // no need to save the next player turn number
        executeSQL("UPDATE GameInstance SET LastUpdateDateTime = '$statusDT',
            Status = '$this->status',
                NextPlayerId = $this->nextPlayerId,
                CurrentPotSize = $this->currentPotSize,
                LastBetSize = $this->lastBetSize,
                NumberCommunityCardsShown = $this->numberCommunityCardsShown,
                LastInstancePlayNumber = $this->lastInstancePlayNumber
            WHERE Id = $this->id", __FUNCTION__ . "
                : Error updating on GameState for instance $this->id");
    }

    public static function DeleteExpiredInstances($expirationDateTime) {
        global $dateTimeFormat;
        
        $endString = $expirationDateTime->format($dateTimeFormat);
        // TODO: also flush player action and game card from memory as 
        // should be done when game ended
        executeSQL("DELETE FROM ExpectedPokerMove WHERE GameInstanceId in
            (SELECT ID FROM GameInstance WHERE LastUpdateDateTime <= '$endString')", 
                __FUNCTION__ . ":Error deleting expired poker moves");
        executeSQL("DELETE FROM GameInstance WHERE LastUpdateDateTime <= '$endString'", 
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
            return -1;
        }
        $row = mysql_fetch_array($result);
        return $row[0];
    }

    /**
     * Get the player id and turn that should make the next play after current. This method updates the game instance status.
     * @param type $curTurnNum current turn number
     */
    public function GetNextPlayerIdAndTurn($curTurnNum) {
        if (is_null($curTurnNum)) {
            $curTurnNum = -1;
        }
 //                   AND Status != '" . PlayerStatusType::LEFT. "'
        $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual 
            FROM PlayerState
                WHERE GameInstanceId = $this->id AND TurnNumber > $curTurnNum
                ORDER BY TurnNumber LIMIT 1", __FUNCTION__ . "
                : Error selecting from PlayerState instance id $this->id and
                turn greater than $curTurnNum");
        if (mysql_num_rows($result) == 0) {
            $result = executeSQL("SELECT PlayerId, TurnNumber, Status, IsVirtual
                    FROM PlayerState 
                    WHERE GameInstanceId = $this->id AND TurnNumber >= 0
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
            //$this->nextTurnNumber = $nextTurn;
            return $nextPlayerStatus;
        } else {
            // keep looking, but increment play counters
            // is the next line correct? what's the point?
            //$this->playerInstanceStatus->lastPlayInstanceNumber +=1;
            $this->lastInstancePlayNumber +=1;
            if ($this->lastInstancePlayNumber < 4 * $this->numberPlayers) {
                $this->GetNextPlayerIdAndTurn($nextTurn);
            } else {
                $this->nextPlayerId = null;
            }
        }
    }

    private function _updateDealerAndBlinds() {
        global $dateTimeFormat;
        $updateDTString = $this->lastUpdateDateTime->format($dateTimeFormat);
        executeSQL("UPDATE GameInstance SET 
            LastUpdateDateTime = '$updateDTString',
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
        global $dateTimeFormat;
        $updateDTString = $this->lastUpdateDateTime->format($dateTimeFormat);
        $status = GameStatus::ENDED;

        executeSQL("UPDATE GameInstance SET Status = '$status',
            WinningPlayerId = $this->winningPlayerId,
                LastUpdateDateTime = '$updateDTString' 
                    WHERE Id = $this->id", __FUNCTION__ . "
                : ERROR updating GameInstance id $this->id");

        // reward winner's stake
        executeSQL("UPDATE PlayerState SET CurrentStake = CurrentStake + $this->currentPotSize,
                LastUpdateDateTime = '$updateDTString' WHERE PlayerId =
                $this->winningPlayerId AND GameInstanceId = $this->id", __FUNCTION__ . "
                : ERROR updating winning PlayerState for game instance id $this->id");
    }

}

?>
