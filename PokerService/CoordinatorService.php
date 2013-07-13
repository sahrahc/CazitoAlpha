<?php

/**
 * The coordinator service is a background service that checks for
 * timed events, including servicing the queue
 */
include_once(dirname(__FILE__) . '/Config.php');

/*
 * Script run by the scheduled job.
 *
 */
/* * ***************************************************************************************************** */

/**
 * Find if message is stale by looking at game session, game instance
 * This function updates the context with game session and instance 
 * information.
 * @param type $params
 */
function isValid($playerId, $gameSessionId, $gameInstanceId) {
    if (!is_null($gameInstanceId)) {
        $playerStatus = EntityHelper::getPlayerInstance($gameInstanceId, $playerId);
        if (is_null($playerStatus)) {
            throw new Exception("Invalid game instance $gameInstanceId for player $playerId");
        }
        return true;
    }
    $gameSession = EntityHelper::GetGameSession($gameSessionId);
    if ($gameSession->isPractice) {
        $playerSession = EntityHelper::getPlayerInstance($gameSessionId, $playerId, true);
        // there must have been an instance if practice session
        if (is_null($playerSession)) {
            throw new Exception("Unknown player $playerId on practice game session $gameSessionId");
        }
    } else if (!CasinoTable::IsPlayerOnTableSession($gameSessionId, $playerId)) {
        throw new Exception("Unknown player $playerId on casino table game session $gameSessionId");
    }
    return true;
}

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
    if (is_null($expiredMoves)) {
        return;
    }
    foreach ($expiredMoves as $move) {
        $gameInstanceId = $move->gameInstanceId;
        $gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
        $player = EntityHelper::getPlayer($move->playerId);
        // FIXME : should be logged
        // game does not exist or is ended, then remove obsolete expected poker move
        if (!is_null($gameInstance) && !is_null($gameInstance->winningPlayerId)) {
            $log->warn(__FUNCTION__ . " - Expected move found for non-existing or ended game instance id " . $gameInstanceId . "<br />");
            $move->Delete();
            // no need to clean up queues, clean up job does it.
            continue;
        }
        if ($player->isVirtual) {
            $practiceSession = EntityHelper::GetGameSession($gameInstance->gameSessionId);
            $playerAction = $practiceSession->generateRandomAction($move);
            $log->warn(__FUNCTION__ . " - Generated PlayerAction is " . json_encode($playerAction) . "<br />");
            PokerCoordinator::MakePokerMove($playerAction, false);
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
    global $waitSessionExpiration;

    $conn = QueueManager::GetConnection();
    $ch = QueueManager::GetChannel($conn);
    $playerExpiration = new DateTime();
    $playerExpiration->sub(new DateInterval($playerTimeOut)); // 20 minutes
    $instanceExpiration = new DateTime();
    $instanceExpiration->sub(new DateInterval($instanceTimeOut)); // 20 minutes
    $unusedSessionExpiration = new DateTime();
    $unusedSessionExpiration->sub(new DateInterval($waitSessionExpiration));

    // get list of all abandoned player instances
    $abandonedPlayerStates = PlayerInstance::DeleteExpiredPlayerInstances($playerExpiration);

    if (!is_null($abandonedPlayerStates)) {
        foreach ($abandonedPlayerStates as $player) {
            $q = QueueManager::GetPlayerQueue($player->id, $ch);
            QueueManager::DeleteQueue($q);
        }
    }

    // delete all abandoned game instances including the poker moves
    GameInstance::DeleteExpiredInstances($instanceExpiration);

    // delete game sessions and update casino; queues remain
    // FIXME: 
    $expiredGameSessionIds = CasinoTable::DeleteExpiredGameSessions($instanceExpiration, $unusedSessionExpiration);
    if (!is_null($expiredGameSessionIds)) {
        foreach ($expiredGameSessionIds as $gameSessionId) {
            $q = QueueManager::GetGameSessionQueue($gameSessionId, $ch);
            QueueManager::DeleteQueue($q);
        }
    }
}

/**
 * Traverses the list of active items and sets status flags such as isActive and isAvailable
 * Any items that are changed generate an queued event for the change only
 * Asynchronous - NEEDS TO COMMUNICATE
 */
function ProcessEndedCheatingItems() {

    $endedItems = PlayerActiveItem::GetEndedItems();
    if (is_null($endedItems)) {
        return;
    }
    foreach ($endedItems as $item) {
        if ($item->playerSessionId == $item->gameSessionId && $item->playerStatus != PlayerStatusType::LEFT) {
            $info = "$item->itemType has ended. You may use this again after $item->lockEndDateTime";
            $messagesOut = array(new CheatOutcomeDto(CheatDtoType::ItemEnd, $info));
            CheatingHelper::_communicateCheatingOutcome($item->playerId, $messagesOut);
        }
        $item->SetItemToInactive();
    }
}

/* if locked end reached: delete record          */

function ProcessUnlockedCheatingItems() {
    $leftStatus = PlayerStatusType::LEFT;
    $unlockedItems = PlayerActiveItem::GetLockEndedItems();
    if (is_null($unlockedItems)) {
        return;
    }
    foreach ($unlockedItems as $item) {
        if ($item->playerSessionId == $item->gameSessionId && $item->playerStatus != $leftStatus) {
            $info = "$item->itemType is available again.";
            $messagesOut = array(new CheatOutcomeDto(CheatDtoType::ItemUnlock, $info));
            CheatingHelper::_communicateCheatingOutcome($item->playerId, $messagesOut);
        }
        $item->Delete();
    }
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
    Context::Init();
    // get all the active tables
    $activeSessionIds = EntityHelper::GetActiveGameSessionIds();
    if (is_null($activeSessionIds)) {
        echo "No active game session founds.<br/>";
        Context::Disconnect();
        exit;
    }
    foreach ($activeSessionIds as $activeSessionId) {
        // add casino, session
        // get the queue and all messages for the table
        $qt = QueueManager::GetGameSessionQueue($activeSessionId, Context::GetQCh());
        while ($msg = $qt->get(AMQP_AUTOACK)) {
            $decodedMsg = json_decode($msg->getBody(), true);
            $eventType = $decodedMsg["eventType"];
            $gameSessionId = $decodedMsg["gameSessionId"];
            $requestingPlayerId = $decodedMsg["requestingPlayerId"];
            // the following is optional so it may be null
            $gameInstanceId = null;
            if (isset($decodedMsg["gameInstanceId"])) {
                $gameInstanceId = $decodedMsg["gameInstanceId"];
            }

            // request parameter may be a json encoded named-array or a 
            // scalar.
            $reqParam = null;
            if (isset($decodedMsg["eventData"])) {
                $reqParam = $decodedMsg["eventData"];
            }
            // ignore stale messages
            if (!isValid($requestingPlayerId, $gameSessionId, $gameInstanceId)) {
                // FIXME: log invalid message
                continue;
            }

            switch ($eventType) {
                case ActionType::StartGame:
                    PokerCoordinator::StartGame($gameSessionId, $requestingPlayerId);
                    break;
                case ActionType::StartPracticeGame:
                    PokerCoordinator::StartPracticeGame($gameSessionId);
                    break;
                case ActionType::MakePokerMove:
                    $requestDto = $reqParam;
                    $playerAction = new PlayerAction(
                            $gameInstanceId, $requestingPlayerId, $requestDto['pokerActionType'], $requestDto['actionTime'], $requestDto['actionValue']);
                    PokerCoordinator::makePokerMove($playerAction, true);
                    break;
                case ActionType::TakeSeat:
                    $seatNumber = $reqParam;
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
                    $cheatRequestDto = $reqParam;
                    PokerCoordinator::Cheat($gameSessionId, $requestingPlayerId, $cheatRequestDto, $gameInstanceId);
                    break;
            }
        }
        Context::Disconnect();
    }
}

/* * ***************************************************************************************************** */
/*
  checkExpiration();
  cleanUp();
 */
?>
