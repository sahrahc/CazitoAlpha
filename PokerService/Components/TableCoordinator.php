<?php

/*
 * TableCoordinator: business logic for actions that require multiple business
 * objects to act in sequence. This logic does not fit the object model 
 * paradigm.
 * Every action that requires communication to all players fit this category.
 */
/* * ************************************************************************************* */

class TableCoordinator {

	private static $log = null;

	public static function log() {
		if (is_null(self::$log))
			self::$log = Logger::getLogger(__CLASS__);
		return self::$log;
	}

	/**
	 * Create a table. The creator is not automatically joined.
	 * @global type $numberPlayers
	 * @param type $playerId
	 * @param type $tableName
	 * @param type $betSize
	 * @return \CasinoTableDto
	 */
	public static function SetupTable($playerId, $tableName, $tableCode, $betSize) {
		global $numberSeats;
		$tableName = strip_tags($tableName);
		// check if table exists
		$table = EntityHelper::getCasinoTableByCode($tableCode);
		if (!is_null($table)) {
			throw new Exception("Table $tableName already exists");
		}
		$casinoTable = EntityHelper::createCasinoTable($tableName, $tableCode, $betSize, $numberSeats, $playerId);
		$dto = new CasinoTableDto($casinoTable);
		$dto->numberCurrentPlayers = 0;
		$dto->numberWaitingPlayers = 0;
		return $dto;
	}

	/**
	 * Finds whether a table exists and returns table info.
	 * @param type $playerId
	 * @param type $tableName
	 * @return \CasinoTableDto
	 */
	public static function GetTable($playerId, $tableCode) {
		$tableCode = strip_tags($tableCode);
		$casinoTable = EntityHelper::getCasinoTableByCode($tableCode);
		if (is_null($casinoTable)) {
			return null;
		}
		// if game session stale, then don't report any users joined or waiting
		$gameSession = new GameSession($casinoTable->currentGameSessionId, $playerId);
		if ($casinoTable->IsSessionStale()) {
			// note that CasinoTable is updated
			$gameSession = $casinoTable->ResetGameSession($playerId);
			/* need to communicate new game status */
			$gameSession->communicateResetStatus($casinoTable);
		}
		$currentPlayers = 0;
		$waitingPlayers = 0;

		// TODO: need to check that the user is authorized somehow
		$players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
		// count current and waiting players
		if (!is_null($players)) {
			foreach ($players as $player) {
				if (!is_null($player->currentSeatNumber)) {
					$currentPlayers++;
				} else {
					$waitingPlayers++;
				}
			}
		}
		$dto = new CasinoTableDto($casinoTable);
		$dto->numberCurrentPlayers = $currentPlayers;
		$dto->numberWaitingPlayers = $waitingPlayers;
		return $dto;
	}

	/**
	 * 
	 * @global type $buyInMultiplier
	 * @param type $playerId
	 * @param type $casinoTable
	 * @param type $allPlayers
	 * @return \Player
	 */
	public static function AddUserToTable($playerId, $casinoTable) {
		global $buyInMultiplier;

		// a table always has a game session;
		$gameSession = EntityHelper::GetGameSession($casinoTable->currentGameSessionId);
		if ($gameSession === null) {
			throw new Exception("Invalid casino table");
		}

		if ($gameSession->isActive === 0) {
			// note that CasinoTable is updated
			$gameSession = $casinoTable->ResetGameSession($playerId);
			/* need to communicate new game status */
			$gameSession->communicateResetStatus($casinoTable);
		}
		// session exists is active but may not have any players left
		else if (isset($gameSession->gameInstanceId) && $gameSession->gameInstanceId !== null) {
			// reset game session if session stale or not users left- the user will be the first 
			$activePlayers = PlayerInstance::GetActivePlayerInstancesForGame($gameSession->gameInstanceId);
			if (count($activePlayers) === 0) {
				$gameSession = $casinoTable->ResetGameSession($playerId);
				/* need to communicate new game status */
				$gameSession->communicateResetStatus($casinoTable);
			}
		}
		// scheduled job cleans up stale sessions, but just in case
		if ($casinoTable->IsSessionStale()) {
			// note that CasinoTable is updated
			$gameSession = $casinoTable->ResetGameSession($playerId);
			/* need to communicate new game status */
			$gameSession->communicateResetStatus($casinoTable);
		}

		// exception will be thrown if user not found.
		$player = EntityHelper::getPlayer($playerId);
		$allPlayers = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);

		// craft the return DTO
		// scenario #1 user must have accidentally closed browser, nothing to do
		if ($casinoTable->id === $player->currentCasinoTableId) {
			QueueManager::GetPlayerQueue($playerId, Context::GetQCh());
			return GameStatusDto::InitForTable($player, $allPlayers, $casinoTable, true);
		}

