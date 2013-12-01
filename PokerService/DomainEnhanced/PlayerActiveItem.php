<?php

/*
 */

class PlayerActiveItem {

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
	public $numberCards;
	private $log;

	function __construct($playerId, $gSessionId, $itemType, $timeOut = null) {
		$this->log = Logger::getLogger(__CLASS__);
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
			$lockString = "'" . $this->lockEndDateTime->format($dateTimeFormat). "'";
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
		executeSQL("INSERT INTO PlayerActiveItem (PlayerId, GameSessionId, GameInstanceId, ItemType,
            StartDateTime, EndDateTime, LockEndDateTime, IsLocked, NumberCards, OtherPlayerId)
            VALUES ($this->playerId, $sessionId, $instanceId, '$this->itemType',
                '$startDT', $endString, $lockString,
                $this->isLocked, $numberCards, $otherPlayerId)", __FUNCTION__ . "
                : Error inserting PlayerActiveItem for player id $this->playerId and
                item type $this->itemType");
	}

	public function UpdateItemEndLock() {
		global $dateTimeFormat;
		$endDateTime = 'null';;
		if ($this->endDateTime != null) {
			$endDateTime = "'" . $this->endDateTime->format($dateTimeFormat) . "'";
		}
		$lockEndDT = 'null';
		if ($this->lockEndDateTime != null) {
			$lockEndDT = "'" . $this->lockEndDateTime->format($dateTimeFormat) . "'";
		}
		executeSQL("UPDATE PlayerActiveItem SET IsLocked = $this->isLocked, "
				. "LockEndDateTime = $lockEndDT, "
				. "EndDateTime = $endDateTime "
				. "WHERE PlayerId = $this->playerId AND GameSessionId = $this->gameSessionId "
				. "AND ItemType = '$this->itemType' ", __FUNCTION__
				. ": Error updating PlayerActiveItem for player id $this->playerId and "
				. "item type $this->itemType and session $this->gameSessionId");
	}

	public function UpdateItemSession() {
		$statusDTString = Context::GetStatusDTString();
		executeSQL("UPDATE PlayerActiveItem SET GameSessionId = $this->gameSessionId "
				. " WHERE PlayerId = $this->playerId AND GameSessionId is null " 
				. " AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') "
				. " AND ItemType = '$this->itemType' ", __FUNCTION__
				. ": Error updating PlayerActiveItem session for player id $this->playerId and "
				. "item type $this->itemType and session $this->gameSessionId");
	}

	function SetInstanceItemToInactive() {
		$statusDateTime = Context::GetStatusDTString();
		// TODO: separate this into update function
		executeSQL("UPDATE PlayerActiveItem SET EndDateTime = '$statusDateTime' WHERE
                PlayerId = $this->playerId AND ItemType = '$this->itemType' "
				. "AND GameInstanceId = $this->gameInstanceId"
				, __FUNCTION__ . ": Error updating item to inactive for player $this->playerId
                    session $this->gameSessionId and item $this->itemType");
	}

	function Delete() {
		executeSQL("DELETE FROM PlayerActiveItem WHERE PlayerId = $this->playerId AND
                 itemType = '$this->itemType' and GameSessionId = 
                $this->gameSessionId", __FUNCTION__ . ": Error deleting 
                    (unlocking) item for player $this->playerId AND
                 itemType = '$this->itemType' and GameSessionId = $this->gameSessionId");
	}

	public function IsItemUnlockedForPlayer($isInstanceId = false) {
		$statusDT = Context::GetStatusDTString();
		if ($isInstanceId) {
			$result = executeSQL("SELECT * from PlayerActiveItem "
					. "WHERE PlayerId = $this->playerId "
					. "AND ItemType = '$this->itemType' and LockEndDateTime >= '$statusDT' "
					. "AND GameInstanceId = $this->gameInstanceId", __FUNCTION__
					. ": Error selecting PlayerActiveItem for player $this->playerId "
					. "AND item type $this->itemType");
		} else {
			$result = executeSQL("SELECT * from PlayerActiveItem "
					. "WHERE PlayerId = $this->playerId "
					. "AND ItemType = '$this->itemType' "
					. "AND LockEndDateTime >= '$statusDT' "
					. "AND GameSessionId = $this->gameSessionId", __FUNCTION__
					. ": Error selecting PlayerActiveItem for player $this->playerId "
					. "AND item type $this->itemType");
		}
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
			$result = executeSQL("SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $this->gameSessionId AND PlayerId = $this->playerId AND ItemType = '$this->itemType' "
					. " AND OtherPlayerId > $this->otherPlayerId"
					, __FUNCTION__ . "
                : Error selecting active item for player $this->playerId and session $this->gameSessionId and
                type $this->itemType");
		} else {
			$result = executeSQL("SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $this->gameSessionId AND PlayerId = $this->playerId AND ItemType = '$this->itemType' "
//				AND EndDateTime > '$statusDTString'
					, __FUNCTION__ . "
                : Error selecting active item for player $this->playerId and session $this->gameSessionId and
                type $this->itemType");
		}
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			$this->mapPlayerActiveItem($row);
		}
	}

	function mapPlayerActiveItem($row) {
		global $dateTimeFormat;
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
