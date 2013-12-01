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
	 * Social spotter - reveal all marked cards
	 * 
	 * @global log $analytics
	 * @param int $gameSessionId
	 * @param int $requestingPlayerId
	 */
	public static function StartGame($gameSessionId, $requestingPlayerId, $indexCards = null) {
		global $analytics;
		global $log;

		// validate last game was finished. Abandoned games do finish 
		// with all players timing out.
		$lastInstance = EntityHelper::getSessionLastInstance($gameSessionId);
		if ($lastInstance && $lastInstance->status !== GameStatus::ENDED) {
			$analytics->info("Game instance for session $gameSessionId requested by $requestingPlayerId but live instance $lastInstance->id");
			$log->error("Game instance for session $gameSessionId requested by $requestingPlayerId but live instance $lastInstance->id");
			return;
		} else if ($lastInstance) {
			$casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
			$activePlayers = PlayerInstance::GetPlayersWithStates($lastInstance->id, $casinoTable->id);
			$count = count($activePlayers);
			if ($count <= 1) {
				$analytics->info("Game instance for session $gameSessionId requested by "
						. "$requestingPlayerId but only $count players.");
				$log->error("Game instance for session $gameSessionId requested by "
						. "$requestingPlayerId but only $count players.");
				return;
			} else {
				$analytics->info("Game instance for session $gameSessionId requested by $requestingPlayerId");
			}
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
		$gameCards = new GameInstanceCards($gameInstance->id);
		$gameCards->InitDealGameCards($indexCards);

		$firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize);

		CheatingHelper::ApplyFaceMelter($gameInstance, ItemType::KEEP_FACE_CARDS);

		// start populating the response
		$gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
		$gameStatusDto->playerStatusDtos = PlayerInstance::GetPlayersWithStates($gameInstance->id, $casinoTable->id);
		$gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);
		$gameStatusDto->waitingListSize = $casinoTable->GetWaitingListSize();
		// communicate to all playing and waiting players. Can't use PlayerStatuses because instance only
		// usePlayerHandDto specific for each user, set by communicate function
		$gameSession->CommunicateGameStarted($gameStatusDto, EntityHelper::GetPlayersForCasinoTable($casinoTable->id));

		CheatingHelper::RevealMarkedCards($gameInstance);
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
	public static function StartPracticeGame($gameSessionId, $indexCards = null) {
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
		$gameCards = new GameInstanceCards($gameInstance->id);
		$gameCards->InitDealGameCards($indexCards);
		$firstMove = ExpectedPokerMove::InitFirstMoveConstraints($gameInstance, $tableSize, 1);

		// start populating the response
		$gameStatusDto = GameStatusDto::SetStartedGame($gameInstance);
		$gameStatusDto->playerStatusDtos = PlayerInstance::GetPlayersWithStates($gameInstance->id, null);
		$gameStatusDto->nextMoveDto = new ExpectedPokerMoveDto($firstMove);
		// communicate to user
		$gameSession->CommunicateGameStarted($gameStatusDto, $statusDateTime);
	}

	public static function EndPracticeSession($gameSessionId, $userPlayerId) {
		$gameSession = EntityHelper::GetGameSession($gameSessionId);
		$gameSession->EndSession();
		$gameInstance = EntityHelper::getSessionLastInstance($gameSessionId);
		$players = PlayerInstance::GetPlayerInstancesForGame($gameSessionId, true);
		foreach ($players as $player) {
			$player->status = PlayerStatusType::LEFT;
			$player->UpdatePlayerLeftStatus();
		}
		$gameInstance->status = GameStatus::ENDED;
		$gameInstance->UpdateInstanceEnded();
		// clean up - purge queue and reset sleeves
		$ch = Context::GetQCh();
		$q = QueueManager::GetPlayerQueue($userPlayerId, $ch);
		QueueManager::DeleteQueue($q);
	}

	/**
	 * Swap River: set end date so they are not processed by ProcessEndedItems
	 * @param type $gameInstance
	 * @param type $gameStatusDto
	 */
	private static function endGame(&$gameInstance, &$gameStatusDto) {
		$gameInstance->status = GameStatus::ENDED;
		// FindWinner updates the winning player and all player hands
		$playerStatusesBefore = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
		$count = 0;
		foreach ($playerStatusesBefore as $playerStatus) {
			if ($playerStatus->status !== PlayerStatusType::FOLDED &&
					$playerStatus->status !== PlayerStatusType::LEFT) {
				$count++;
			}
		}
		if ($count > 1) {
			$gameInstance->FindWinner();
			$gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
			$playerHandsDto = PlayerHandDto::mapPlayerHands($gameInstance->playerHands);
			$gameStatusDto->playerHandsDto = $playerHandsDto;
		}
		$playerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
		if ($count === 1) {
			foreach ($playerStatuses as $playerStatus) {
				if ($playerStatus->status === PlayerStatusType::FOLDED ||
						$playerStatus->status === PlayerStatusType::LEFT) {
					/* don't set to status if left, implied lost */
					continue;
				}
				$gameInstance->lastUpdateDateTime = Context::GetStatusDT();
				$gameInstance->winningPlayerId = $playerStatus->playerId;
				// update instance with winner info
				$gameInstance->_updateWinner();
				$gameStatusDto->winningPlayerId = $playerStatus->playerId;
				$playerStatus->status = PlayerStatusType::WON;
				$playerStatus->UpdatePlayerStatus(PlayerStatusType::WON);
			}
		}
		/* --------------------------------------------------------------------- */
		/* mark cards that are to be seen if item is enabled */
		CheatingHelper::AddVisibleCards($gameInstance);

		$gameStatusDto->playerStatusDtos = PlayerStatusDto::MapPlayerStatuses($playerStatuses);
		CheatingHelper::SetItemToInactiveForInstance($gameInstance->id, ItemType::SOCIAL_SPOTTER);
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

		$gameInstance = EntityHelper::GetGameInstance($playerAction->gameInstanceId);
		if ($gameInstance->IsGameEnded()) {
			$log->error("User attempted move but game is ended: " . json_encode($playerAction));
			return;
		}

		// craft response DTO
		$gameStatusDto = GameStatusDto::InitForInstance($gameInstance);

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
			$gameInstance->status = GameStatus::IN_PROGRESS;
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
				$gameCards = new GameInstanceCards($gameInstance->id, $gameInstance->numberCommunityCardsShown);
				$newCards = $gameCards->DealCommunityCards($roundNumber);
			}
			if (isset($newCards)) {
				$gameStatusDto->newCommunityCards = $newCards;
				$gameInstance->numberCommunityCardsShown = $gameInstance->numberCommunityCardsShown + count($newCards);
			}
		}
		// only send the player who changed status
		$gameStatusDto->turnPlayerStatusDto = PlayerStatusDto::mapPlayerStatus($playerInstanceStatus);
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
		$playerId = $pokerMove->playerId;
		$playerInstanceStatus = $gameInstance->SkipTurn($pokerMove);
		/* skipping may have set the player status to left
		if ($playerInstanceStatus->status == PlayerStatusType::LEFT) {
			$pokerMove = self::adjustNextForLeftUser($gameInstance, $pokerMove);
			$gameInstance->lastInstancePlayNumber +=1;
		} */
		if ($playerInstanceStatus->numberTimeOuts >= 3 && $playerInstanceStatus->status !== PlayerStatusType::LEFT) {
			// || $playerInstanceStatus->status == PlayerStatusType::LEFT) {
			$casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameSessionId);
			// note that pokerMove is null; this avoids infinite loops between SkipPokerMove and RemoveUserFromTable
			$vacatedSeat = TableCoordinator::RemoveUserFromTable($casinoTable, $playerId);
			TableCoordinator::ReserveAndOfferSeat($casinoTable, $vacatedSeat);
			$playerInstanceStatus->status = PlayerStatusType::LEFT;
		}

		// follow player status update with instance level follow-up
		$gameStatusDto = GameStatusDto::InitForInstance($gameInstance);

		// current play may have triggered game end
		if ($gameInstance->IsGameEnded()) {
			self::endGame($gameInstance, $gameStatusDto);
		} else {
			// update the next player id and turn number
			$gameInstance->status = GameStatus::IN_PROGRESS;
			$curTurn = $playerInstanceStatus->turnNumber;

			// update next player id on game instance if not done so already
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
				$gameCards = new GameInstanceCards($gameInstance->id, $gameInstance->numberCommunityCardsShown);
				$newCards = $gameCards->DealCommunityCards($roundNumber);
			}
			if (isset($newCards)) {
				$gameStatusDto->newCommunityCards = $newCards;
				$gameInstance->numberCommunityCardsShown = $gameInstance->numberCommunityCardsShown + count($newCards);
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
	public static function CheckGameEnd($gameInstanceId) {
		// Logic --------------------------------------------------------------------------------
		$gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
		if ($gameInstance === null || $gameInstance->IsGameEnded()) {
			return;
		}
		// follow player status update with instance level follow-up
		$gameStatusDto = GameStatusDto::InitForInstance($gameInstance);
		self::endGame($gameInstance, $gameStatusDto);

		$gameStatusDto->gameStatus = $gameInstance->status;
		$gameStatusDto->currentPotSize = $gameInstance->currentPotSize;

		$gameInstance->CommunicateMoveResult($gameStatusDto);
	}

	public static function Cheat($gameSessionId, $playerId, $cheatRequestDto, $gameInstanceId = null) {

		$cheatingItem = new CheatingItem($playerId, $gameSessionId, $cheatRequestDto->itemType);
		// TODO: this may not work if $cheatRequestDto->playerCardNumber not set
		$cheatingItem->playerCardNumber = isset($cheatRequestDto->playerCardNumber) ? $cheatRequestDto->playerCardNumber : null;
		// cheating items that last across game instances (game instance id not given)
		if ($cheatRequestDto->itemType === ItemType::SOCIAL_SPOTTER) {
			$messagesOut = $cheatingItem->ApplySocialSpotter();
			CheatingHelper::_communicateCheatingOutcome($playerId, $messagesOut, $gameSessionId);
			return;
		}

		// an active game instance is required for the next items
		$gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
		if (is_null($gameInstance)) {
			$gameInstance = EntityHelper::getSessionLastInstance($gameSessionId);
		}
		if (is_null($gameInstance)) {
			// no message out, possible fraud
			return;
		}
		$gameSessionId = $gameInstance->gameSessionId;

		$messagesOut = null;
		// these are cheating items that require an active game instance 
		// or are available only while actively playing a game (even if thye
		// do not expire when the game ends.
		switch ($cheatRequestDto->itemType) {
			case ItemType::ACE_PUSHER:
				$messagesOut = $cheatingItem->ApplyAcePusher($gameInstance);
				break;
			case ItemType::HEART_MARKER:
			case ItemType::CLUB_MARKER:
			case ItemType::DIAMOND_MARKER:
				$messagesOut = $cheatingItem->ApplySuitMarker($gameInstance);
				break;
			case ItemType::USE_CARD_ON_SLEEVE:
				$ch = new CheatingHidingItem($cheatingItem);
				$ch->playerCardNumber = $cheatRequestDto->playerCardNumber;
				$hiddenCardNumber = $cheatRequestDto->hiddenCardNumber;
				$prevItemType = ItemType::LOAD_CARD_ON_SLEEVE;
				$messagesOut = $ch->CheatUseHidden($gameInstance, $hiddenCardNumber, $prevItemType);
				break;
			case ItemType::RIVER_SHUFFLER:
				$messagesOut = $cheatingItem->CheatLookRiverCard($gameInstance);
				break;
			case ItemType::RIVER_SHUFFLER_USE:
				$messagesOut = $cheatingItem->CheatSwapRiverCard($gameInstance);
				break;
			case ItemType::POKER_PEEKER:
				$ch = new CheatingAnotherItem($cheatingItem, $cheatRequestDto->otherPlayerId);
				$ch->playerCardNumber = $cheatRequestDto->playerCardNumber;
				$messagesOut = $ch->ApplyPokerPeeker($gameInstance);
				break;
			case ItemType::TUCKER_TABLE_SLIDE_OUT:
				$ch = new CheatingHidingItem($cheatingItem);
				$messagesOut = $ch->TuckCardOut($cheatRequestDto->hiddenCardNumber);
				break;
			case ItemType::TUCKER_TABLE_SLIDE_UNDER:
				$ch = new CheatingHidingItem($cheatingItem);
				$messagesOut = $ch->TuckCardUnder($cheatRequestDto->cardNameList);
				break;
			case ItemType::TUCKER_TABLE_EXCHANGE:
				$ch = new CheatingHidingItem($cheatingItem);
				$ch->playerCardNumber = $cheatRequestDto->playerCardNumber;
				$hiddenCardNumber = $cheatRequestDto->hiddenCardNumber;
				$prevItemType = ItemType::TUCKER_TABLE_SLIDE_UNDER;
				$messagesOut = $ch->CheatUseHidden($gameInstance, $hiddenCardNumber, $prevItemType);
				break;
			case ItemType::SNAKE_OIL_MARKER:
				$messagesOut = $cheatingItem->ApplyOilMarker($gameInstanceId);
				break;
			case ItemType::ANTI_OIL_MARKER:
				$ch = new CheatingAnotherItem($cheatingItem, $cheatRequestDto->otherPlayerId);
				$messagesOut = $ch->ApplyAntiOilMarker($gameInstance);
				break;
			case ItemType::KEEP_FACE_CARDS:
				$messagesOut = $cheatingItem->RequestFaceMelter();
				break;
			default:
				break;
		}
		if (count($messagesOut) > 0) {
			CheatingHelper::_communicateCheatingOutcome($playerId, $messagesOut, $gameSessionId);
		}
	}

}

?>
