<?php

/* * ************************************************************************************* */

class PlayerInstance {

	public $playerId;
	public $gameInstanceId;
	public $isVirtual;
	public $gameSessionId;
	public $lastUpdateDateTime;
	public $seatNumber;
	public $turnNumber;
	public $status;
	public $currentStake;
	public $lastPlayAmount;
	public $lastPlayInstanceNumber;
	public $numberTimeOuts;
	private $history = null;

	public function __construct() {
		$this->history = Logger::getLogger(__CLASS__);
	}

	/**
	 * Get a player's instance given the player and instance identifiers.
	 * @param type $gInstId
	 * @param type $pId
	 * @return PlayerInstance
	 */
	public static function GetPlayerInstance($id, $pId, $isSessionId = false) {
		if ($isSessionId) {
			$query = "SELECT *
                FROM PlayerState
                WHERE GameSessionId = $id and PlayerId = $pId 
                    ORDER BY SeatNumber";
		} else {
			$query = "SELECT *
                FROM PlayerState
                WHERE GameInstanceId = $id and PlayerId = $pId 
                    ORDER BY SeatNumber";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		// sorted by seat number
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$playerStatus = new PlayerInstance();
		$playerStatus->mapRow($row);

		return $playerStatus;
	}

	public static function GetPlayerInstanceByTurn($gameInstanceId, $turnNumber) {
		$query = "SELECT * FROM PlayerState 
                WHERE GameInstanceId = $gameInstanceId AND TurnNumber = $turnNumber";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		// sorted by seat number
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$playerStatus = new PlayerInstance();
		$playerStatus->mapRow($row);

		return $playerStatus;
	}

	/**
	 * Get all the players instance setup and status for a game instance
	 * SORTING IS IMPORTANT!
	 * 1) get next dealer seat number
	 * 2) get max seat number
	 * 3) add seatnumber to max
	 * 4) #3 modulus #1+max (next dealer)
	 * @param int gInstId
	 * @return PlayerInstance[]
	 */
	public static function GetPlayerInstancesForNewGame($gameSessionId, $nextDealerSeat) {
		$maxSeatNumberResult = executeSQL("SELECT max(SeatNumber) MaxSeat
			FROM PlayerState
            WHERE GameSessionId = $gameSessionId", __CLASS__ . "-" . __FUNCTION__);
		$maxSeatNumberRow = mysql_fetch_array($maxSeatNumberResult, MYSQL_NUM);
		$maxSeatNumber = $maxSeatNumberRow[0] + 1;
		$divisor = $nextDealerSeat + $maxSeatNumber;
		$query = "SELECT ps.*
                FROM PlayerState ps
                WHERE GameSessionId = $gameSessionId "
				. "ORDER BY (SeatNumber + $maxSeatNumber) % $divisor";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$playerInstances = null;
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerInstances[$i] = new PlayerInstance();
			$playerInstances[$i]->mapRow($row);
			$i++;
		}
		return $playerInstances;
	}

	/**
	 * Get all the players instance setup and status for a game instance. Only active players are
	 * relevant
	 * @param int gInstId
	 * @return PlayerInstance[]
	 */
	public static function GetPlayerInstancesForGame($id, $isSessionId = false) {
		$left = PlayerStatusType::LEFT;
		if ($isSessionId) {
			$query = "SELECT *
                FROM PlayerState
                WHERE GameSessionId = $id AND Status != '$left' ORDER BY TurnNumber";
 //               WHERE GameSessionId = $id ORDER BY TurnNumber";
		} else {
			$query = "SELECT *
                FROM PlayerState
                WHERE GameInstanceId = $id AND Status != '$left' ORDER BY TurnNumber";
//                WHERE GameInstanceId = $id ORDER BY TurnNumber";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$playerInstances = null;
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerInstances[$i] = new PlayerInstance();
			$playerInstances[$i]->mapRow($row);
			$i++;
		}
		return $playerInstances;
	}

	/**
	 * Get all the players instance setup and status for a game instance
	 * @param int gInstId
	 * @return PlayerInstance[]
	 */
	public static function GetPlayersWithStates($gameInstanceId, $casinoTableId) {
		$left = "'" . PlayerStatusType::LEFT . "'";
		if ($casinoTableId === null) {
			$query = "SELECT ps.*, p.Id, p.Name, p.ImageUrl, p.CurrentSeatNumber, p.BuyIn 
                FROM Player p LEFT JOIN PlayerState ps ON p.Id = ps.PlayerId
                WHERE ps.GameInstanceId = $gameInstanceId "
					. "ORDER BY IFNULL(TurnNumber, 20), IFNULL(p.CurrentSeatNumber, 20)";
		} else {
			// second condition is for users who left and came back.
			$query = "SELECT ps.*, p.Id, p.Name, p.ImageUrl, p.CurrentSeatNumber, p.BuyIn
                FROM Player p LEFT JOIN CasinoTable c ON p.CurrentCasinoTableId = c.Id
				LEFT JOIN PlayerState ps ON p.Id = ps.PlayerId AND c.CurrentGameSessionId = ps.GameSessionId 
                WHERE (ps.GameInstanceId = $gameInstanceId and ps.Status != $left) OR "
					. "(ps.GameInstanceId = $gameInstanceId and ps.Status = $left AND c.Id is not null) OR "
					. "(ps.GameInstanceId is null AND p.CurrentCasinoTableId = $casinoTableId) "
					. "ORDER BY IFNULL(TurnNumber, 20), IFNULL(p.CurrentSeatNumber, 20)";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$playerInstances = null;
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerInstances[$i] = new PlayerInstance();
			$playerInstances[$i]->mapRow($row);
			// player identity part
			$playerInstances[$i]->playerId = $row["Id"];
			$playerInstances[$i]->playerName = $row["Name"];
			$playerInstances[$i]->playerImageUrl = $row["ImageUrl"];
			$playerInstances[$i]->seatNumber = $row["CurrentSeatNumber"];
			if (is_null($playerInstances[$i]->status)) {
				$playerInstances[$i]->status = PlayerStatusType::SEATED;
			}
			if (is_null($playerInstances[$i]->currentStake)) {
				$playerInstances[$i]->currentStake = $row["BuyIn"];
			}
			$i++;
		}
		return $playerInstances;
	}

	/**
	 * Create new player state records for players who got a seat
	 * @param type $gameInstance
	 * @return null|\PlayerInstance
	 */
	public static function GetNewPlayerStatesOnSession($gameInstance) {
		$query = "SELECT p.Id PlayerId, 
                p.IsVirtual, p.CurrentSeatNumber, p.BuyIn
            FROM Player p 
            INNER JOIN CasinoTable c ON p.CurrentCasinoTableId = c.Id
             LEFT JOIN PlayerState s ON p.Id = s.PlayerId AND s.GameSessionId = c.CurrentGameSessionId
            WHERE c.CurrentGameSessionId = $gameInstance->gameSessionId 
              AND p.CurrentSeatNumber IS NOT NULL AND s.PlayerId IS NULL
                ORDER BY IFNULL(p.CurrentSeatNumber, 20)";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}
		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerStates[$i] = new PlayerInstance();
			$playerStates[$i]->playerId = (int) $row["PlayerId"];
			$playerStates[$i]->gameInstanceId = $gameInstance->id;
			$playerStates[$i]->isVirtual = (int) $row["IsVirtual"];
			$playerStates[$i]->gameSessionId = (int) $gameInstance->gameSessionId;
			$playerStates[$i]->lastUpdateDateTime = Context::GetStatusDT();
			$playerStates[$i]->seatNumber = is_null($row["CurrentSeatNumber"]) ? null : (int) $row["CurrentSeatNumber"];
			// don't set turnNumber,
			$playerStates[$i]->status = PlayerStatusType::WAITING;
			// TODO: is buy in the stake?
			$playerStates[$i]->currentStake = is_null($row["BuyIn"]) ? null : (int) $row["BuyIn"];
			$playerStates[$i]->lastPlayAmount = 0;
			$playerStates[$i]->lastPlayInstanceNumber = 0;
			$playerStates[$i]->numberTimeOuts = 0;
			$i++;
		}
		return $playerStates;
	}

