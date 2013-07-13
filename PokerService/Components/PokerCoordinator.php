<?php

/*
 * PokerCoordinator: business logic for actions that require multiple business
 * objects to act in sequence. This logic does not fit the object model 
 * paradigm.
 * Every action that requires communication to all players fit this category.
 */
/* * ************************************************************************************* */

/**
 * Security checks already done - game session and instance to player validation
 */
class PokerCoordinator {

    /**
     * Start a live game with real players. Validates that a game is not already 
     * in progress. session and player id's already verified.
     * 
     * @global log $analytics
     * @param int $gameSessionId
     * @param int $requestingPlayerId
     */
    public static function StartGame($gameSessionId, $requestingPlayerId) {
        global $analytics;
        global $log;

        // validate last game was finished. Abandoned games do finish 
        // with all players timing out.
        $lastInstance = EntityHelper::getSessionLastInstance($gameSessionId);
        if ($lastInstance && !$lastInstance->IsGameEnded()) {
            $analytics->info("Game instance for session $gameSessionId requested by $requestingPlayerId but live instance $lastInstance->id");
            $log->error("Game instance for session $gameSessionId requested by $requestingPlayerId but live instance $lastInstance->id");
            return;
        } else {
            $analytics->info("Game instance for session $gameSessionId requested by $requestingPlayerId");
        }

        // init objects
        $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
        $blindBetAmounts = $casinoTable->FindBlindBetAmounts();
        $tableSize = $casinoTable->tableMinimum;

        $gameSession = EntityHelper::GetGameSession($casinoTable->currentGameSessionId);
        // validate it is a practice session
        if ($gameSession->isPractice) {
            $log->error("Practice game $gameSessionId confused as live session.");
            return;
        }

        // sequencing of poker game start - same sequencing for live vs.
        // practice games (excluding communication and wait list)
        $gameInstance = $gameSession->InitNewLiveGameInstance();
        $playerStatuses = $gameInstance->ResetActivePlayers();

        $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
        GameInstanceCards::InitDealGameCards($gameInstance);
        $firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize);

