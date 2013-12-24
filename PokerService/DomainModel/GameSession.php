<?php

/* * ************************************************************************************* */

/**
 * An instance of two or more players (real or otherwise) being together to play a game at a point in time.
 */
class GameSession {

	public $id;
	public $startDateTime;
	public $requestingPlayerId;
	public $isCheatingAllowed;
	// transient only
	public $isPractice = false;
	protected $history;

	public function __construct($gSessionId, $playerId) {
		$this->history = Logger::getLogger(__CLASS__);
		$this->id = (int) $gSessionId;
		$this->requestingPlayerId = (int) $playerId;
	}

	private function mapRow($row) {
		global $dateTimeFormat;
		$this->tableMinimum = is_null($row["TableMinimum"]) ? null : (int) $row["TableMinimum"];
		$this->numberSeats = is_null($row["NumberSeats"]) ? null : (int) $row["NumberSeats"];
		$this->startDateTime = DateTime::createFromFormat($dateTimeFormat, $row["StartDateTime"]);
	}

	public static function GetGameSession($gameSessionId) {
		$query = "SELECT * FROM GameSession WHERE Id = $gameSessionId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$isPractice = is_null($row["IsPractice"]) ? null : (int) $row["IsPractice"];
		if ($isPractice) {
			$gameSession = new PracticeSession($row["Id"], $row["RequestingPlayerId"]);
		} else {
			$gameSession = new GameSession($row["Id"], $row["RequestingPlayerId"]);
		}
		$gameSession->mapRow($row);
		return $gameSession;
	}

