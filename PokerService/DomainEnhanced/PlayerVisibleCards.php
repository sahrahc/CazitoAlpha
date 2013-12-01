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
	private $log;

	function __construct($playerId, $gameSessionId = null, $itemType = null, $cardCodeList = null) {
		$this->log = Logger::getLogger(__CLASS__);

		$this->playerId = $playerId;
		$this->itemType = $itemType;
		$this->gameSessionId = $gameSessionId;
		$this->cardCodeList = $cardCodeList;
	}

	public function ResetVisible($all = true) {
		if ($all) {
			executeSQL("DELETE FROM PlayerVisibleCard WHERE PlayerId = $this->playerId", __FUNCTION__ . "
            :Error deleting from PlayerVisibleCard where player is $this->playerId");
		} else {
			executeSQL("DELETE FROM PlayerVisibleCard WHERE PlayerId = $this->playerId "
					. "AND ItemType = '$this->itemType'", __FUNCTION__ . "
            :Error deleting from PlayerVisibleCard where player is $this->playerId");
		}
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
			$query = "INSERT INTO PlayerVisibleCard (PlayerId, CardCode, GameSessionId, ItemType) "
					. "VALUES ($this->playerId, '$cardCode', $this->gameSessionId, '$this->itemType')";
			executeSQL($query, __FUNCTION__ . ": Error inserting into PlayerVisibleCard "
					. "where player is $this->playerId and session is $this->gameSessionId "
					. "and item type is $this->itemType");
		}
	}

	public function SaveSingleCard($cardCode, $gameInstanceId) {
		executeSQL("INSERT INTO PlayerVisibleCard (PlayerId, CardCode, GameSessionId, ItemType)
                    SELECT $this->playerId, CardCode, $this->gameSessionId, "
				. "'$this->itemType' FROM GameCard "
				. "WHERE DeckPosition = $cardCode "
				. "AND GameInstanceId = $gameInstanceId ", __FUNCTION__ . ": Error
                        inserting into PlayerVisibleCard player $this->playerId");
	}

	/**
	 * Public for testing only
	 * @param type $pId
	 * @return type 
	 */
	public function GetSavedCardCodes() {
		$result = executeSQL("SELECT CardCode FROM PlayerVisibleCard WHERE PlayerId = $this->playerId
                AND GameSessionId = $this->gameSessionId AND ItemType = '$this->itemType'", __FUNCTION__ . ":
                Error selecting player visible card for player $this->playerId and session $this->gameSessionId");
		$visibleList = null;
		$counter = 0;
		while ($row = mysql_fetch_array($result)) {
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
		$query = "DELETE FROM PlayerVisibleCard WHERE PlayerId = $this->playerId AND CardCode in ($csv)
            AND GameSessionId = $this->gameSessionId AND ItemType = '$this->itemType'";
		executeSQL($query, __FUNCTION__ . ": Error deleting from PlayerVisibleCard where player is $this->playerId");
		echo $query;
	}

}

?>
