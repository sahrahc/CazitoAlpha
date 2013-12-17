<?php

/* Type: Object and partial response Dto.
 * Primary Table: PlyaerHiddenCard
 * Description: Cards in sleeve or tucked under the a groove in table
 */

class PlayerHiddenCards {

	public $playerId;
	public $cardCodeList;
	public $hideType;
	private $history;

	function __construct($playerId, $cardCodeList, $hideType) {
		$this->history = Logger::getLogger(__CLASS__);
		/*
		  global $pokerCardCode;
		  $cardCodeList = array();
		  if (!is_null($cardNameList) && count($cardNameList) > 0) {
		  for ($i=0; $i<count($cardNameList); $i++) {
		  array_push($cardCodeList, $pokerCardCode[$this->cardCodeList[$i]]);
		  }
		  }
		 * 
		 */
		$this->playerId = $playerId;
		$this->cardCodeList = $cardCodeList;
		$this->hideType = $hideType;
	}

	/**
	 * Add cards to the hidden list. Translates the card names to codes
	 * TODO: create PlayerActiveItem entry
	 * @param type $pId
	 * @param type $cardCodes
	 * @return string array
	 */
	public function Save() {
		// validate
		if ($this->cardCodeList == null || count($this->cardCodeList) == 0) {
			return;
		}
		// get old cards first, because adding unique
		// TODO: verify if requirement
		$oldCardCodes = $this->GetSavedCardCodes();
		if ($oldCardCodes != null && count($oldCardCodes) > 0) {
			for ($i = 0; $i < count($this->cardCodeList); $i++) {
				if (array_search($this->cardCodeList[$i], $oldCardCodes) != false) {
					unset($this->cardCodeList[$i]);
				}
			}
		}
		if (count($this->cardCodeList) == 0) {
			return;
		}
		// start the cardposition with the number after the max
		$query = "select max(CardPosition) MaxPos 
            From PlayerHiddenCard WHERE PlayerId = $this->playerId
            AND HideType = '$this->hideType'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		$maxPos = $row[0] == 0 ? 0 : (int) $row[0] + 1;
		for ($i = 0; $i < count($this->cardCodeList); $i++) {
			$cardCode = $this->cardCodeList[$i];
			if ($cardCode != false) {
				$cardPosition = $i + $maxPos;
				$vars = "PlayerId, CardCode, CardPosition, HideType";
				$values = "$this->playerId, '$cardCode', $cardPosition, '$this->hideType'";
				$event = "INSERT INTO PlayerHiddenCard ($vars) VALUES ($values)";
				$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
				$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
			}
		}
	}

	/**
	 * Gets the list of hidden cards for a given player
	 * @param type $pId
	 * @return string array
	 */
	public function GetSavedCardCodes() {

		if ($this->playerId == null || $this->hideType == null) {
			return null;
		}
		$query = "SELECT * FROM PlayerHiddenCard WHERE PlayerId = $this->playerId
            AND HideType = '$this->hideType' 
                ORDER BY CardPosition";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$counter = 0;
		$hiddenList = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$hiddenList[$counter++] = $row['CardCode'];
		}
		return $hiddenList;
	}

	/**
	 * Swaps a card on a player's hand with another from a hidden list.
	 * Sends queue with updated list and updated player hand
	 * @param type $pId
	  // Replaced card not released back to deck
	 * @param type $playerCardNumber
	 * @param type $hiddenCardNumber
	 */
	public function UseHiddenCard($gameInstanceId, $pCardNum, $hiddenCardNumber, $itemType) {
		global $pokerCardName;

		// update hidden card list
		$hiddenCodeList = $this->GetSavedCardCodes();
		$updatedHiddenList = array();
		$i = 0;
		foreach ($hiddenCodeList as $hCardCode) {
			if ($i == $hiddenCardNumber) {
				//$oldplayerCard = CardHelper::getPlayerCard($pId, $gameInstanceId, $pCardNum);  
				$playerCardCode = $hCardCode;
			} else {
				array_push($updatedHiddenList, $hCardCode);
			}
			$i++;
		}
		$this->ResetSleeve(false);
		$this->cardCodeList = $updatedHiddenList;
		$this->Save();

		// get the index for the hidden card
		$gameCards = new GameInstanceCards($gameInstanceId);
		$gameCard = $gameCards->GetGameCardByCode($playerCardCode);
		// update player hand
		$gameCard->playerId = $this->playerId;
		$gameCard->playerCardNumber = $pCardNum;
		$gameCard->UpdatePlayerCard();
		// reset old player card

		$dto = new PlayerCardDto($this->playerId, $pCardNum, $playerCardCode, null);
		$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::CheatedHands, $dto);
		$messagesOut[1] = new CheatOutcomeDto($itemType, CheatDtoType::CheatedHidden, $updatedHiddenList);
		$cardName = $pokerCardName[$playerCardCode];
		$info = new CheatInfoDto("$itemType - Replaced card number $pCardNum with $cardName from the hidden list");
		if (count($updatedHiddenList) == 0) {
			$info->isDisabled = 1;
		} else {
			$info->isDisabled = 0;
		}
		$messagesOut[2] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	public function ResetSleeve($all = false) {
		$where = "PlayerId = $this->playerId";
		if (!$all) {$where = $where . " AND HideType = '$this->hideType'"; }
		$event = "DELETE FROM PlayerHiddenCard WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": -WHERE- $where");
	}

}

?>
