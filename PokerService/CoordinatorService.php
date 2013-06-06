<?php

/**
 * The coordinator service is a background service that checks for
 * timed events, including servicing the queue
 */
// Include Libraries
include_once(dirname(__FILE__) . '/../../libraries/log4php/Logger.php');
include_once(dirname(__FILE__) . '/../../libraries/helper/DataHelper.php');

// TODO: too much business logic here.
// Include Application Scripts
require_once(dirname(__FILE__) . '/Config.php');
require_once(dirname(__FILE__) . '/Components/QueueManager.php');
require_once(dirname(__FILE__) . '/Components/CheatingHelper.php');
include_once(dirname(__FILE__) . '/DomainHelper/AllInclude.php');
include_once(dirname(__FILE__) . '/DomainModel/AllInclude.php');
include_once(dirname(__FILE__) . '/Dto/AllInclude.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/log4php.xml');
$log = Logger::getLogger("CoordinatorService");
/*
 * Script run by the scheduled job.
 *
 */
/* * ***************************************************************************************************** */

/**
 * No need for extra cleanup
 * @global type $dateTimeFormat
 * @global type $log
 */
function ProcessExpiredPokerMoves() {
    global $dateTimeFormat;
    global $log;

    Context::Init();

    $statusDateTime = date($dateTimeFormat);

    $expiredMoves = ExpectedPokerMove::GetExpiredPokerMoves($statusDateTime);
    foreach ($expiredMoves as $move) {
        $gameInstanceId = $move->gameInstanceId;
        $gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
        $gameSession = EntityHelper::GetGameSession($gameInstance->gameSessionId);
        // FIXME : should be logged
        // game does not exist or is ended, then remove obsolete expected poker move
        if (is_null($gameInstance) || !is_null($gameInstance->winningPlayerId)) {
            $log->warn(__FUNCTION__ . " - Expected move found for non-existing or ended game instance id " . $gameInstanceId . "<br />");
            $move->Delete();
            // no need to clean up queues, clean up job does it.
            continue;
        }
        if ($gameSession->isPractice == 1) {
            $practiceSession = EntityHelper::GetGameSession($gameInstance->gameSessionId);
            $playerAction = $practiceSession->generateRandomAction($move);
            $log->warn(__FUNCTION__ . " - Generated PlayerAction is " . json_encode($playerAction) . "<br />");
            PokerCoordinator::MakePokerMove($playerAction);
        } else {
            $log->warn(__FUNCTION__ . " - Expired move for Game instance " . json_encode($gameInstance));
            //$playerAction = new PlayerAction($gameInstanceId, $playerId, null, $move->expirationDate, null);
            PokerCoordinator::SkipPokerMove($move, $gameInstance);
        }
    }
}

/*
 * TODO: implement by finding all abandoned tables, sessions and instance 
 * (no activity in predefined time frame - lastUpdateDateTime)
 * 1) abandoned player instances - flush and remove from memory, reset player
 *      remove queue as when user leaves
 * 2) abandoned instances - flush and remove from memory
 * 3) abandoned sessions - flush and remove from memory, reset casino table  
 *      don't remove queue for casino table
 * 
 */
function CleanUpAbandonedPlays() {
    global $playerTimeOut;
    global $instanceTimeOut;
    global $log;

    $conn = QueueManager::GetQueueConnection();
    $ch = QueueManager::GetChannel($conn);
    $playerExpiration = new DateTime();
    $playerExpiration->sub(new DateInterval($playerTimeOut)); // 20 minutes
    $instanceExpiration = new DateTime();
    $instanceExpiration->sub(new DateInterval($instanceTimeOut)); // 20 minutes
    
    // get list of all abandoned player instances
    $abandonedPlayerStates = PlayerInstance::DeleteExpiredPlayerInstances($playerExpiration);
    foreach ($abandonedPlayerStates as $player) {
        $q = QueueManager::GetPlayerQueue($player->id, $ch);
        QueueManager::PurgeQueue($q);
    }
    
    // delete all abandoned game instances including the poker moves
    GameInstance::DeleteExpiredInstances($instanceExpiration);
    
    // delete game sessions and update casino; queues remain
    $inactiveCasinoTables = CasinoTable::DeleteExpiredGameSessions($instanceExpiration);
    foreach ($inactiveCasinoTables as $casinoTable) {
        $q = QueueManager::GetTableQueue($casinoTable->id, $ch);
        QueueManager::PurgeQueue($q);
    }
}

