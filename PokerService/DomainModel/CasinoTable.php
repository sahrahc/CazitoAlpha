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
	private $history;

	public function __construct($id) {
		$this->history = Logger::getLogger(__CLASS__);

		$this->id = $id;
	}

	/**
	 * Retrieves the casino table given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
	 * @global type $dateTimeFormat
	 * @global type $sessionExpiration
	 * @param type $cTableId
	 * @return CasinoTable 
	 */
	public static function GetCasinoTable($cTableId) {
		if (is_null($cTableId) || $cTableId == "") {
			return null;
		}
		$query = "SELECT * FROM CasinoTable WHERE Id = $cTableId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		return self::mapCasinoTableSqlRow($result);
	}

	/**
	 * Retrieves the casino table given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
	 * @global type $dateTimeFormat
	 * @global type $sessionExpiration
	 * @param type $cTableId
	 * @return CasinoTable 
	 */
	public static function GetCasinoTableByCode($tableCode) {
		if (is_null($tableCode) || $tableCode == "") {
			return null;
		}
		$query = "SELECT * FROM CasinoTable WHERE Code = '$tableCode'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		return self::mapCasinoTableSqlRow($result);
	}

	/**
	 * Given a game session, get the casino table entity.
	 * @param int $gSessionId
	 * @return CasinoTable
	 */
	public static function GetCasinoTableForSession($gSessionId) {
		$query = "SELECT * FROM CasinoTable
                WHERE CurrentGameSessionId = $gSessionId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		return self::mapCasinoTableSqlRow($result);
	}

	private static function mapCasinoTableSqlRow($result) {
		global $dateTimeFormat;
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$obj = new CasinoTable((int) $row["Id"]);
		$obj->name = $row["Name"];
		$obj->code = $row["Code"];
		$obj->tableMinimum = (int) $row["TableMinimum"];
		$obj->numberSeats = (int) $row["NumberSeats"];
		$obj->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
		$obj->currentGameSessionId = is_null($row["CurrentGameSessionId"]) ? null : (int) $row["CurrentGameSessionId"];
		$obj->sessionStartDateTime = is_null($row["SessionStartDateTime"]) ? null : DateTime::createFromFormat($dateTimeFormat, $row["SessionStartDateTime"]);
		return $obj;
	}

	/*	 * ***************************************************************************** */
	/* gaming */

	/**
	 * Calculates the amount of the first and second blind based on table minimums and current game play.
	 * @return array{int, int}
	 */
	public function FindBlindBetAmounts() {
		global $log;
		if (is_null($this->tableMinimum)) {
			throw new Exception("Empty casino table, cannot find blind bets");
		}
		$blind1 = $this->tableMinimum / 2;
		$blind2 = $blind1 * 2;
		//$log->Debug(__FUNCTION__ . "Blind 1 is $blind1 and Blind2 is $blind2");
		return array($blind1, $blind2);
	}

	public function CreateLiveSession($playerId) {
		$statusDT = Context::GetStatusDTString();
		$nextSessionId = getNextSequence('GameSession', 'Id');
		$gameSession = new GameSession($nextSessionId, $playerId);
		$gameSession->startDateTime = Context::GetStatusDT();

		$vars = "Id, RequestingPlayerId, TableMinimum, NumberSeats, StartDateTime, IsPractice";
		$values = "$nextSessionId, $playerId, $this->tableMinimum, $this->numberSeats, '$statusDT', 0";
		$event = "INSERT INTO GameSession ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");

		$this->currentGameSessionId = $nextSessionId;
		$this->sessionStartDateTime = $gameSession->startDateTime;
		$this->UpdateSessionForCasinoTable();
		return $gameSession;
	}

	/**
	 * Creates a new game session on the casino table.
	 * Note that casinoTable is updated
	 * @param timestamp $statusDT
	 * @return GameSession
	 */
	public function ResetGameSession($playerId) {
		// set old session to expired
		$statusDT = Context::GetStatusDT();
		$oldGameSession = GameSession::GetGameSession($this->currentGameSessionId);
		$oldGameSession->Delete();

		$gameSession = $this->CreateLiveSession($playerId);
		$this->currentGameSessionId = $gameSession->id;
		$this->sessionStartDateTime = $statusDT;
		$this->lastUpdateDateTime = $statusDT;
		$this->UpdateSessionForCasinoTable();

		return $gameSession;
	}

	/*	 * ***************************************************************************** */

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

	public function UpdateSessionForCasinoTable() {
		global $dateTimeFormat;
		
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		$gameSessionId = $this->currentGameSessionId;
		if (is_null($gameSessionId)) {
			$gameSessionId = 'null';
		}
		$sessionStartDT = 'null';
		if ($this->sessionStartDateTime !== null) {
			$sessionStartDT = '"' . $this->sessionStartDateTime->format($dateTimeFormat) . '"';
		}
		$vars = "CurrentGameSessionId, SessionStartDateTime, LastUpdateDateTime";
		$values = "$gameSessionId, $sessionStartDT, $statusDTQ";
		$where = "Id = $this->id";
		$event = "UPDATE CasinoTable SET CurrentGameSessionId = $gameSessionId, "
				. "SessionStartDateTime = $sessionStartDT, "
				. "LastUpdateDateTime = $statusDTQ WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * Get or create a casino table for a user when he joins a table. Getting or creating
	 * separates the logic for checking if a casino table exists from a user joining a table.
	 * When a new casino table is created, a session is automatically created as well.
	 * @global type $numberSeats
	 * @global type $defaultTableMin
	 * @param type $cTableId
	 * @param type $betSize
	 * @param type $statusDTQ
	 * @return CasinoTable 
	 */
	public function CreateCasinoTable($tableName, $tableCode, $betSize, $numberSeats, $playerId) {
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";

		// resetting the id
		$nextTableId = getNextSequence('CasinoTable', 'Id');
		$this->id = $nextTableId;
		$this->name = $tableName;
		$this->code = $tableCode;
		$this->tableMinimum = $betSize;
		$this->numberSeats = $numberSeats;
		$this->lastUpdateDateTime = Context::GetStatusDT();
		$gameSession = $this->CreateLiveSession($playerId);

		// TODO: need business rules for setting table minimums.
		$vars = "Id, Name, Code, TableMinimum, NumberSeats, LastUpdateDateTime, CurrentGameSessionId, "
				. "SessionStartDateTime";
		$values = "$nextTableId, '$tableName', '$tableCode', $betSize, $numberSeats, $statusDTQ, "
				. " $gameSession->id, $statusDTQ";
		$event = "INSERT INTO CasinoTable ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");

		$this->currentGameSessionId = $gameSession->id;
		$this->sessionStartDateTime = Context::GetStatusDT();

		// creates queue for game session
		QueueManager::addGameSessionQueue($gameSession->id, Context::GetQCh());
	}

}

?>
