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
function isValid($playerId, $gameSessionId, $gameInstanceId, $actionType) {
	// game instance validation by the cheating item
	if (!is_null($gameInstanceId) && $actionType != ActionType::Cheat && $actionType != ActionType::StartGame) {
		// what if new user on 
		$playerStatus = PlayerInstance::GetPlayerInstance($gameInstanceId, $playerId);
		if (is_null($playerStatus)) {
			throw new Exception("Invalid game instance $gameInstanceId for player $playerId");
		}
		return true;
	}
	$gameSession = GameSession::GetGameSession($gameSessionId);
	if ($gameSession->isPractice) {
		$playerSession = PlayerInstance::GetPlayerInstance($gameSessionId, $playerId, true);
		// there must have been an instance if practice session
		if (is_null($playerSession)) {
			throw new Exception("Unknown player $playerId on practice game session $gameSessionId");
		}
	} else if (!SeatingHelper::IsPlayerOnTableSession($gameSessionId, $playerId)) {
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
		$gameInstance = GameInstance::GetGameInstance($gameInstanceId);
		$gameSession = GameSession::GetGameSession($gameInstance->gameSessionId);
		$casinoTable = CasinoTable::GetCasinoTableForSession($gameSession->id);
		$player = Player::GetPlayer($move->playerId);
		// FIXME : should be logged
		// game does not exist or is ended, then remove obsolete expected poker move
		if (is_null($gameInstance) || is_null($gameSession) ||
				(!$gameSession->isPractice && $casinoTable->currentGameSessionId != $gameSession->id)) {
			$log->warn(__FUNCTION__ . " - Expected move found for non-existing or ended game instance id " . $gameInstanceId . "<br />");
			$move->Delete();
			// no need to clean up queues, clean up job does it.
			//continue;
		} else if ($player->isVirtual) {
			$practiceSession = GameSession::GetGameSession($gameInstance->gameSessionId);
			$playerAction = $practiceSession->generateRandomAction($move);
			//$log->debug(__FUNCTION__ . " - Generated PlayerAction is " . json_encode($playerAction) . "<br />");
			PokerCoordinator::MakePokerMove($playerAction, false);
		} else {
			//$log->debug(__FUNCTION__ . " - Expired move for Game instance " . json_encode($gameInstance));
			//$playerAction = new PlayerAction($gameInstanceId, $playerId, null, $move->expirationDate, null);
			PokerCoordinator::SkipPokerMove($move, $gameInstance);
		}
	}
	Context::Disconnect();
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
	//global $playerTimeOut;
	global $instanceTimeOut;
	global $sessionExpiration;
	global $dateTimeFormat;

	Context::Init();
/*	  $playerExpiration = Context::GetStatusDT();
	  $playerExpiration->sub(new DateInterval($playerTimeOut)); // 20 minutes */
	$instanceExpiration = Context::GetStatusDT();
	$instanceExpiration->sub(new DateInterval($instanceTimeOut)); // 20 minutes
	$instanceExpString = $instanceExpiration->format($dateTimeFormat);
	$unusedSessionExpiration = Context::GetStatusDT();
	$unusedSessionExpiration->sub(new DateInterval($sessionExpiration));

	GameInstance::DeleteExpiredInstances($instanceExpString);

	// delete game sessions and update casino; queues remain
	// FIXME: 
	GameSession::DeleteExpiredGameSessions($unusedSessionExpiration);
	Context::Disconnect();
}

/**
 * Traverses the list of ended items and notifies user
 * Any items that are changed generate an queued event for the change only
 * Asynchronous - NEEDS TO COMMUNICATE
 */
function ProcessEndedCheatingItems() {
	global $dateTimeFormat;

	Context::Init();

	$endedItems = CheatingHelper::GetEndedItems();
	if (is_null($endedItems)) {
		return;
	}
	foreach ($endedItems as $item) {
		if ($item->playerSessionId != $item->gameSessionId || $item->playerStatus === PlayerStatusType::LEFT) {
			continue;
		}
		$info = new CheatInfoDto("$item->itemType has ended. ");
		if ($item->itemType === ItemType::SNAKE_OIL_MARKER_COUNTERED) {
			$info = new CheatInfoDto(ItemType::SNAKE_OIL_MARKER . " has ended. ");
		}
		if ($item->lockEndDateTime !== null) {
			$lockEndDT = $item->lockEndDateTime->format($dateTimeFormat);
			$info->info = $info->info . "You may use this again after $lockEndDT.";
		}
		$item->UpdateIsNotified();
		$info->isDisabled = 0;
		$messagesOut = array(new CheatOutcomeDto($item->itemType, CheatDtoType::ItemEnd, $info));
		CheatingHelper::_communicateCheatingOutcome($item->playerId, $messagesOut, $item->gameSessionId);

		/* special processing */
		if ($item->itemType === ItemType::SOCIAL_SPOTTER ||
				$item->itemType === ItemType::SNAKE_OIL_MARKER_COUNTERED
		) {
			CheatingHelper::DeleteVisibleCards($item->playerId, $item->itemType, $item->gameSessionId);
		}
		//$item->SetItemToInactive();
	}
	Context::Disconnect();
}

/* if locked end reached: delete record          */

