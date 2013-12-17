<?php

/* Type: 
 * Primary Table: none
 * Description: cards that are visible to the user
 */

// TODO: fix to PlayerVisibleCards and remove static
class PlayerVisibleCards {

	public $playerId;
	public $itemType;
	public $gameSessionId;
	//public $cardCodeList;
	private $history;

	function __construct($playerId, $gameSessionId = null, $itemType = null, $cardCodeList = null) {
		$this->history = Logger::getLogger(__CLASS__);

		$this->playerId = $playerId;
		$this->itemType = $itemType;
		$this->gameSessionId = $gameSessionId;
		$this->cardCodeList = $cardCodeList;
	}

	public function ResetVisible($all = true) {
		if ($all) {
			$where = " PlayerId = $this->playerId";
		} else {
			$where = "PlayerId = $this->playerId AND ItemType = '$this->itemType'";
		}
		$event = "DELETE FROM PlayerVisibleCard WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": -WHERE- $where");
	}

	/**
	 * Made public for testing purposes only
	 * @global type $dateTimeFormat
	 * @param type $pId
	 * @param type $gameSessionId
	 * @param type $cardCodes
	 */
	public function SaveInstanceCards($instanceCardList) {
		$playerCardCodes = $this->GetSavedCardCodes();
		if (is_null($playerCardCodes)) {
			$newCards = $instanceCardList;
		} else {
			$newCards = array_diff($instanceCardList, $playerCardCodes);
		}

		// player active item
		//$activeItem = new PlayerActiveItem($this->playerId, $this->gameSessionId, $this->itemType);
		//$activeItem->RecordItemUse();
		// insert
		//$counter = count($playerCardCodes);
		foreach ($newCards as $cardCode) {
			$vars = "PlayerId, CardCode, GameSessionId, ItemType";
			$values = "$this->playerId, '$cardCode', $this->gameSessionId, '$this->itemType'";
			$event = "INSERT INTO PlayerVisibleCard ($vars) VALUES ($values)";
			$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
			$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
		}
	}

	public function SaveSingleCard($cardCode, $gameInstanceId) {
		$query = "SELECT CardCode FROM GameCard WHERE DeckPosition = $cardCode "
				. "AND GameInstanceId = $gameInstanceId";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);

		$vars = "PlayerId, CardCode, GameSessionId, ItemType";
		$values = "$this->playerId, '" . $row["CardCode"] . "', $this->gameSessionId, '$this->itemType'";
		$event = "INSERT INTO PlayerVisibleCard ($vars) values ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	/**
	 * Public for testing only
	 * @param type $pId
	 * @return type 
	 */
	public function GetSavedCardCodes() {
		$query = "SELECT CardCode FROM PlayerVisibleCard WHERE PlayerId = $this->playerId
                AND GameSessionId = $this->gameSessionId AND ItemType = '$this->itemType'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$visibleList = null;
		$counter = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$visibleList[$counter++] = $row['CardCode'];
		}
		return $visibleList;
	}

	/**
	 * Public for testing only
	 * @param type $pId
	 * @param type $gameSessionId
	 * @param type $cardCodes
	 */
	public function RemoveCardCodes($cardCodes) {
		//$csv = implode(",", $cardCodes);
		$csv = "";
		for ($i = 0; $i < count($cardCodes); $i++) {
			$csv = $csv . "'" . $cardCodes[$i] . "',";
		}
		$csv = $csv . "''";
		$where = "PlayerId = $this->playerId AND CardCode in ($csv) "
				. "AND GameSessionId = $this->gameSessionId AND ItemType = '$this->itemType'";
		$event = "DELETE FROM PlayerVisibleCard WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": -WHERE- $where");
	}

}

?>
