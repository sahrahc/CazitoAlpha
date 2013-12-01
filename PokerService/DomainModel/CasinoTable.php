<?php

/* * ************************************************************************************** */

/**
 * A virtual casino table with a fixed number of seats. A table exits even if there are no users currently playing. Casino tables are named entities so that users may go back to the same table across sessions.
 * Seat numbers map to specific UI locations by convention. They are stored at the casino table level and the game instance level, as tables may be adjusted to hold different number of players.
 * A player's assigned seat is stored at the table and at the player game level because users who take a seat while a game is being played must wait until the game ends. The seat information while assigning, offering and taking a seat is at the casino table level.
 */
class CasinoTable {

	public $id;
	public $name;
	public $code;
	public $description;
	public $tableMinimum;
	public $numberSeats;
	public $lastUpdateDateTime;
	public $currentGameSessionId;
	public $sessionStartDateTime;
	// log
	private $log;

	public function __construct() {
		$this->log = Logger::getLogger(__CLASS__);
	}

	/*	 * ***************************************************************************** */
	/* gaming */

	/**
	 * Calculates the amount of the first and second blind based on table minimums and current game play.
	 * @return array{int, int}
	 */
	public function FindBlindBetAmounts() {
		if (is_null($this->tableMinimum)) {
			throw new Exception("Empty casino table, cannot find blind bets");
		}
		$blind1 = $this->tableMinimum / 2;
		$blind2 = $blind1 * 2;
		$this->log->Debug(__FUNCTION__ . "Blind 1 is $blind1 and Blind2 is $blind2");
		return array($blind1, $blind2);
	}

	public function IsSessionStale() {
		if (isset($_SESSION['isSessionStale'])) {
			return $_SESSION['isSessionStale'];
		}
		global $sessionExpiration;
		global $dateTimeFormat;
		$result = executeSQL("SELECT i.LastUpdateDateTime FROM GameInstance i 
			INNER JOIN GameSession s on s.Id = i.GameSessionId
                WHERE i.GameSessionId = $this->currentGameSessionId 
					AND s.IsActive = 1 ORDER BY i.StartDateTime DESC
                ", __FUNCTION__ . ":Error selecting from GameInstance session id
                $this->currentGameSessionId");
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			// expiration date time is 24 hours after the last update
			$expirationDateTime = DateTime::createFromFormat($dateTimeFormat, $row[0]);
			$expirationDateTime->add(new DateInterval($sessionExpiration)); // 24 hours
			$this->log->warn(" Last Update " . json_encode($expirationDateTime));
			$isSessionStale = new DateTime() > $expirationDateTime ? true : false;
			$_SESSION['isSessionStale'] = $isSessionStale;
			return $isSessionStale;
		}
	}