/**
 * Checks time and sets items to expired or ended if needed.
 */
function ProcessTimedCheatingItems() {

    CheatingHelper::UpdateEndedItems();
    CheatingHelper::UpdateUnlockedItems();
}

/* * ***************************************************************************************************** */

/**
 * Every casino table has a queue to receive actions from players seating at the table.
 * Calls the appropriate coordinator service
 *  1) startGame
 *  2) makePokerMove
 *  3) takeSeat (if on waiting list)
 *  4) leaveTable
 * The following are coordinator services that are event-driven and not called
 * from this service: endPokerRound, addUser
 */
function ConsumeTableQueue() {
    // get all the active tables
    $activeSessionIds = GameSession::GetActiveGameSessionIds();
    foreach ($activeSessionIds as $activeSessionId) {
        Context::Init();
        // add casino, session
        // get the queue and all messages for the table
        $qt = QueueManager::GetGameSessionQueue($activeSessionId);
        while ($msg = $qt->get(AMQP_AUTOACK)) {
            $decodedMsg = json_decode($msg, true);
            $eventType = $decodedMsg["eventType"];
            $decodedPar = $decodedMsg["eventData"];
            $gameSessionId = $decodedPar["gameSessionId"];
            $requestingPlayerId = $decodedPar["requestingPlayerId"];
            // the following is optional so it may be null
            $gameInstanceId = $decodedPar["gameInstanceId"];
            // ignore stale messages
            if (isValid($requestingPlayerId, $gameSessionId, $gameInstanceId)) {
                // FIXME: log invalid message
                continue;
            }

            switch ($eventType) {
                case ActionType::StartGame:
                    PokerCoordinator::startGame($gameSessionId, $requestingPlayerId);
                    break;
                case ActionType::StartPracticeGame:
                    PokerCoordinator::startPracticeGame($gameSessionId, $requestingPlayerId);
                    break;
                case ActionType::MakePokerMove:
                    $playerAction = new PlayerAction(
                            $gameInstanceId, $requestingPlayerId, $decodedPar['pokerActionType'], $decodedPar['actionTime'], $decodedPar['actionValue']);
                    PokerCoordinator::makePokerMove($playerAction);
                    break;
                case ActionType::TakeSeat:
                    $seatNumber = $decodedPar["seatNumber"];
                    TableCoordinator::SeatUserOnTable($gameSessionId, $seatNumber, $requestingPlayerId);
                    break;
                case ActionType::LeaveTable:
                    $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
                    if (!is_null($casinoTable)) {
                        $vacatedSeat = TableCoordinator::RemoveUserFromTable($casinoTable, $requestingPlayerId);
                        if (!is_null($vacatedSeat)) {
                            TableCoordinator::ReserveAndOfferSeat($casinoTable, $vacatedSeat);
                        }
                    }
                    break;
                case ActionType::Cheat:
                    $cheatRequestDto = json_decode($decodedPar);
                    PokerCoordinator::Cheat($gameSessionId, $requestingPlayerId, $gameInstanceId, $cheatRequestDto);
                    break;
            }
        }
        Context::Disconnect();
    }

    /**
     * Find if message is stale by looking at game session, game instance
     * This function updates the context with game session and instance 
     * information.
     * @param type $params
     */
    function isValid($playerId, $gameSessionId, $gameInstanceId) {
        if ($gameInstanceId) {
            $playerStatus = EntityHelper::getPlayerInstance($gameInstanceId, $playerId);
            if (is_null($playerStatus)) {
                throw new Exception("Invalid game instance $gameInstanceId for player $playerId");
            }
            if ($playerStatus && $playerStatus->gameSessionId != $gameSessionId) {
                throw new Exception("Game Session id $gameSessionId invalid for instance $gameInstanceId and player $playerId");
            }
            return true;
        }
        $playerStatus = EntityHelper::getPlayerInstance($gameInstanceId, $playerId, true);
        if (is_null($playerStatus)) {
            throw new Exception("Unknown player $playerId on game session $gameSessionId");
        }
    }

}

/* * ***************************************************************************************************** */
/*
  checkExpiration();
  cleanUp();
 */
?>