		// scenario #2 If user in another table, eject so seat vacated and minimize wait for time out
		if (!is_null($player->currentCasinoTableId) && $casinoTable->id !== $player->currentCasinoTableId) {
			self::log()->debug(__FUNCTION__ . ": player $player->id at new casino table id
                        $casinoTable->id previous " . $player->currentCasinoTableId);
			$otherTable = EntityHelper::getCasinoTable($player->currentCasinoTableId);
			if (!is_null($otherTable)) {
				$vacatedSeat = TableCoordinator::RemoveUserFromTable($otherTable, $playerId);
				TableCoordinator::ReserveAndOfferSeat($otherTable, $vacatedSeat);
			}
		}

		// find user a seat
		$seatNum = $casinoTable->FindAvailableSeat($allPlayers);

		// reset queue for player on table (purges any previous game session messages)
		QueueManager::addPlayerQueue($playerId, Context::GetQCh());

		// update player's casino table
		$player->currentCasinoTableId = $casinoTable->id;
		$player->lastUpdateDateTime = Context::GetStatusDT();
		if (!is_null($seatNum)) {
			$player->currentSeatNumber = $seatNum;
		} else {
			$player->waitStartDateTime = Context::GetStatusDT();
		}
		$player->buyIn = $casinoTable->tableMinimum * $buyInMultiplier;

		$player->Update();

// Communicating that user joined to the other players already at table
// notice user is skipped because response sent as REST
		// update all players to include current player
		//$updatedPlayers = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
		if (!is_null($allPlayers)) {
			$casinoTable->CommunicateUserJoined($player, $allPlayers, false);
		}
		return GameStatusDto::InitForTable($player, EntityHelper::GetPlayersForCasinoTable($casinoTable->id), $casinoTable, true);
	}

	/**
	 * If user leaving means only one left, remaining user wins. If none left, then
	 * delete game session.
	 * @param CasinoTable $casinoTable
	 * @param int $playerId
	 * @return int
	 */
	public static function RemoveUserFromTable($casinoTable, $playerId) {
		$leavingPlayer = EntityHelper::getPlayer($playerId);

		$vacatedSeat = null;

		if (!is_null($leavingPlayer)) {
			$playerTableId = $leavingPlayer->currentCasinoTableId;
			// returned vacated seat if user on table
			if ($casinoTable->id === $playerTableId) {
				$vacatedSeat = $leavingPlayer->currentSeatNumber;
			}
			if ($vacatedSeat == null) {
				$vacatedSeat = $leavingPlayer->reservedSeatNumber;
			}
			$leavingPlayer->currentCasinoTableId = null;
			$leavingPlayer->currentSeatNumber = null;
			$leavingPlayer->waitStartDateTime = null;
			$leavingPlayer->reservedSeatNumber = null;
			$leavingPlayer->Update();
		}
		$gameInstance = EntityHelper::getSessionLastInstance($casinoTable->currentGameSessionId);
		if ($gameInstance !== null) {
			// if leaving user's turn set it to next
			$leavingPlayerStatus = EntityHelper::getPlayerInstance($gameInstance->id, $playerId);
			$leavingPlayerStatus->status = PlayerStatusType::LEFT;
			$leavingPlayerStatus->UpdatePlayerLeftStatus();
			$pokerMove = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstance->id);
			// if leaving user has turn, skip move. communicate skip before left
			// note that player status is LEFT; this avoids infinite loops between SkipPokerMove and RemoveUserFromTable
			if (!is_null($pokerMove) && $pokerMove->playerId === $playerId) {
				PokerCoordinator::SkipPokerMove($pokerMove, $gameInstance);
				$gameInstance->lastInstancePlayNumber +=1;
				$gameInstance->UpdateInstanceAfterMove();
			}
		} else {
			$leavingPlayerStatus = PlayerInstance::MapPlayerToStatus($leavingPlayer);
		}
		// communicate user left
		// notify user 
		$text = "You are being ejected because you missed three turns.";
		$casinoTable->CommunicateUserMessage(EventType::UserEjected, $playerId, $text);
		$players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
		$casinoTable->CommunicateUserLeft($leavingPlayerStatus, $players);
		// clean up - purge queue and reset sleeves 
		/* purge queue later
		$ch = Context::GetQCh();
		$q = QueueManager::GetPlayerQueue($playerId, $ch);
		QueueManager::DeleteQueue($q);
		 * 
		 */
		// removes from both sleeve and under table groove 
		$hidden = new PlayerHiddenCards($playerId, null, ItemType::TUCKER_TABLE_SLIDE_UNDER);
		$hidden->ResetSleeve();
		$visible = new PlayerVisibleCards($playerId);
		$visible->ResetVisible();