function ProcessUnlockedCheatingItems() {
	Context::Init();
	$leftStatus = PlayerStatusType::LEFT;
	$unlockedItems = CheatingHelper::GetLockEndedItems();
	if (is_null($unlockedItems)) {
		return;
	}
	foreach ($unlockedItems as $item) {
		if ($item->playerSessionId !== $item->gameSessionId || $item->playerStatus === $leftStatus) {
			$item->Delete();
			continue;
		}
		$info = new CheatInfoDto("$item->itemType is available again.");
		if ($item->itemType === ItemType::SNAKE_OIL_MARKER_COUNTERED) {
			$info = new CheatInfoDto(ItemType::SNAKE_OIL_MARKER . " is available again.");
		}
		$info->isDisabled = 0;
		$messagesOut = array(new CheatOutcomeDto($item->itemType, CheatDtoType::ItemUnlock, $info));
		CheatingHelper::_communicateCheatingOutcome($item->playerId, $messagesOut, $item->gameSessionId);
		$item->Delete();
	}
	Context::Disconnect();
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
	$activeSessionIds = GameSession::GetActiveGameSessionIds();
	if (is_null($activeSessionIds)) {
		echo "No active game session founds.<br/>";
		Context::Disconnect();
		return;
	}
	$deleteList = array();
	foreach ($activeSessionIds as $activeSessionId) {
		// add casino, session
		// get the queue and all messages for the table
		$qt = QueueManager::GetGameSessionQueue($activeSessionId, Context::GetQCh());
		while ($msg = $qt->get(AMQP_AUTOACK)) {
			$decodedMsg = json_decode($msg->getBody(), true);
			$eventType = $decodedMsg["eventType"];
			$gameSessionId = (int) $decodedMsg["gameSessionId"];
			$requestingPlayerId = (int) $decodedMsg["requestingPlayerId"];
			// the following is optional so it may be null
			$gameInstanceId = null;
			if (isset($decodedMsg["gameInstanceId"]) && $decodedMsg["gameInstanceId"] !== "") {
				$gameInstanceId = (int) $decodedMsg["gameInstanceId"];
			}

			// request parameter may be a json encoded named-array or a 
			// scalar.
			$reqParam = null;
			if (isset($decodedMsg['eventData'])) {
				$reqParam = $decodedMsg["eventData"];
			}
			// ignore stale messages
			if (!isValid($requestingPlayerId, $gameSessionId, $gameInstanceId, $eventType)) {
				// FIXME: log invalid message
				return;
			}

			switch ($eventType) {
				case ActionType::StartGame:
					PokerCoordinator::StartGame($gameSessionId, $requestingPlayerId, $reqParam);
					break;
				case ActionType::StartPracticeGame:
					PokerCoordinator::StartPracticeGame($gameSessionId, $reqParam);
					break;
				case ActionType::MakePokerMove:
					$requestDto = $reqParam;
					$playerAction = new PlayerAction(
							$gameInstanceId, $requestingPlayerId, $requestDto['pokerActionType'], 
							$requestDto['actionTime'], $requestDto['actionValue']);
					PokerCoordinator::makePokerMove($playerAction, true);
					break;
				case ActionType::TakeSeat:
					$seatNumber = $reqParam;
					TableCoordinator::SeatUserOnTable($gameSessionId, $seatNumber, $requestingPlayerId);
					break;
				case ActionType::LeaveTable:
					$casinoTable = CasinoTable::GetCasinoTableForSession($gameSessionId);
					if ($casinoTable !== null) {
						$vacatedSeat = TableCoordinator::RemoveUserFromTable($casinoTable, $requestingPlayerId);
						PokerCoordinator::CheckGameEnd($gameInstanceId);
						if (!is_null($vacatedSeat)) {
							TableCoordinator::ReserveAndOfferSeat($casinoTable, $vacatedSeat);
						}
					}
					$_SESSION['casinoTableId'] = null;

					break;
				case ActionType::EndPractice:
					PokerCoordinator::EndPracticeSession($gameSessionId, $requestingPlayerId);
					array_push($deleteList, $activeSessionId);
					break;
				case ActionType::Cheat:
					$cheatRequestDto = new CheatRequestDto();
					$cheatRequestDto->itemType = $reqParam['itemType'];
					if (isset($reqParam['playerCardNumber'])) {
						$cheatRequestDto->playerCardNumber = (int) $reqParam['playerCardNumber'];
					}
					if (isset($reqParam['hiddenCardNumber'])) {
						$cheatRequestDto->hiddenCardNumber = (int) $reqParam['hiddenCardNumber'];
					}
					if (isset($reqParam['cardNameList'])) {
						$cheatRequestDto->cardNameList = $reqParam['cardNameList'];
					}
					if (isset($reqParam['cardName'])) {
						$cheatRequestDto->cardName = $reqParam['cardName'];
					}
					if (isset($reqParam['otherPlayerId'])) {
						$cheatRequestDto->otherPlayerId = (int) $reqParam['otherPlayerId'];
					}
					PokerCoordinator::Cheat($gameSessionId, $requestingPlayerId, $cheatRequestDto, $gameInstanceId);
					break;
			}
		}
	}
	if (count($deleteList) > 0) {
	foreach ($deleteList as $deleteId) {
		$qt = QueueManager::GetGameSessionQueue($deleteId, Context::GetQCh());
		QueueManager::DeleteQueue($qt);
	}
	}
	Context::Disconnect();
}

/* * ***************************************************************************************************** */
/*
  checkExpiration();
  cleanUp();
 */
?>