	/**
	 * Creates a new game session on the casino table.
	 * Note that casinoTable is updated
	 * @param timestamp $statusDT
	 * @return GameSession
	 */
	public function ResetGameSession($playerId) {
		// set old session to expired
		$statusDT = Context::GetStatusDTString();
		executeSQL("UPDATE GameSession SET IsActive = 0 " //, LastUpdateDateTime = '$statusDT' "
				. "WHERE Id = " . $this->currentGameSessionId, ": Error making GameSession inactive id " . $this->currentGameSessionId);
		$nextSessionId = getNextSequence('GameSession', 'Id');
		executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
            TableMinimum, NumberSeats, StartDateTime, IsPractice,
                    IsActive) VALUES ($nextSessionId, $playerId,
                $this->tableMinimum, $this->numberSeats,
                    '$statusDT', 0, 1)", __FUNCTION__ .
				": Error inserting into GameSession with generated id $nextSessionId");
		$this->currentGameSessionId = $nextSessionId;
		$this->sessionStartDateTime = $statusDT;
		$this->lastUpdateDateTime = $statusDT;
		$this->_updateSessionForCasinoTable();

		return new GameSession($nextSessionId, $playerId);
	}

	/**
	 * Get the number of players in the waiting list of a table.
	 * @return int
	 */
	public function GetWaitingListSize() {
		$result = executeSQL("SELECT COUNT(1) FROM Player WHERE CurrentCasinoTableId =
                $this->id AND CurrentSeatNumber is null", __FUNCTION__ . ": Error select
                    count of waiting list on casino id $this->id");
		$row = mysql_fetch_array($result);
		return (int) $row[0];
	}

	/**
	 * Gets the next player in the waiting list. If none return null.
	 * @return int playerId
	 */
	public function FindNextWaitingPlayer() {
		$result = executeSQL("SELECT Id from Player WHERE CurrentCasinoTableId = $this->id
                AND CurrentSeatNumber is NULL AND ReservedSeatNumber is NULL
				ORDER BY WaitStartDateTime", __FUNCTION__ . ":
                    Error selecting player without a seat for casino table $this->id");
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result);
		return EntityHelper::getPlayer($row[0]);
	}

	/*	 * ***************************************************************************** */

	public static function IsPlayerOnTableSession($gameSessionId, $playerId) {
		$result = executeSQL("Select p.Id FROM Player p
            INNER JOIN CasinoTable c on p.CurrentCasinoTableId = c.Id
            WHERE c.CurrentGameSessionId = $gameSessionId AND
                p.Id = $playerId", __FUNCTION__ .
				": Error selecting Player $playerId on game session id $gameSessionId");

		if (mysql_num_rows($result) == 0) {
			return false;
		}
		return true;
	}

	/**
	 * Find the player who is reserving or occupying a specific seat at a casino table.
	 * @param int $seatNum The seat number being checked
	 * @return playerId The player id or null if no player is taking or reserving that seat.
	 */
	public function IsSeatTakenOrReservedBy($seatNum) {
		if (is_null($seatNum)) {
			return null;
		}
		$result = executeSQL("SELECT Id FROM Player WHERE CurrentCasinoTableId = $this->id
                AND (CurrentSeatNumber = $seatNum || ReservedSeatNumber = $seatNum)"
				, __FUNCTION__ .
				": ERROR selecting FROM Player id $this->id, seatnumber $seatNum");
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			return $row[0];
		}
		return null;
	}

	/*	 * ****************************************************************************** */
	// seat management

	/**
	 * Find the lowest numbered seat that is not taken or reserved.
	 * @return int
	 */
	public function FindAvailableSeat($players) {
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
				$this->log->debug(__FUNCTION__ . "Empty seat found " . $emptySeat);
				return $emptySeat;
			}
			$previous = $takenSeats[$i];
		}
		// edge case: last seats empty
		$seatNumber = count($takenSeats);
		if ($seatNumber < $this->numberSeats) {
			return $seatNumber;
		}
		return null;
	}

	/*	 * ****************************************************************************** */

	/*
	 * The newly joined player is excluded because status is sent as REST response
	 * unless the user is rejoining (may have accidentally closed browser) in 
	 * which case includeNewPlayerFlag is set (because queue still available).
	 */

	public function CommunicateUserJoined($newPlayer, $allPlayers, $includeNewPlayer = true) {
		$ex = Context::GetExchangePlayer();

		// new user info returned as player includ name and image
		$newPlayerStatusDto = PlayerStatusDto::mapPlayers(array($newPlayer), PlayerStatusType::WAITING, true);
		$this->log->debug(__FUNCTION__ . ": Waiting list size " . $this->GetWaitingListSize());
		$newPlayerStatusDto[0]->waitingListSize = $this->GetWaitingListSize();
		// dynamically adding this
		$eventType = EventType::SeatTaken;

		for ($i = 0; $i < count($allPlayers); $i++) {
			// newly joined user to receive REST response to REST request
			if ($allPlayers[$i]->id != $newPlayer->id ||
					($includeNewPlayer && $allPlayers[$i]->id == $newPlayer->id)) {
				$message = new QueueMessage($eventType, $newPlayerStatusDto, $this->currentGameSessionId);
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
	public function CommunicateUserLeft($departedPlayerStatus, $allPlayers) {
		$ex = Context::GetExchangePlayer();
		$waitingListSize = $this->GetWaitingListSize();

		// even though single player, send as array
		$departedPlayerStatusDtos = PlayerStatusDto::MapPlayerStatuses(array($departedPlayerStatus));
		$departedPlayerStatusDtos[0]->waitingListSize = $waitingListSize;
		$eventType = EventType::UserLeft;

		for ($i = 0; $i < count($allPlayers); $i++) {
			// ignore user who left
			if ($allPlayers[$i]->id != $departedPlayerStatus->playerId) {
				$message = new QueueMessage($eventType, $departedPlayerStatusDtos, $this->currentGameSessionId);
				//$message->eventData = $playerStatusDtos;
				QueueManager::SendToPlayer($ex, $allPlayers[$i]->id, json_encode($message));
			}
		}
	}

	/**
	 * Generic communication for user who left
	 * @global type $dateTimeFormat
	 * @param type $dto
	 * @param type $playerDtos
	 */
	public function CommunicateUserMessage($eventType, $playerId, $text) {
		$ex = Context::GetExchangePlayer();

		$message = new QueueMessage($eventType, $text, $this->currentGameSessionId);
		//$message->eventData = $playerStatusDtos;
		QueueManager::SendToPlayer($ex, $playerId, json_encode($message));
	}

	public function CommunicateSeatTaken($seatedPlayer, $allPlayers) {
		$ex = Context::GetExchangePlayer();

		$seatedPlayerStatusDtos = PlayerStatusDto::mapPlayers(array($seatedPlayer), PlayerStatusType::SEATED, true);
		$this->log->debug(__FUNCTION__ . ": Waiting list size " . $this->GetWaitingListSize());
		$seatedPlayerStatusDtos[0]->waitingListSize = $this->GetWaitingListSize();
		$eventType = EventType::SeatTaken;

		for ($i = 0; $i < count($allPlayers); $i++) {
			$message = new QueueMessage($eventType, $seatedPlayerStatusDtos, $this->currentGameSessionId);
			QueueManager::SendToPlayer($ex, $allPlayers[$i]->id, json_encode($message));
		}
	}

	public function CommunicateSeatOffered($waitingPlayerId, $seatNum) {
		$ex = Context::GetExchangePlayer();

		// TODO: move this to CasinoTable communicate
		$actionType = EventType::SeatOffer;

		$message = new QueueMessage($actionType, $seatNum, $this->currentGameSessionId);
		//$message->eventData = $seatNumber;
		QueueManager::SendToPlayer($ex, $waitingPlayerId, json_encode($message));
	}

	/**
	 * Game session are expired if no activity in specified period of type:
	 *   1) players added (see last update time on player) AND
	 *   2) no game instances
	 * Also updates casino table
	 * $unusedDateTime = sessions that did not have any play for this period of time
	 */
	public static function DeleteExpiredGameSessions($unusedDateTime) {
		global $dateTimeFormat;
		$statusDT = "'" . Context::GetStatusDTString() . "'";
		$unusedString = "'" . $unusedDateTime->format($dateTimeFormat) . "'";

		$result = executeSQL("SELECT gs.Id GameSessionId, c.Id CasinoTableId "
				. "FROM GameSession gs INNER JOIN CasinoTable c "
				. "WHERE gs.Id not in (select distinct(GameSessionId) from GameInstance) "
				. "AND c.Id not in (SELECT distinct(CurrentCasinoTableId) from Player "
				. "WHERE LastUpdateDateTime <= $unusedString)", __FUNCTION__, ": Error selecting expired game sessions.");
		/*        executeSQL("UPDATE CasinoTable c LEFT JOIN 
		  GameInstance s on s.GameSessionId = c.CurrentGameSessionId
		  SET CurrentGameSessionId = null, SessionStartDateTime = null
		  WHERE s.LastUpdateDateTime <= '$endString' OR
		  (s.Id IS NULL AND c.SessionStartDateTime <= '$unusedString')", __FUNCTION__ .
		  ": Error updating casino tables for game sessions that are expired");
		  executeSQL("DELETE GameSession FROM GameSession
		  LEFT JOIN GameInstance on GameInstance.GameSessionId = GameSession.Id
		  WHERE GameInstance.LastUpdateDateTime <= '$endString' OR
		  (GameInstance.Id IS NULL AND GameSession.StartDateTime <= '$unusedString')", __FUNCTION__ .
		  ": Error deleting game sessions that are expired");
		  $result = executeSQL("SELECT s.Id FROM GameSession s
		  LEFT JOIN GameInstance i on i.GameSessionId = s.Id
		  WHERE i.LastUpdateDateTime <= '$endString' OR
		  (i.Id IS NULL AND s.StartDateTime <= '$unusedString')", __FUNCTION__ .
		  ": Error selecting game sessions that are expired"); */
		$counter = 0;
		$gameSessionIds = null;
		// want to loop, so you can log
		while ($row = mysql_fetch_array($result)) {
			$gameSessionIds[$counter] = $row['GameSessionId'];
			$casinoTableId = $row['CasinoTableId'];
			executeSQL("Update CasinoTable Set CurrentGameSessionId = null, "
					. "LastUpdateDateTime = $statusDT, SessionStartDateTime = null "
					. "Where Id = $casinoTableId", __FUNCTION__ . ": Error unsetting game session for casino table $casinoTableId");
			executeSQL("Update Player Set CurrentCasinoTableId = null, "
					. "CurrentSeatNumber = null, LastUpdateDateTime = $statusDT, "
					. "WaitStartDateTime = null, ReservedSeatNumber = null, BuyIn = null "
					. "WHERE CurrentCasinoTableId = $casinoTableId", __FUNCTION__ . ": Error unsetting table $casinoTableId on all Players.");
			$gameSession = new GameSession($row['GameSessionId'], -1);
			$gameSession->Delete();
			$counter++;
		}
		return $gameSessionIds;
	}

	private function _updateSessionForCasinoTable() {
		global $dateTimeFormat;
		$sessionStartDT = $this->sessionStartDateTime;
		$statusDT = $this->lastUpdateDateTime;

		executeSQL("UPDATE CasinoTable SET CurrentGameSessionId = $this->currentGameSessionId,
            SessionStartDateTime = '$sessionStartDT', LastUpdateDateTime = '$statusDT'
            WHERE Id = $this->id", __FUNCTION__ .
				": Error updating casino's session with generated id $this->currentGameSessionId");
	}

}

?>