        // start populating the response
        $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
        $updatedPlayerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStatuses, true);
        $gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);
        $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);
        $gameStatusDto->waitingListSize = $casinoTable->GetWaitingListSize();
        // communicate to all playing and waiting players. Can't use PlayerStatuses because instance only
        // usePlayerHandDto specific for each user, set by communicate function
        $gameSession->CommunicateGameStarted($gameStatusDto, EntityHelper::GetPlayersForCasinoTable($casinoTable->id));

        $messagesOut = PlayerVisibleCard::RevealMarkedCards($gameInstance, ItemType::SOCIAL_SPOTTER);
        if (count($messagesOut) > 0) {
        CheatingHelper::_communicateCheatingOutcome($requestingPlayerId, $messagesOut);
        }
    }

    /**
     * Start a practice game (other than the first game which started 
     * with the practice session). Validates that a game is not already 
     * in progress. session and player id's already verified.
     * @global type $numberSeats
     * @global type $defaultTableMin
     * @global type $log
     * @global type $dateTimeFormat
     */
    //

    /**
     * Start a practice game. Validation 
     * 
     * @global type $log
     * @param int $gameSessionId
     */
    public static function StartPracticeGame($gameSessionId) {
        global $log;

        // can't start game if another is being played
        if (is_null(EntityHelper::getSessionLastInstance($gameSessionId))) {
            return;
        }

        $statusDateTime = Context::GetStatusDT();

        $gameSession = EntityHelper::GetGameSession($gameSessionId);
        // validate it is a practice session
        if (!$gameSession->isPractice) {
            $log->error("Live game $gameSessionId confused as practice session.");
            return;
        }

        $blindBetAmounts = $gameSession->FindBlindBetAmounts();
        $tableSize = $gameSession->tableMinimum;

        // sequencing of poker game start - same sequencing for live vs.
        // practice games (excluding communication)
        $gameInstance = $gameSession->InitNewPracticeInstance();
        $playerStatuses = $gameInstance->ResetActivePlayers(true);

        $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
        GameInstanceCards::InitDealGameCards($gameInstance);
        $firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize, 1);

        // start populating the response
        $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
        $updatedPlayerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStatuses, true);
        $gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);
        $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);
        // communicate to user
        $gameSession->CommunicateGameStarted($gameStatusDto, $statusDateTime);
    }

    private static function endGame(&$gameInstance, &$gameStatusDto) {
        $gameInstance->status = GameStatus::ENDED;
        // FindWinner updates the winning player and all player hands
        $gameInstance->FindWinner();
        $gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
        $playerHandsDto = PlayerHandDto::mapPlayerHands($gameInstance->playerHands);
        $gameStatusDto->playerHandsDto = $playerHandsDto;
        $playerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
        // folded players should have status set lost
        foreach($playerStatuses as $playerStatus) {
            if ($playerStatus->status == PlayerStatusType::FOLDED) {
                $playerStatus->status = PlayerStatusType::LOST;
                $playerStatus->UpdatePlayerStatus(PlayerStatusType::LOST);
            }
        }
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($playerStatuses);
    }

    /**
     * next move is for a user who left, adjust
     */
    private static function adjustNextForLeftUser($gameInstance, $expectedMove = null) {
        if (is_null($expectedMove)) {
            $expectedMove = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstance->id);
        }
        $badPlayer = EntityHelper::getPlayerInstance($gameInstance->id, $expectedMove->playerId);
        // "catch up" game instance to real expected move ...
        $nextMove = ExpectedPokerMove::FindNextExpectedMoveForInstance($gameInstance, $badPlayer->turnNumber);
        // ... and increment counter with the obsolete move that is being skipped
        $expectedMove->Delete();
        return $nextMove;
    }

    /**
     * Processes a player action, whether dummy or live. In the latter case
     * $validateMove is true so that action is validated against expected 
     * Session and player id's already verified.
     * 
     * @param PlayerAction $playerAction
     * @param boolean $validateMove
     */
    public static function MakePokerMove($playerAction, $validateMove) {
        global $log;
        global $dateTimeFormat;

        $gameInstance = EntityHelper::GetGameInstance($playerAction->gameInstanceId);
        if ($gameInstance->IsGameEnded()) {
            $log->error("User attempted move but game is ended: " . json_encode($playerAction));
            return;
        }

        // craft response DTO
        $gameStatusDto = new GameStatusDto();
        $gameStatusDto->gameSessionId = $gameInstance->gameSessionId;
        $gameStatusDto->gameInstanceId = $gameInstance->id;
        $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
        //$gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime->format($dateTimeFormat);
        $gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime->format($dateTimeFormat);

        if ($validateMove) {
            $isMoveValid = $playerAction->IsMoveValid();
            // outlier case, next user left someone else making move, 
            // find next expected move, end of move may be triggered if none.
            if (is_null($isMoveValid)) {
                $nextMove = self::adjustNextForLeftUser($gameInstance);
                $gameInstance->lastInstancePlayNumber += 1;
                if (!is_null($nextMove) && $nextMove->playerId == $playerAction->playerId) {
                    $playerInstanceStatus = $gameInstance->ApplyPlayerAction($playerAction);
                } else {
                    $log->warn(__CLASS__ . "-" . __FUNCTION__ . "Got new expected move but wrong player $playerAction->playerId moved. Player $nextMove->playerId expected.");
                    return;
                }
            }
            // not valid
            else if (!$isMoveValid) {
                return;
            } else if ($isMoveValid) {
                $playerInstanceStatus = $gameInstance->ApplyPlayerAction($playerAction);
            }
        } else {
            // updates PlayerStatus
            $playerInstanceStatus = $gameInstance->ApplyPlayerAction($playerAction);
        }
        // current play may have triggered game end
        if ($gameInstance->IsGameEnded()) {
            self::endGame($gameInstance, $gameStatusDto);
        } else {
            // update the next player id and turn number
            $curTurn = $playerInstanceStatus->turnNumber;

            // update next player id on game instance
            $nextMove = ExpectedPokerMove::FindNextExpectedMoveForInstance($gameInstance, $curTurn);
            if (is_null($nextMove)) {
                // if no next move, then game is ended.
                self::endGame($gameInstance, $gameStatusDto);
            }
            $gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($nextMove);

            // find whether community cards need to be send (gameinstance is updated)
            // GameInstance updated by function. Needs to be called after finding
            // next because depends on last play amount being updated
            $roundNumber = $gameInstance->IsRoundEnd();
            if ($roundNumber) { // === 1 or $roundNumber === 2) {
                $newCards = GameInstanceCards::DealCommunityCards($gameInstance, $roundNumber);
                if ($newCards) {
                    $gameStatusDto->newCommunityCards = $newCards;
                    $gameInstance->numberCommunityCardsShown = + count($newCards);
                }
            }
            // only send the player who changed status
            $gameStatusDto->turnPlayerStatusDto = PlayerStatusDto::mapPlayerStatus($playerInstanceStatus);
        }
        $gameInstance->UpdateInstanceAfterMove();
        $gameStatusDto->gameStatus = $gameInstance->status;
        $gameStatusDto->currentPotSize = $gameInstance->currentPotSize;

        $gameInstance->CommunicateMoveResult($gameStatusDto);
    }

    /**
     * Skip an expected move in a game. Time out the user if three skips
     * 
     * @global type $log
     * @param ExpectedPokerMove $pokerMove
     * @param GameInstance $gameInstance
     */
    public static function SkipPokerMove($pokerMove, $gameInstance) {
        global $log;
        global $dateTimeFormat;
        // Logic --------------------------------------------------------------------------------
        if ($gameInstance->IsGameEnded()) {
            $log->error("Time out triggered but game already ended: " . json_encode($pokerMove));
            return;
        }

        // updates PlayerStatus
        $playerInstanceStatus = $gameInstance->SkipTurn($pokerMove);
        if ($playerInstanceStatus->status == PlayerStatusType::LEFT) {
            $pokerMove = self::adjustNextForLeftUser($gameInstance, $pokerMove);
            $gameInstance->lastInstancePlayNumber +=1;
        }
        $isTimeOut = false;
        if ($playerInstanceStatus->numberTimeOuts >= 3) {
            $isTimeOut = true;
        }
        if ($isTimeOut || $playerInstanceStatus->status == PlayerStatusType::LEFT) {
            $casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameSessionId);
            $vacatedSeat = TableCoordinator::RemoveUserFromTable($casinoTable, $pokerMove->playerId);
            TableCoordinator::ReserveAndOfferSeat($casinoTable, $vacatedSeat);
        }

        // follow player status update with instance level follow-up
        $gameStatusDto = new GameStatusDto();
        $gameStatusDto->gameSessionId = $gameInstance->gameSessionId;
        $gameStatusDto->gameInstanceId = $gameInstance->id;
        $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
        $gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime->format($dateTimeFormat);
        ;

        // current play may have triggered game end
        if ($gameInstance->IsGameEnded()) {
            self::endGame($gameInstance, $gameStatusDto);
        } else {
            // update the next player id and turn number
            $curTurn = $playerInstanceStatus->turnNumber;

            // update next player id on game instance
            $nextMove = ExpectedPokerMove::FindNextExpectedMoveForInstance($gameInstance, $curTurn);
            if (is_null($nextMove)) {
                // if no next move, then game is ended.
                self::endGame($gameInstance, $gameStatusDto);
            }
            $gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($nextMove);

            // find whether community cards need to be sent (gameinstance is updated)
            // GameInstance updated by function. Needs to be called after finding
            // next because depends on last play amount being updated
            $roundNumber = $gameInstance->IsRoundEnd();
            if ($roundNumber) { // === 1 || $roundNumber === 2 || $roundNumber === 3) {
                $newCards = GameInstanceCards::DealCommunityCards($gameInstance, $roundNumber);
                if ($newCards) {
                    $gameStatusDto->newCommunityCards = $newCards;
                    $gameInstance->numberCommunityCardsShown = + count($newCards);
                }
            }
            // only send the player who changed status
            $gameStatusDto->turnPlayerStatusDto = PlayerStatusDto::mapPlayerStatus($playerInstanceStatus);
        }
        $gameInstance->UpdateInstanceAfterMove();
        $gameStatusDto->gameStatus = $gameInstance->status;
        $gameStatusDto->currentPotSize = $gameInstance->currentPotSize;

        $gameInstance->CommunicateMoveResult($gameStatusDto);
    }

    public static function Cheat($gameSessionId, $playerId, $cheatRequestDto, $gameInstanceId = null) {

        // cheating items game instance null
        if ($cheatRequestDto->itemType === ItemType::SOCIAL_SPOTTER) {
            CheatingHelper::StartCardMarking($playerId, $gameSessionId, ItemType::SOCIAL_SPOTTER);
            return;
        }

        // an active game instance is required for the next items
        $gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
        if (is_null($gameInstance)) {
            $gameInstance = EntityHelper::getSessionLastInstance($gameSessionId);
        }
        if (is_null($gameInstance)) {
            return;
        }
        $gameInstanceId = $gameInstance->id;
        $gameSessionId = $gameInstance->gameSessionId;

        // these are cheating items that require an active game instance 
        // or are available only while actively playing a game (even if thye
        // do not expire when the game ends.
        switch ($cheatRequestDto->itemType) {
            case ItemType::ACE_PUSHER:
                $playerCardNumber = $cheatRequestDto->playerCardNumber;
                CheatingHelper::PushRandomAce($playerId, $gameInstance, $playerCardNumber, $cheatRequestDto->itemType);
                return;
            case ItemType::HEART_MARKER:
            case ItemType::CLUB_MARKER:
            case ItemType::DIAMOND_MARKER:
                CheatingHelper::GetSuitForAllGameCards($playerId, $gameInstance, $cheatRequestDto->itemType);
                return;
            case ItemType::RIVER_SHUFFLER:
                CheatingHelper::CheatLookRiverCard($playerId, $gameInstance, $cheatRequestDto->itemType);
                return;
            case ItemType::RIVER_SHUFFLER_USE:
                CheatingHelper::CheatSwapRiverCard($playerId, $gameInstance, $cheatRequestDto->itemType);
                return;
            default:
                break;
        }
    }

}

?>