		// delete session queue if any
		if ($gameInstance) {
			$activePlayers = PlayerInstance::GetActivePlayerInstancesForGame($gameInstance->id);
			if (count($activePlayers) === 0) {
				$gameSession = EntityHelper::GetGameSession($casinoTable->currentGameSessionId);
				$gameSession->EndSession();
			}
		}
		return $vacatedSeat;
	}

	/**
	 * 
	 * @param type $casinoTable
	 * @param int $seatNum
	 * @return null|boolean
	 * @throws Exception
	 */
	public static function ReserveAndOfferSeat($casinoTable, $seatNum) {
		$statusDT = "'" . Context::GetStatusDTString() . "'";

		$waitingPlayer = $casinoTable->FindNextWaitingPlayer();
		if (is_null($waitingPlayer)) {
// nobody waiting for seat
			return null;
		}
		$occupantPlayerId = $casinoTable->IsSeatTakenOrReservedBy($seatNum);
		if (!is_null($occupantPlayerId) && $waitingPlayer->id != $occupantPlayerId) {
			throw new Exception("Player $occupantPlayerId already has seat $seatNum
                    reserved so player id $waitingPlayer->id cannot take it");
		}

// if player not on casino table, log error
		if ($casinoTable->id !== $waitingPlayer->currentCasinoTableId) {
			throw new Exception("Player $waitingPlayer->id cannot reserve any 
                seats because player is not at table $casinoTable->id");
		}

// TODO: must consolidate this with IsSeatTakenOrReservedBy
		$currentSeat = $waitingPlayer->currentSeatNumber;
		$reservedSeat = $waitingPlayer->reservedSeatNumber;
		if ($currentSeat != null && $currentSeat != $seatNum) {
			throw new Exception("Player $waitingPlayer->id already has seat $currentSeat and cannot
                    take $seatNum");
		}
		if ($reservedSeat != null && $reservedSeat != $seatNum) {
			throw new Exception("Player $waitingPlayer->id already has seat $reservedSeat reserved and
                    cannot take $seatNum");
		}
		$waitingPlayer->reservedSeatNumber = $seatNum;

// TODO: move to CasinoTable
		try {
			executeSQL("UPDATE Player SET ReservedSeatNumber = $seatNum,
					LastUpdateDateTime = $statusDT WHERE Id =
                    $waitingPlayer->id", __FUNCTION__ . "
                    : Error updating Player id $waitingPlayer->id to reserved seat number $seatNum");
		} catch (Exception $e) {
			$waitingPlayer->reservedSeatNumber = null;
			return false;
		}

		$casinoTable->CommunicateSeatOffered($waitingPlayer->id, $seatNum);
		return true;
	}

	/**
	 * 
	 * @param type $gameSessionId
	 * @param type $seatNum
	 * @param type $pId
	 * @throws Exception
	 */
	public static function SeatUserOnTable($gameSessionId, $seatNum, $pId) {
		$casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
		$players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
		if (is_null($seatNum)) {
			throw new Exception("Missing parameter - Player $pId cannot reserve empty seat
                    at table $casinoTable->id");
		}
		$seatingPlayer = EntityHelper::getPlayer($pId);

		// verify seat is reserved
		if ($seatingPlayer->currentCasinoTableId != $casinoTable->id) {
			throw new Exception("Player $pId cannot reserve any seats because player is
                    not at table $casinoTable->id");
		}

		$occupantPlayerId = $casinoTable->IsSeatTakenOrReservedBy($seatNum);
		if ($pId != $occupantPlayerId) {
			throw new Exception("Player $occupantPlayerId already has seat $seatNum
                    reserved so player id $pId cannot take it");
		}

		$currentSeat = $seatingPlayer->currentSeatNumber;
		$reservedSeat = $seatingPlayer->reservedSeatNumber;

		if ($currentSeat != null && $currentSeat != $seatNum) {
			throw new Exception("The player $pId already has seat $currentSeat and cannot
                    take $seatNum");
		}
// note that the player may take a seat even if he did not reserve it.
		if ($reservedSeat != null && $reservedSeat != $seatNum) {
			throw new Exception("The player $pId already has reserved seat $reservedSeat
                    and cannot take $seatNum");
		}
		$seatingPlayer->UpdatePlayerSeat($seatNum);

		$casinoTable->CommunicateSeatTaken($seatingPlayer, $players);
	}

	/*	 * *
	 * Only rule for adding money to the table is no live game is playing
	 * A user will have to add money to a game before a game starts if
	 * less than 3* minimum remaining.
	 */

	public static function AddMoneyToTable($casinoTable, $player) {
		
	}

}

?>
