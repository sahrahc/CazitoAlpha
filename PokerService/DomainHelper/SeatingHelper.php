<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class SeatingHelper {
		private static $log = null;

	public static function log() {
		if (is_null(self::$log)) {
			self::$log = Logger::getLogger('CasinoTable');
		}
		return self::$log;
	}

	/**
	 * Find the lowest numbered seat that is not taken or reserved.
	 * @return int
	 */
	public static function FindAvailableSeat($numberSeats, $players) {
		$takenSeats = null;
		for ($i = 0, $j = 0; $i < count($players); $i++) {
			// collect reserved seats
			if (!is_null($players[$i]->reservedSeatNumber)) {
				$takenSeats[$j++] = $players[$i]->reservedSeatNumber;
			}
			// collect occupied seats
			if (!is_null($players[$i]->currentSeatNumber)) {
				$takenSeats[$j++] = $players[$i]->currentSeatNumber;
			}
		}
		// return first seat if no seats are taken.
		if (is_null($takenSeats)) {
			return 0;
		}
		sort($takenSeats);
		// edge case: first seat empty
		if ($takenSeats[0] != 0) {
			return 0;
		}

		$previous = 0;
		for ($i = 0; $i < count($takenSeats); $i++) {
			// this is how the gap is detected
			if ($takenSeats[$i] - 1 > $previous) {
				$emptySeat = $previous + 1;
				//self::log()->debug(__FUNCTION__ . "Empty seat found " . $emptySeat);
				return $emptySeat;
			}
			$previous = $takenSeats[$i];
		}
		// edge case: last seats empty
		$seatNumber = count($takenSeats);
		if ($seatNumber < $numberSeats) {
			return $seatNumber;
		}
		return null;
	}

	/**
	 * Gets the next player in the waiting list. If none return null.
	 * @return int playerId
	 */
	public static function FindNextWaitingPlayer($casinoTableId) {
		$query = "SELECT Id from Player WHERE CurrentCasinoTableId = $casinoTableId
                AND CurrentSeatNumber is NULL AND ReservedSeatNumber is NULL
				ORDER BY WaitStartDateTime";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result, MYSQL_NUM);
		return Player::GetPlayer($row[0]);
	}

	/**
	 * Get the number of players in the waiting list of a table.
	 * @return int
	 */
	public static function GetWaitingListSize($casinoTableId) {
		$query = "SELECT COUNT(1) FROM Player WHERE CurrentCasinoTableId =
                $casinoTableId AND CurrentSeatNumber is null";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		return (int) $row[0];
	}

	/**
	 * Find the player who is reserving or occupying a specific seat at a casino table.
	 * @param int $seatNum The seat number being checked
	 * @return playerId The player id or null if no player is taking or reserving that seat.
	 */
	public static function IsSeatTakenOrReservedBy($casinoTableId, $seatNum) {
		if (is_null($seatNum)) {
			return null;
		}
		$query = "SELECT Id FROM Player WHERE CurrentCasinoTableId = $casinoTableId
                AND (CurrentSeatNumber = $seatNum || ReservedSeatNumber = $seatNum)";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_NUM);
			return $row[0];
		}
		return null;
	}

	public static function IsPlayerOnTableSession($gameSessionId, $playerId) {
		$query = "Select p.Id FROM Player p
            INNER JOIN CasinoTable c on p.CurrentCasinoTableId = c.Id
            WHERE c.CurrentGameSessionId = $gameSessionId AND
                p.Id = $playerId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);

		if (mysql_num_rows($result) == 0) {
			return false;
		}
		return true;
	}

	public static function CommunicateUserJoined($casinoTable, $newPlayer, $allPlayers, $includeNewPlayer = true) {
		$ex = Context::GetExchangePlayer();

		// new user info returned as player includ name and image
		$newPlayerStatusDto = PlayerStatusDto::mapPlayers(array($newPlayer), PlayerStatusType::WAITING, true);
		$waitingListSize = self::GetWaitingListSize($casinoTable->id);
		//self::log()->debug(__FUNCTION__ . ": Waiting list size " . $waitingListSize);
		$newPlayerStatusDto[0]->waitingListSize = $waitingListSize;
		// dynamically adding this
		$eventType = EventType::SeatTaken;

		for ($i = 0; $i < count($allPlayers); $i++) {
			// newly joined user to receive REST response to REST request
			if ($allPlayers[$i]->id != $newPlayer->id ||
					($includeNewPlayer && $allPlayers[$i]->id == $newPlayer->id)) {
				$message = new QueueMessage($eventType, $newPlayerStatusDto, $casinoTable->currentGameSessionId);
				//$message->eventData = $playerStatusDtos;
				QueueManager::SendToPlayer($ex, $allPlayers[$i]->id, json_encode($message));
			}
		}
	}

	/**
	 * Sends updated player states
	 * @global type $dateTimeFormat
	 * @param type $dto
	 * @param type $playerDtos
	 */
	public static function CommunicateUserLeft($casinoTable, $departedPlayerStatus, $allPlayers) {
		$ex = Context::GetExchangePlayer();
		$waitingListSize = self::GetWaitingListSize($casinoTable->id);

		// even though single player, send as array
		$departedPlayerStatusDtos = PlayerStatusDto::MapPlayerStatuses(array($departedPlayerStatus));
		$departedPlayerStatusDtos[0]->waitingListSize = $waitingListSize;
		$eventType = EventType::UserLeft;

		for ($i = 0; $i < count($allPlayers); $i++) {
			// ignore user who left
			if ($allPlayers[$i]->id != $departedPlayerStatus->playerId) {
				$message = new QueueMessage($eventType, $departedPlayerStatusDtos, $casinoTable->currentGameSessionId);
				//$message->eventData = $playerStatusDtos;
				QueueManager::SendToPlayer($ex, $allPlayers[$i]->id, json_encode($message));
			}
		}
	}

	public function CommunicateSeatOffered($casinoTable, $waitingPlayerId, $seatNum) {
		$ex = Context::GetExchangePlayer();

		// TODO: move this to CasinoTable communicate
		$actionType = EventType::SeatOffer;

		$message = new QueueMessage($actionType, $seatNum, $casinoTable->currentGameSessionId);
		//$message->eventData = $seatNumber;
		QueueManager::SendToPlayer($ex, $waitingPlayerId, json_encode($message));
	}

	public function CommunicateSeatTaken($casinoTable, $seatedPlayer, $allPlayers) {
		$ex = Context::GetExchangePlayer();

		$seatedPlayerStatusDtos = PlayerStatusDto::mapPlayers(array($seatedPlayer), PlayerStatusType::SEATED, true);
		$waitListSize = self::GetWaitingListSize($casinoTable->id);
		//self::log()->debug(__FUNCTION__ . ": Waiting list size " . $waitListSize);
		$seatedPlayerStatusDtos[0]->waitingListSize = $$waitListSize;
		$eventType = EventType::SeatTaken;

		for ($i = 0; $i < count($allPlayers); $i++) {
			$message = new QueueMessage($eventType, $seatedPlayerStatusDtos, $casinoTable->currentGameSessionId);
			QueueManager::SendToPlayer($ex, $allPlayers[$i]->id, json_encode($message));
		}
	}


}
?>
