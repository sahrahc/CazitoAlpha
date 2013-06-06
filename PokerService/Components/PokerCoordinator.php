<?php

/*
 * PokerCoordinator: business logic for actions that require multiple business
 * objects to act in sequence. This logic does not fit the object model 
 * paradigm.
 * Every action that requires communication to all players fit this category.
 */
/* * ************************************************************************************* */
// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

/**
 * All the coordinator functions go through this process
 * 1) get the list of active poker tables
 */
class PokerCoordinator {

    public static function startGame($gameSessionId, $requestingPlayerId) {
        global $log;

        $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
        
        $blindBetAmounts = $casinoTable->FindBlindBetAmounts();
        $tableSize = $casinoTable->tableMinimum;
        
        // sequencing of poker game start
        $gameSession = EntityHelper::GetGameSession($casinoTable->currentGameSessionId);        
        
        $gameInstance = $gameSession->InitNewLiveGameInstance();
        $playerStatuses = $gameInstance->ResetActivePlayers();
        
        $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
        GameInstanceCards::InitDealGameCards($gameInstance);
        $firstMove = ExpectedPokerMove::InitFirstMove($gameInstance, $tableSize);

        // start populating the response
        $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
        $updatedPlayerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStatuses);
        $gameStatusDto->nextMoveDto = new PokerMoveDto($firstMove);
        $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);
        $gameStatusDto->waitingListSize = $casinoTable->GetWaitingListSize();
        // communicate to all playing and waiting players
        // usePlayerHandDto specific for each user, set by communicate function
        $gameSession->CommunicateGameStarted($gameStatusDto, EntityHelper::GetPlayersForCasinoTable($casinoTable->id));

        CheatingHelper::RevealMarkedCards($gameInstance);
    }

    /**
     * Start a practice game
     * @global type $numberSeats
     * @global type $defaultTableMin
     * @global type $log
     * @global type $dateTimeFormat
     */
    public static function startPracticeGame($gameSessionId, $requestingPlayerId) {
        global $numberSeats;
        global $defaultTableMin;
        global $log;

        // can't start game if another is being played
        if (!is_null(EntityHelper::getSessionLastInstance($gameSessionId))) {
            return;
        }

        // TODO: move this logic to the saveInstanceWithDealerAndBlinds
        $statusDateTime = Context::GetStatusDT();
        $blindBetAmounts = array($defaultTableMin / 2, $defaultTableMin);
        
        // sequencing of poker game start
        $gameSession = EntityHelper::GetGameSession($gameSessionId);
        
        $gameInstance = $gameSession->InitNewPracticeInstance();
        $playerStatuses = $gameInstance->ResetActivePlayers(true);

        $gameInstance->InitInstanceWithDealerAndBlinds($blindBetAmounts, $playerStatuses);
        //$log->Debug(__FUNCTION__ . " - number cards: " . count($pokerCards));
        GameInstanceCards::InitDealGameCards($gameInstance);
        $firstMove = ExpectedPokerMove::InitFirstMove($gameInstance, $defaultTableMin);

        // start populating the response
        $gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
        $updatedPlayerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);        
        $gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($updatedPlayerStatuses, true);
        $gameStatusDto->nextMoveDto = new PokerMoveDto($firstMove);
        $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, 3);
        // communicate to all playing and waiting players
        // usePlayerHandDto specific for each user, set by communicate function
        $gameSession->CommunicateGameStarted($gameStatusDto, $statusDateTime);
    }

    /**
     * TODO: validate requesting player with cookie with active sessions. 
     * Validation means retrieving the last action and getting the correct value 
     * for now, trust the browser sends the correct data validate also that the 
     * user making the action had the turn, there should be no concurrency issues.
     * @param PlayerAction 
     * @param moveId move known and already validated.
     */
    public static function MakePokerMove($playerAction, $validateMove) {

        $gameInstance = EntityHelper::GetGameInstance($playerAction->gameInstanceId);
        if ($gameInstance->IsGameEnded()) {
            throw new Exception("Game is ended");
        }

        if (!is_null($validateMove)) {
            $playerAction->ValidateMove();
        }
        // updates PlayerStatus
        $playerInstanceStatus = $gameInstance->ApplyPlayerAction($playerAction);

        $gameStatusDto = new GameStatusDto();

        // current play may have triggered game end
        if ($gameInstance->IsGameEnded()) {
            $gameInstance->FindWinner();
            $gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
            $playerHandsDto = PlayerHandDto::mapPlayerHands($gameInstance->playerHands);
            $gameStatusDto->playerHandsDto = $playerHandsDto;
        } else {
            // update the next player id and turn number
            $curTurn = $playerInstanceStatus->turnNumber;

            // update next player id on game instance
            $nextMove = ExpectedPokerMove::FindNextExpectedMoveForInstance($gameInstance, $curTurn);

            // find whether community cards need to be send (gameinstance is updated)
            // update the community cards
            $roundNumber = $gameInstance->IsRoundEnd();
            if ($roundNumber) {
                $newCards = GameInstanceCards::DealCommunityCards($roundNumber);
                if ($newCards) {
                    $gameStatusDto->newCommunityCards = $newCards;
                    $gameInstance->numberCommunityCardsShown =+ count($newCards);
                }
            }
            $gameStatusDto->nextMoveDto = new NextPokerMoveDto($nextMove);
        }

        $gameStatusDto->updatePlayerStatusDto = PlayerStatusDto::mapPlayerStatus($playerInstanceStatus);
        $gameInstance->UpdateInstanceAfterMove();

        $gameInstance->CommunicateMoveResult($gameStatusDto, 0);
    }

    public static function SkipPokerMove($pokerMove, $gameInstance) {

        // Logic --------------------------------------------------------------------------------
        if ($gameInstance->IsGameEnded()) {
            throw new Exception("Game is ended");
        }

        // updates PlayerStatus
        $playerInstanceStatus = $gameInstance->SkipTurn($pokerMove);

        if ($playerInstanceStatus->numberTimeOuts >= 3) {
            $casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameSessionId);
            $vacatedSeat = TableCoordinator::RemoveUserFromTable($casinoTable, $pokerMove->playerId);
            TableCoordinator::ReserveAndOfferSeat($casinoTable, $vacatedSeat);
        }

        // follow player status update with instance level follow-up
        $gameStatusDto = new GameStatusDto();

        // current play may have triggered game end
        if ($gameInstance->IsGameEnded()) {
            $gameInstance->FindWinner();
            $gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
            $playerHandsDto = PlayerHandDto::mapPlayerHands($gameInstance->playerHands);
            $gameStatusDto->playerHandsDto = $playerHandsDto;
        } else {
            // update the next player id and turn number
            $curTurn = $playerInstanceStatus->turnNumber;

            // update next player id on game instance
            $nextMove = ExpectedPokerMove::FindNextExpectedMoveForInstance($gameInstance, $curTurn);

            // find whether community cards need to be send (gameinstance is updated)
            // update the community cards
            $roundNumber = $gameInstance->IsRoundEnd();
            if ($roundNumber) {
                $newCards = GameInstanceCards::DealCommunityCards($roundNumber);
                if ($newCards) {
                    $gameStatusDto->newCommunityCards = $newCards;
                    $gameInstance->numberCommunityCardsShown =+ count($newCards);
                }
            }
            $gameStatusDto->nextMoveDto = new NextPokerMoveDto($nextMove);
        }

        $gameStatusDto->updatePlayerStatusDto = PlayerStatusDto::mapPlayerStatus($playerInstanceStatus);
        $gameInstance->UpdateInstanceAfterMove();

        $gameInstance->CommunicateMoveResult($gameStatusDto, 0);
    }

    public static function Cheat($gameSessionId, $playerId, $gameInstanceId, $cheatRequestDto) {

        /* --------------------------------------------------------------------- */
        Context::Init();

        // cheating items before user enters session
        switch ($cheatRequestDto->itemType) {
            case ItemType::LOAD_CARD_ON_SLEEVE:
                $cardNameList = $cheatRequestDto->cardNameList;
                CheatingHelper::AddHiddenCards($playerId, $cardNameList);
            case ItemType::SOCIAL_SPOTTER:
                CheatingHelper::StartCardMarking($playerId, $gameSessionId);
        }

        $gameInstance = EntityHelper::GetGameInstance($cheatRequestDto->gameInstanceId);
        if (is_null($gameInstance)) {
            $gameInstance = EntityHelper::getSessionLastInstance($cheatRequestDto->gameSessionId);
        }
        if (is_null($gameInstance)) {
            return null;
        }
        // convenience vars
        $gameInstanceId = $gameInstance->id;
        $gameSessionId = $gameInstance->gameSessionId;
        // Logic -----------------------------------------------------------------
        $returnDto = null;

        switch ($cheatRequestDto->itemType) {
            case ItemType::ACE_PUSHER:
                $playerCardNumber = $cheatRequestDto->playerCardNumber;
                CheatingHelper::PushRandomAce($playerId, $gameInstance, $playerCardNumber);
                break;
            case ItemType::HEART_MARKER:
                CheatingHelper::GetSuitForAllGameCards($playerId, $gameInstance, 'hearts');
                break;
            case ItemType::CLUB_MARKER:
                CheatingHelper::GetSuitForAllGameCards($playerId, $gameInstance, 'clubs');
                break;
            case ItemType::DIAMOND_MARKER:
                CheatingHelper::GetSuitForAllGameCards($playerId, $gameInstance, 'diamonds');
                break;
            case ItemType::RIVER_SHUFFLER:
                CheatingHelper::CheatLookRiverCard($playerId, $gameInstance);
                break;
            case ItemType::RIVER_SHUFFLER_USE:
                CheatingHelper::CheatSwapRiverCard($playerId, $gameInstance);
                break;
            default:
                break;
        }
        Context::Disconnect();
    }

}

?>