	/**
	 * Returns all the active sessions, live and practice
	 * @param type $casinoTableId
	 * @return int[]
	 */
	public static function GetActiveGameSessionIds() {
		global $dateTimeFormat;
		global $sessionExpiration;
		$expirationDateTime = Context::GetStatusDT();
		$expirationDateTime->sub(new DateInterval($sessionExpiration)); // 24 hours
		$expString = $expirationDateTime->format($dateTimeFormat);

		$query = "SELECT distinct(s.Id) Id FROM GameSession s " .
				" LEFT JOIN GameInstance i on s.Id = i.GameSessionId" .
				" WHERE (i.LastUpdateDateTime >= '$expString' OR " .
				" (s.StartDateTime >= '$expString' AND i.Id is null)) ";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$gameSessionIds = null;
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$gameSessionIds[$i++] = (int) $row["Id"];
		}
		return $gameSessionIds;
	}

	/* Used to find active practice instances */

	public static function GetLastSessionByRequestor($currentGameSessionId, $playerId) {
		$result = executeSQL("select * from GameSession "
				. "WHERE RequestingPlayerId = $playerId and Id <> $currentGameSessionId "
				. "ORDER BY StartDateTime DESC LIMIT 1", __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			$result = executeSQL("select s.* from GameSession s "
					. "INNER JOIN PlayerState i ON s.Id = i.GameSessionId "
					. "WHERE i.PlayerId = $playerId and s.Id <> $currentGameSessionId "
					. "ORDER BY StartDateTime DESC LIMIT 1", __CLASS__ . "-" . __FUNCTION__);
		}
		if (mysql_num_rows($result) == 0) {
			return null;
		}

		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$isPractice = is_null($row["IsPractice"]) ? null : (int) $row["IsPractice"];
		if ($isPractice) {
			$gameSession = new PracticeSession($row["Id"], $row["RequestingPlayerId"]);
		} else {
			$gameSession = new GameSession($row["Id"], $row["RequestingPlayerId"]);
		}
		$gameSession->mapRow($row);
		return $gameSession;
	}

	/**
	 * Create and save game instance. Initialize values including identifier but there is no real data.
	 * The number of players participating in this next game is set when the turns are reset.
	 * @param int $tableMin The minimize bet.
	 * @param type $statusDT
	 * @return GameInstance
	 */
	public function InitNewGameInstance() {
		$statusDT = Context::GetStatusDT();

		$nextInstanceId = getNextSequence('GameInstance', 'Id');
		$gameInstance = new GameInstance($nextInstanceId);
		$gameInstance->gameSessionId = $this->id;
		$gameInstance->status = GameStatus::STARTED;
		$gameInstance->startDateTime = $statusDT;
		$gameInstance->lastUpdateDateTime = $statusDT;
		// number of players set later while reseting turns.
		// dealer and first player set later per business rules
		$gameInstance->currentPotSize = 0;
		$gameInstance->lastBetSize = 0;
		$gameInstance->numberCommunityCardsShown = 0;
		$gameInstance->lastInstancePlayNumber = 0;

		$gameInstance->Insert();
		return $gameInstance;
	}

	/**
	 * Communicates game started to the list of recipients. 
	 * @param type $instanceSetupDto
	 * @param Player[] $recipientPlayers Cannot be PlayerInstance because waiting players need to be communicated
	 */
	function CommunicateGameStarted($gameStatusDto, $recipientPlayers) {
		$QEx = Context::GetExchangePlayer();

		$eventType = EventType::GameStarted;
		$instanceId = $gameStatusDto->gameInstanceId;

		for ($i = 0; $i < count($recipientPlayers); $i++) {
			$playerId = $recipientPlayers[$i]->id;
			$gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $instanceId);

			$message = new QueueMessage($eventType, $gameStatusDto, $this->id);
			//$message->eventData = $instanceSetupDto;
			QueueManager::SendToPlayer($QEx, $playerId, json_encode($message));
			//}
		}
	}

	/**
	 * game was reset, communicate to all active players.
	 */
	function communicateResetStatus($casinoTable) {
		$recipientPlayers = Player::GetPlayersForCasinoTable($casinoTable->id);
		$gameStatusDto = GameStatusDto::InitResetSession($recipientPlayers, $casinoTable);

		$QEx = Context::GetExchangePlayer();

		// shortcut
		$eventType = EventType::ChangeNextTurn;
		for ($i = 0; $i < count($recipientPlayers); $i++) {
			$playerId = $recipientPlayers[$i]->id;

			$gameStatusDto->userPlayerId = $playerId;
			$gameStatusDto->userSeatNumber = $recipientPlayers[$i]->currentSeatNumber;
			$message = new QueueMessage($eventType, $gameStatusDto, $this->id);
			//$message->eventData = $instanceSetupDto;
			QueueManager::SendToPlayer($QEx, $playerId, json_encode($message));
			//}
		}
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
		$unusedString = "'" . $unusedDateTime->format($dateTimeFormat) . "'";

		$query = "SELECT gs.Id GameSessionId, gs.RequestingPlayerId PlayerId, "
				. "c.Id CasinoTableId, gs.IsPractice "
				. "FROM GameSession gs "
				. "LEFT JOIN CasinoTable c ON gs.Id = c.CurrentGameSessionId "
				. "LEFT JOIN GameInstance i on i.GameSessionId = gs.Id "
				. "LEFT JOIN Player p on p.CurrentCasinoTableId = c.Id "
				. "WHERE i.Id IS NULL "
				. "AND (p.Id IS NULL OR p.LastUpdateDateTime <= $unusedString)"
				. "GROUP BY gs.Id, gs.RequestingPlayerId, c.Id";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		// want to loop, so you can log
		$ch = Context::GetQCh();
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$gameSessionId = (int) $row['GameSessionId'];
			$casinoTableId = $row['CasinoTableId'] == null ? null : (int) $row['CasinoTableId'];
			$requestingPlayerId = (int) $row['PlayerId'];
			$isPractice = (int) $row['IsPractice'];
			// live game only
			if (!is_null($casinoTableId)) {
				$casinoTable = new CasinoTable($casinoTableId);
				$casinoTable->currentGameSessionId = $gameSessionId;

				$players = Player::GetPlayersForCasinoTable($casinoTableId);
				if (!is_null($players)) {
					foreach ($players as $player) {
						$text = "Ending inactive game.";
						$casinoTable->CommunicateUserMessage(EventType::UserEjected, $player->id, $text);

						$player->currentCasinoTableId = null;
						$player->currentSeatNumber = null;
						$player->waitStartDateTime = null;
						$player->reservedSeatNumber = null;
						$player->buyIn = null;
						$player->Update();
						// reset hidden and visible cards;
						$hidden = new PlayerHiddenCards($player->id, null, null);
						$hidden->ResetSleeve(true);
						$visibles = new PlayerVisibleCards($player->id, $gameSessionId, null, null);
						$visibles->ResetVisible(true);

						$q = QueueManager::GetPlayerQueue($player->id, $ch);
						QueueManager::DeleteQueue($q);
					}
				}
				PlayerActiveItem::DeleteForSession($gameSessionId);

				$casinoTable->currentGameSessionId = null;
				$casinoTable->sessionStartDateTime = null;
				$casinoTable->UpdateSessionForCasinoTable();
				$gameSession = new GameSession($gameSessionId, $requestingPlayerId);
				$gameSession->Delete();
				$q = QueueManager::GetGameSessionQueue($gameSessionId, $ch);
				QueueManager::DeleteQueue($q);
			}
			if ($isPractice) {
				PokerCoordinator::EndPracticeSession($gameSessionId, $requestingPlayerId);
			}
		}
	}

	function Delete() {
		$where = "Id = $this->id";
		$event = "DELETE FROM GameSession WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($this));
	}

}

?>
