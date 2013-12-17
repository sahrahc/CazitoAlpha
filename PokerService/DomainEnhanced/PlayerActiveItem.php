<?php

/*
 */

class PlayerActiveItem {

	public $id;
	public $playerId;
	public $gameSessionId;
	public $gameInstanceId;
	public $itemType;
	public $startDateTime;
	public $endDateTime;
	public $lockEndDateTime;
	public $otherPlayerId;
	// value sent to browser, in synch with lockEndDateTime except for 
	// UseCard, tuck in/tuck out, if no more cards are available
	public $isLocked;
	public $isNotified;
	public $numberCards;
	private $history;

	function __construct($playerId, $gSessionId, $itemType, $timeOut = null) {
		$this->history = Logger::getLogger(__CLASS__);
		$this->isLocked = 0;
		if ($timeOut != null) {
			$lockEndDateTime = Context::GetStatusDT();
			$lockEndDateTime->add(new DateInterval($timeOut));
			$this->lockEndDateTime = $lockEndDateTime;
			$this->isLocked = 1; // lock out period
		}
		$this->playerId = $playerId;
		$this->gameSessionId = $gSessionId;
		$this->itemType = $itemType;
		$this->numberCards = null;
	}

	function SetEndDate($timeOut) {
		$endDateTime = Context::GetStatusDT();
		if ($timeOut != '') {
			$endDateTime->add(new DateInterval($timeOut));
		}
		$this->endDateTime = $endDateTime;
	}