	private function mapRow($row) {
		global $dateTimeFormat;
		$this->playerId = (int) $row["PlayerId"];
		$this->gameInstanceId = (int) $row["GameInstanceId"];
		$this->isVirtual = (int) $row["IsVirtual"];
		$this->gameSessionId = (int) $row["GameSessionId"];
		$this->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
		//(int) $row["SeatNumber"];
		$this->seatNumber = is_null($row["SeatNumber"]) ? null : (int) $row["SeatNumber"];
		$this->turnNumber = $row["TurnNumber"] == null ? null : (int) $row["TurnNumber"];
		$this->status = $row["Status"];
		$this->currentStake = $row["CurrentStake"] == null ? null : (int) $row["CurrentStake"];
		$this->lastPlayAmount = $row["LastPlayAmount"] == null ? null : (int) $row["LastPlayAmount"];
		$this->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"] == null ? null : (int) $row["LastPlayInstanceNumber"];
		$this->numberTimeOuts = $row["NumberTimeOuts"] == null ? null : (int) $row["NumberTimeOuts"];
	}

	/*	 * ************************************************************************** */

	public function Insert() {
		global $dateTimeFormat;
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		// properties that can be null
		$gameInstanceId = is_null($this->gameInstanceId) ? 'null' : $this->gameInstanceId;
		$turnNumber = is_null($this->turnNumber) ? 'null' : $this->turnNumber;
		$lastPlayAmount = is_null($this->lastPlayAmount) ? 'null' : $this->lastPlayAmount;
		$lastPlayNumber = is_null($this->lastPlayInstanceNumber) ? 'null' : $this->lastPlayInstanceNumber;
		$numberTimeOuts = is_null($this->numberTimeOuts) ? 'null' : $this->numberTimeOuts;

		$vars = "PlayerId, GameInstanceId, IsVirtual, GameSessionId, LastUpdateDateTime, SeatNumber, "
                . "TurnNumber, Status, CurrentStake, LastPlayAmount, LastPlayInstanceNumber, " 
				. "NumberTimeOuts";
		$values = "$this->playerId, $gameInstanceId, $this->isVirtual, $this->gameSessionId, " 
                . "$statusDTQ, $this->seatNumber, $turnNumber, '$this->status', $this->currentStake, "
                . "$lastPlayAmount, $lastPlayNumber, $numberTimeOuts";
		$event = "INSERT INTO PlayerState ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	public function Delete() {
		$where = "PlayerId = $this->playerId AND GameInstanceId = $this->gameInstanceId";
		$event = "DELETE FROM PlayerState WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($this));
	}

