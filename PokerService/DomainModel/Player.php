<?php

/* * ************************************************************************************** */

/**
 * A player entity between the time he logs in and before he starts playing
 * in games.
 */
class Player {

	public $id;
	public $name;
	public $imageUrl;
	public $isVirtual;
	public $lastUpdateDateTime;
	public $currentCasinoTableId;
	public $currentSeatNumber;
	public $reservedSeatNumber;
	public $waitStartDateTime;
	public $buyIn;
	private $history;

	/*
	 * Use constructor when first logged in
	 */

	function __construct($id, $name, $imageUrl, $isVirtual) {
		$this->history = Logger::getLogger(__CLASS__);

		$this->id = $id == null ? null : (int) $id;
		$this->name = $name;
		$this->imageUrl = $imageUrl;
		$this->isVirtual = $isVirtual == null ? null : (int) $isVirtual;
	}

	public static function GetPlayersForCasinoTable($cTableId) {
		global $dateTimeFormat;
		$query = "SELECT * FROM Player WHERE CurrentCasinoTableId = $cTableId
            ORDER BY CurrentSeatNumber";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$players[$i] = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
			$players[$i]->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
			$players[$i]->casinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
			$players[$i]->currentSeatNumber = $row["CurrentSeatNumber"] == null ? null : (int) $row["CurrentSeatNumber"];
			$players[$i]->reservedSeatNumber = $row["ReservedSeatNumber"] == null ? null : (int) $row["ReservedSeatNumber"];
			$players[$i]->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
			$players[$i]->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];