	function RecordItemUse() {
		global $dateTimeFormat;
		/* validate that the record can be inserted first */
		$this->startDateTime = Context::GetStatusDT();
		$startDT = $this->startDateTime->format($dateTimeFormat);
		/*
		  if (!$this->IsItemUnlockedForPlayer()) {
		  $this->log->warn(__FUNCTION__ . "$this->playerId attempted to reset $this->itemType before it expired");
		  return;
		  } */
		$lockString = 'null';
		if (!is_null($this->lockEndDateTime)) {
			$lockString = "'" . $this->lockEndDateTime->format($dateTimeFormat) . "'";
		}
		$endString = 'null';
		if (!is_null($this->endDateTime)) {
			$endString = "'" . $this->endDateTime->format($dateTimeFormat) . "'";
		}
		$numberCards = $this->numberCards;
		if (is_null($numberCards)) {
			$numberCards = 'null';
			;
		}
		$instanceId = $this->gameInstanceId;
		if (is_null($instanceId)) {
			$instanceId = 'null';
		}
		$sessionId = $this->gameSessionId;
		if (is_null($sessionId)) {
			$sessionId = 'null';
		}
		$otherPlayerId = $this->otherPlayerId;
		if (is_null($otherPlayerId)) {
			$otherPlayerId = 'null';
		}
		$nextItemId = getNextSequence('PlayerActiveItem', 'Id');
		$this->id = $nextItemId;

		$vars = "Id, PlayerId, GameSessionId, GameInstanceId, ItemType, "
				. "StartDateTime, EndDateTime, LockEndDateTime, IsLocked, NumberCards, OtherPlayerId";
		$values = "$nextItemId, $this->playerId, $sessionId, $instanceId, '$this->itemType', "
				. "'$startDT', $endString, $lockString, $this->isLocked, $numberCards, $otherPlayerId";
		$event = "INSERT INTO PlayerActiveItem ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	public function UpdateItemEndLock() {
		global $dateTimeFormat;
		$endDateTime = 'null';
		;
		if ($this->endDateTime != null) {
			$endDateTime = "'" . $this->endDateTime->format($dateTimeFormat) . "'";
		}
		$lockEndDT = 'null';
		if ($this->lockEndDateTime != null) {
			$lockEndDT = "'" . $this->lockEndDateTime->format($dateTimeFormat) . "'";
		}
		$vars = "IsLocked, LockEndDateTime, EndDateTime";
		$values = "$this->isLocked, $lockEndDT, $endDateTime";
		if (is_null($this->id)) {
			$where = "PlayerId = $this->playerId AND GameSessionId = $this->gameSessionId "
					. "AND ItemType = '$this->itemType' ";
		} else {
			$where = "id = $this->id";
		}
		$event = "UPDATE PlayerActiveItem SET IsLocked = $this->isLocked, "
				. "LockEndDateTime = $lockEndDT, "
				. "EndDateTime = $endDateTime WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public function UpdateItemSession() {
		$statusDTString = Context::GetStatusDTString();
		$vars = "GameSessionId";
		$values = $this->gameSessionId;
		if (is_null($this->id)) {
			$where = "PlayerId = $this->playerId AND GameSessionId is null "
					. " AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') "
					. " AND ItemType = '$this->itemType' ";
		} else {
			$where = "id = $this->id";
		}
		$event = "UPDATE PlayerActiveItem SET GameSessionId = $this->gameSessionId "
				. " WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public function UpdateIsNotified() {
		$vars = "IsNotified";
		$values = "1";
		if (is_null($this->id)) {
			$where = "PlayerId = $this->playerId AND GameSessionId = $this->gameSessionId "
					. "AND ItemType = '$this->itemType' ";
		} else {
			$where = "id = $this->id";
		}
		$event = "UPDATE PlayerActiveItem SET IsNotified = 1 WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	function SetInstanceItemToInactive() {
		$statusDateTime = Context::GetStatusDTString();
		// TODO: separate this into update function
		$vars = "EndDateTime";
		$values = $statusDateTime;
		if (is_null($this->id)) {
			$where = "PlayerId = $this->playerId AND ItemType = '$this->itemType' "
					. "AND GameInstanceId = $this->gameInstanceId";
		} else {
			$where = "id = $this->id";
		}
		$event = "UPDATE PlayerActiveItem SET EndDateTime = '$statusDateTime' WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public static function DeleteForSession($gameSessionId) {
		$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId = $gameSessionId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
			$hiddenList = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$item = new PlayerActiveItem($row['PlayerId'], $gameSessionId, null, null);
			$item->id = $row['Id'];
			$item->Delete();
		}
		return $hiddenList;
	}

	function Delete() {
		if (is_null($this->id)) {
			$where = "PlayerId = $this->playerId AND itemType = '$this->itemType' "
					. "AND GameSessionId = $this->gameSessionId";
		} else {
			$where = "Id = $this->id";
		}
		$event = "DELETE FROM PlayerActiveItem WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($this));
	}

	public function IsItemUnlockedForPlayer($isInstanceId = false) {
		$statusDT = Context::GetStatusDTString();
		if ($isInstanceId) {
			$query = "SELECT * FROM PlayerActiveItem "
					. "WHERE PlayerId = $this->playerId "
					. "AND ItemType = '$this->itemType' and LockEndDateTime >= '$statusDT' "
					. "AND GameInstanceId = $this->gameInstanceId";
		} else {
			$query = "SELECT * FROM PlayerActiveItem "
					. "WHERE PlayerId = $this->playerId "
					. "AND ItemType = '$this->itemType' "
					. "AND LockEndDateTime >= '$statusDT' "
					. "AND GameSessionId = $this->gameSessionId";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) > 0) {
			return false;
		}
		return true;
	}

	/**
	 * Public for testing only
	 * @param type $pId
	 * @param type $gSessionId
	 * @param type $itemType
	 * @return \PlayerActiveItem
	 */
	public function GetSavedPlayerItem() {
		//$statusDTString = Context::GetStatusDTString();
		if ($this->otherPlayerId != null) {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $this->gameSessionId AND PlayerId = $this->playerId AND ItemType = '$this->itemType' "
					. " AND OtherPlayerId > $this->otherPlayerId";
		} else {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $this->gameSessionId AND PlayerId = $this->playerId AND ItemType = '$this->itemType' ";
//				AND EndDateTime > '$statusDTString'
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$this->mapPlayerActiveItem($row);
		}
	}

	function mapPlayerActiveItem($row) {
		global $dateTimeFormat;
		$this->id = $row['Id'];
		$this->gameInstanceId = $row['GameInstanceId'];
		$this->startDateTime = DateTime::createFromFormat($dateTimeFormat, $row['StartDateTime']);
		$this->endDateTime = DateTime::createFromFormat($dateTimeFormat, $row['EndDateTime']);
		$this->lockEndDateTime = DateTime::createFromFormat($dateTimeFormat, $row['LockEndDateTime']);
		$this->isLocked = (int) $row['IsLocked'];
		$this->numberCards = (int) $row['NumberCards'];
		$this->otherPlayerId = (int) $row['OtherPlayerId'];
	}

}

?>