	public function Update() {
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		$stake = is_null($this->currentStake) ? 0 : $this->currentStake;
		// no blind bets yet 
		$lastPlayAmount = $this->lastPlayAmount;
		if (is_null($lastPlayAmount)) {
			$lastPlayAmount = 0;
		}
		$lastPlayInstanceNumber = $this->lastPlayInstanceNumber;
		if (is_null($lastPlayInstanceNumber)) {
			$lastPlayInstanceNumber = 0;
		}
		$numberTimeOuts = $this->numberTimeOuts;
		if (is_null($numberTimeOuts)) {
			$numberTimeOuts = 0;
		}
		$turnNumber = $this->turnNumber;
		if (is_null($turnNumber)) {
			$turnNumber = 0;
		}
		$vars = "LastUpdateDateTime, GameInstanceId, TurnNumber, Status, CurrentStake, "
				. "LastPlayAmount, LastPlayInstanceNumber, NumberTimeOuts";
		$values = "$statusDTQ, $this->gameInstanceId, $turnNumber, '$this->status', $stake, "
			. "$lastPlayAmount, $lastPlayInstanceNumber, $numberTimeOuts";
		$where = "PlayerId = $this->playerId AND GameSessionId = $this->gameSessionId";
		$event = "UPDATE PlayerState set LastUpdateDateTime = $statusDTQ, "
				. "GameInstanceId = $this->gameInstanceId, "
				. "TurnNumber = $turnNumber, "
				. "Status = '$this->status', "
				. "CurrentStake = $stake, "
				. "LastPlayAmount = $lastPlayAmount, "
				. "LastPlayInstanceNumber = $lastPlayInstanceNumber, "
				. "NumberTimeOuts = $numberTimeOuts WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * Preparing for obsolete instance to be deleted
	 * @param type $endString
	 */
	public static function DeleteExpired($endString) {
		$query = "SELECT * FROM PlayerState WHERE GameInstanceId in
            (SELECT ID FROM GameInstance WHERE LastUpdateDateTime <= '$endString')";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$playerInstances = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerInstances[$i] = new PlayerInstance();
			$playerInstances[$i]->mapRow($row);
			$i++;
		}
		if (!is_null($playerInstances)) {
			foreach ($playerInstances as $playerInstance) {
				$playerInstance->Delete();
			}
		}
	}

	/**
	 * Delete all PlayerState records for players who left
	 * @param type $gameSessionId
	 */
	public static function DeleteDeparted($gameSessionId) {
		$leftStatus = PlayerStatusType::LEFT;
		$query = "SELECT * FROM PlayerState
                WHERE GameSessionId = $gameSessionId AND Status = '$leftStatus'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);

		// only the last record for every game instance id is processed
		// this won't be needed when only one move is stored (out of database)
		$i = 0;
		$playerInstances = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerInstances[$i] = new PlayerInstance();
			$playerInstances[$i]->mapRow($row);
			$i++;
		}
		if (!is_null($playerInstances)) {
			$ch = Context::GetQCh();
			foreach ($playerInstances as $player) {
				$q = QueueManager::GetPlayerQueue($player->playerId, $ch);
				QueueManager::DeleteQueue($q);
				$player->Delete();
			}
		}
	}

}

?>