			$i++;
		}
		return $players;
	}

	/*	 * ********************************************************************************* */

	/*	 * ********************************************************************************* */

	/**
	 * Retrieve a player given the player id or null if not found. Exception handling in that case by calling function.
	 * @param int $playerId
	 * @return Player, throws exception if not found
	 */
	public static function GetPlayer($playerId) {
		global $dateTimeFormat;

		$query = "SELECT * FROM Player WHERE Id = $playerId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);

		if (mysql_num_rows($result) == 0) {
			throw new Exception("Invalid user id");
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
		$obj->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
		$obj->currentCasinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
		$obj->currentSeatNumber = $row["CurrentSeatNumber"] == null ? null : (int) $row["CurrentSeatNumber"];
		$obj->reservedSeatNumber = $row["ReservedSeatNumber"] == null ? null : (int) $row["ReservedSeatNumber"];
		$obj->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
		$obj->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];

		return $obj;
	}

	/**
	 *
	 * @param type $playerName
	 * @return Player
	 */
	public static function GetPlayerByName($playerName) {
		global $dateTimeFormat;

		$query = "SELECT * FROM Player WHERE Name = '$playerName'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$player = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
			$player->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
			$player->currentCasinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
			$player->currentSeatNumber = $row['CurrentSeatNumber'] == null ? null : (int) $row['CurrentSeatNumber'];
			$player->reservedSeatNumber = $row['ReservedSeatNumber'] == null ? null : (int) $row['ReservedSeatNumber'];
			$player->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
			$player->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];
			return $player;
		}
		return null;
	}

	/**
	 * Get a player by player name or create one if now found. If the player name is Guest,
	 * then append the identifier to Guest to make it unique. This get or create operation separates the login logic from the player management logic.
	 * @global type $defaultTableMin
	 * @param type $casinoTableId
	 * @param string $playerName
	 * @param type $statusDT
	 * @return Player
	 */
	public static function GetOrCreatePlayer($playerName) {
		$player = null;
		if ($playerName != 'Guest') {
			$player = self::GetPlayerByName($playerName);
		}
		if (is_null($player)) {
			$player = Player::CreatePlayer($playerName);
		}
		return $player;
	}

	/**
	 * Given a game instance for a practice session, return the player
	 * @param gInstId
	 * @return \Player
	 * @throws Exception
	 */
	public static function GetPracticePlayer($gInstId) {
		global $dateTimeFormat;

		$query = "SELECT p.* FROM Player p
            INNER JOIN PlayerState s on p.id = s.PlayerId
            INNER JOIN GameSession g on g.id = s.GameSessionId
            WHERE g.IsPractice = 1 AND s.GameInstanceId = $gInstId
                ORDER BY p.CurrentSeatNumber";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			throw new Exception("Invalid game instance id");
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
		$obj->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
		/* $obj->casinoTableId = $row["CurrentCasinoTableId"];
		  $obj->currentSeatNumber = $row["CurrentSeatNumber"];
		  $obj->reservedSeatNumber = $row["ReservedSeatNumber"];
		  $obj->waitStartDateTime = $row["WaitStartDateTime"];
		 */
		$obj->buyIn = $row["BuyIn"];

		return $obj;
	}

	/**
	 * Create a new player - returns a DTO.
	 * @global type $defaultAvatarUrl
	 * @param string $playerName
	 * @return Player
	 */
	public static function CreatePlayer($playerName) {
		global $defaultAvatarUrl;
		$nextPlayerId = getNextSequence('Player', 'Id');
		if ($playerName == 'Guest') {
			$playerName = 'Guest' . $nextPlayerId;
		}
		$imageUrl = $defaultAvatarUrl;

		$player = new Player($nextPlayerId, $playerName, $imageUrl, 0);
		$player->Insert();
		return $player;
	}

	public function Insert() {
		$playerName = $this->name;
		$imageUrl = $this->imageUrl;
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		$vars = "Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId, CurrentSeatNumber, BuyIn, "
				. "WaitStartDateTime, LastUpdateDateTime";
		$values = "$this->id, 0, '$playerName', '$imageUrl', null, null, null, null, $statusDTQ";
		$event = "INSERT INTO Player ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	/**
	 * Creates a practice virtual player
	 * @global type $defaultAvatarUrl
	 * @global type $defaultTableMin
	 * @global type $buyInMultiplier
	 * @param type $playerName
	 * @param type $seatNum
	 * @param type $statusDTQ
	 * @return Player
	 */
	public function CreatePracticePlayer($seatNum) {
		global $defaultAvatarUrl;
		global $defaultTableMin;
		global $buyInMultiplier;

		$nextPlayerId = getNextSequence('Player', 'Id');
		$this->id = $nextPlayerId;
		$this->imageUrl = $defaultAvatarUrl;
		$this->buyIn = $defaultTableMin * $buyInMultiplier;
		$this->currentSeatNumber = $seatNum;
		$this->lastUpdateDateTime = Context::GetStatusDT();

		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		$vars = "Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId, CurrentSeatNumber, BuyIn, "
				. "LastUpdateDateTime, WaitStartDateTime";
		$values = "$nextPlayerId, 1, '$this->name', '$this->imageUrl', null, $seatNum, $this->buyIn, "
                . "$statusDTQ, null";
		$event = "INSERT INTO Player ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	public function Update() {

		$casinoTableId = $this->currentCasinoTableId;
		if (is_null($casinoTableId)) {
			$casinoTableId = 'null';
		}
		$seatValue = $this->currentSeatNumber;
		if (is_null($seatValue)) {
			$seatValue = 'null';
		}
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		if (is_null($this->waitStartDateTime)) {
			$waitingStartDTQ = 'null';
		} else {
			$waitingStartDTQ = $statusDTQ;
		}
		$reservedSeat = $this->reservedSeatNumber;
		if (is_null($reservedSeat)) {
			$reservedSeat = 'null';
		}
		$buyIn = $this->buyIn;
		if (is_null($buyIn)) {
			$buyIn = 'null';
		}
		$vars = "LastUpdateDateTime, CurrentCasinoTableId, CurrentSeatNumber, ReservedSeatNumber, "
				. "WaitStartDateTime, BuyIn";
		$values = "$statusDTQ, $casinoTableId, $seatValue, $reservedSeat, $waitingStartDTQ";
		$where = "Id = $this->id";
		$event = "UPDATE Player SET LastUpdateDateTime = $statusDTQ, "
				. "CurrentCasinoTableId = $casinoTableId, "
				. "CurrentSeatNumber = $seatValue, "
				. "ReservedSeatNumber = $reservedSeat, "
				. "WaitStartDateTime = $waitingStartDTQ, "
				. "BuyIn = $buyIn WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * Converts a reserved seat into a current seat.
	 * TODO: move to coordinator
	 * @param type $seatNum
	 * @param type $pId
	 */
	public function UpdatePlayerSeat($seatNum, $isReserved = false) {

		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		if ($isReserved) {
			$this->reservedSeatNumber = $seatNum;
			$vars = "LastUpdateDateTime, ReservedSeatNumber, CurrentSeatNumber";
			$values = "$statusDTQ, $seatNum, null";
			$where = "Id = $this->id";
			$event = "UPDATE Player SET LastUpdateDateTime = $statusDTQ, "
					. "ReservedSeatNumber = $seatNum, "
					. "CurrentSeatNumber = null WHERE $where";
			$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
			$log = $vars . " -TO- " . $values . " -WHERE- $where";
			$this->history->info("UPDATED " . $eventCount . ": $log");
			return;
		}
		$this->currentSeatNumber = $seatNum;
		$vars = "LastUpdateDateTime, CurrentSeatNumber, ReservedSeatNumber";
		$values = "$statusDTQ, $seatNum, null";
		$where = "Id = $this->id";
		$event = "UPDATE Player SET LastUpdateDateTime = $statusDTQ, "
				. "CurrentSeatNumber = $seatNum, "
				. "ReservedSeatNumber = null WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public function Delete() {
		$where = "Id = $this->id";
		$event = "DELETE FROM Player WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($this));
	}

}

?>
