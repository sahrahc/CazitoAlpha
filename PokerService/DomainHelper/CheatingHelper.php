<?php

/* * ************************************************************************************* */

/**
 * FIXME:
 * PlayerActiveItem = key/value
 */
class CheatingHelper {

	private static $log = null;

	public static function log() {
		if (is_null(self::$log)) {
			self::$log = Logger::getLogger(__CLASS__);
		}
		return self::$log;
	}

	/**
	 * Public for testing only
	 * Gets the list of player Id's who have an un-ended social spotter.
	 * @param GameInstance $gInstStatus
	 * @return int array
	 */
	public static function GetPlayersWithItemType($gSessionId, $itemType) {
		$statusDTString = Context::GetStatusDTString();
		$sessionId = $gSessionId;
		if ($sessionId == null) {
			$sessionId = 'null';
		}
		/* returns a list of player ids */
		/* possible for an item to expire in the future but set to inactive before that time */
		$leftStatus = PlayerStatusType::LEFT;
		$query = "SELECT i.PlayerId FROM PlayerActiveItem i 
                INNER JOIN PlayerState ps on i.PlayerId = ps.PlayerId 
                AND i.GameSessionId = ps.GameSessionId
                WHERE ps.status != '$leftStatus' AND i.GameSessionId =
            $sessionId AND ItemType = '$itemType' "
				. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString')";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$playerIdList = null;
		$counter = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerIdList[$counter++] = (int) $row['PlayerId'];
		}
		return $playerIdList;
	}

	public static function GetPlayerWithItemType($gSessionId, $playerId, $itemType) {
		$statusDTString = Context::GetStatusDTString();
		$sessionId = $gSessionId;
		if ($sessionId == null) {
			$sessionId = 'null';
		}
		/* returns a list of player ids */
		/* possible for an item to expire in the future but set to inactive before that time */
		$leftStatus = PlayerStatusType::LEFT;
		$query = "SELECT i.* FROM PlayerActiveItem i 
                INNER JOIN PlayerState ps on i.PlayerId = ps.PlayerId 
                AND i.GameSessionId = ps.GameSessionId
                WHERE ps.status != '$leftStatus' AND i.GameSessionId =
            $sessionId AND ItemType = '$itemType' AND i.PlayerId = $playerId "
				. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString')";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			$playerItem = new PlayerActiveItem($row['PlayerId'], $sessionId, $itemType);
			$playerItem->mapPlayerActiveItem($row);
			return $playerItem;
		}
	}

	/**
	 * Get the active items of players who cheated a specific player 
	 * @param type $gSessionId
	 * @param type $itemType
	 * @param type $otherPlayerId
	 * @return type
	 */
	public static function GetActiveItemsWithOtherPlayer($gSessionId, $itemType, $otherPlayerId) {
		$statusDTString = Context::GetStatusDTString();
		$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $gSessionId AND OtherPlayerId = $otherPlayerId AND ItemType = '$itemType' "
				. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') ";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$activeItems = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$activeItems[$i] = new PlayerActiveItem($row['PlayerId'], $gSessionId, $itemType);
			$activeItems[$i]->mapPlayerActiveItem($row);
			$i++;
		}
		return $activeItems;
	}

	public static function GetActiveItemsOfItemType($gSessionId, $itemType) {
		$statusDTString = Context::GetStatusDTString();
		if ($gSessionId === null) {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId IS NULL
            AND ItemType = '$itemType' "
					. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') ";
		} else {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $gSessionId AND ItemType = '$itemType' "
					. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') ";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$activeItems = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$activeItems[$i] = new PlayerActiveItem($row['playerId'], $gSessionId, $itemType);
			$activeItems[$i]->mapPlayerActiveItem($row);
			$i++;
		}
		return $activeItems;
	}

	public static function GetActiveItemsOfItemTypeforPlayer($playerId, $gSessionId, $itemType) {
		$statusDTString = Context::GetStatusDTString();
		if ($gSessionId === null) {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId IS NULL
            AND ItemType = '$itemType' AND PlayerId = $playerId "
					. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') ";
		} else {
			$query = "SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $gSessionId AND ItemType = '$itemType' AND PlayerId = $playerId "
					. "AND (EndDateTime IS NULL OR EndDateTime > '$statusDTString') ";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$activeItems = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$activeItems[$i] = new PlayerActiveItem($row['PlayerId'], $gSessionId, $itemType);
			$activeItems[$i]->mapPlayerActiveItem($row);
			$i++;
		}
		return $activeItems;
	}

	public static function GetEndedItems() {
		global $dateTimeFormat;
		$statusDateTime = Context::GetStatusDTString();

		$query = "SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN PlayerState ps ON i.PlayerId = ps.PlayerId 
                WHERE EndDateTime <= '$statusDateTime' AND i.IsNotified = 0";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$endedItems = null;

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerId = (int) $row["PlayerId"];
			$sessionId = (int) $row["GameSessionId"];
			$itemType = $row["ItemType"];
			$endedItems[$i] = new PlayerActiveItem($playerId, $sessionId, $itemType);
			$endedItems[$i]->id = $row["Id"];
			$endedItems[$i]->lockEndDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LockEndDateTime"]);
			// the following properties are not part of the object
			$endedItems[$i]->playerSessionId = (int) $row["PlayerSessionId"];
			$endedItems[$i]->playerStatus = $row["Status"];
			$i++;
		}
		return $endedItems;
	}

	public static function DeleteVisibleCards($playerId, $itemType, $sessionId) {
		$visibleCards = new PlayerVisibleCards($playerId, $sessionId, $itemType);
		$cardCodes = $visibleCards->GetSavedCardCodes();
		$visibleCards->RemoveCardCodes($cardCodes);
	}
	
	public static function GetLockEndedItems() {
		$statusDT = Context::GetStatusDTString();
		// FIXME: should record on log
		$query = "SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN PlayerState ps ON i.PlayerId = ps.PlayerId 
                AND ps.status != ''
                WHERE LockEndDateTime <= '$statusDT'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$unlockedItems = null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			// send communication
			$sessionId = (int) $row["GameSessionId"];
			$playerId = (int) $row["PlayerId"];
			$itemType = $row["ItemType"];
			$unlockedItems[$i] = new PlayerActiveItem($playerId, $sessionId, $itemType);
			$unlockedItems[$i]->id = (int) $row["Id"];
			// communicate only if the user is in the same session and not left
			$unlockedItems[$i]->playerSessionId = (int) $row["PlayerSessionId"];
			$unlockedItems[$i]->playerStatus = $row["Status"];
			$i++;
		}
		return $unlockedItems;
	}

	/**
	 * To be called at the end of a game, if it is a cheating game, although it could be called
	 * earlier.
	 * Adds all the game cards just used in game to a visible list 
	 * which is specific to a player for all players who have an active Sally Spotter item
	 * Must validate if the user has this item activated 
	 * @param type $gInstStatus 
	 */
	public static function AddVisibleCards($gInstStatus) {
		// FIXME: not all the community cards were seen if the game was restarted before the prevoius finished.

		$gameSessionId = $gInstStatus->gameSessionId;
		$itemType = ItemType::SOCIAL_SPOTTER;
		// implied validation
		$playerIdList = CheatingHelper::GetPlayersWithItemType($gameSessionId, $itemType);
		$gameCards = new GameInstanceCards($gInstStatus->id);
		$instanceCardList = $gameCards->GetCardCodesForInstance(false);

		// insert record on Player Visible Card for each such player for each instance card
		if ($playerIdList == null) {
			return null;
		}
		foreach ($playerIdList as $playerId) {
			$visible = new PlayerVisibleCards($playerId, $gameSessionId, $itemType, null);
			$visible->SaveInstanceCards($instanceCardList);

			// no need to send info or result, that is done at the beginning of a game
		}
	}

	/**
	 * To be called at the start of a game if it is a cheating game.
	 * This function returns the list of other players' cards that the user knows the value of
	 * by comparing to the list of visible cards
	 * Asynchronous - NEEDS TO COMMUNICATE
	 * @param type $gameInstance
	 * @return type 
	 */
	public static function RevealMarkedCards($gameInstance) {
		$gameSessionId = $gameInstance->gameSessionId;

		$item1 = ItemType::SOCIAL_SPOTTER;
		$item2 = ItemType::SNAKE_OIL_MARKER_COUNTERED;
		$item3 = ItemType::SNAKE_OIL_MARKER;
		$playerIdList1 = self::GetPlayersWithItemType($gameSessionId, $item1);
		$playerIdList2 = self::GetPlayersWithItemType($gameSessionId, $item2);
		$playerIdList3 = self::GetPlayersWithItemType($gameSessionId, $item3);
		// no consolidating, all three types of items will run if a user has them all
		// snake oil marker takes precedence
		// implied validation
		if ($playerIdList1 == null && $playerIdList2 == null && $playerIdList3 == null) {
			return;
		}
		// get list of all instance cards
		$gameCards = new GameInstanceCards($gameInstance->id);
		$gameCards->GetSavedCards();
		$playerHands = $gameCards->playerHands;
		$cheatingItem = new CheatingItem(null, $gameSessionId, $item2);
		if (!is_null($playerIdList2)) {
			foreach ($playerIdList2 as $playerId) {
				// item oil marker countered is first because it clears other revealed marked cards
				$cheatingItem->playerId = $playerId;
				$cheatingItem->RevealOpponentsCards($gameInstance, $playerHands);
								// tell casting player 
				$cheatingAnotherItem = new CheatingAnotherItem($cheatingItem, $playerId);
				$cheatingAnotherItem->playerId = null; // needs to be found
				$cheatingAnotherItem->NotifyAntiOilMarkerUse();
			}
		}
		if (!is_null($playerIdList1)) {
			$cheatingItem->itemType = $item1;
			foreach ($playerIdList1 as $playerId) {
				$cheatingItem->playerId = $playerId;
				$cheatingItem->RevealOpponentsCards($gameInstance, $playerHands);
			}
		}
		if (!is_null($playerIdList3)) {
			$cheatingItem->itemType = $item3;
			foreach ($playerIdList3 as $playerId) {
				$cheatingItem->playerId = $playerId;
				$cheatingItem->RevealOpponentsCards($gameInstance, $playerHands);
			}
		}
	}

	/**
	 * For new game, select first face card not intended for user and swap. May not swap if
	 * player was to be given all face cards anyway.
	 * @param type $playerId
	 * @param type $sessionId
	 * @param type $itemType
	 */
	public static function ApplyFaceMelter($gameInstance, $itemType) {
		$sessionId = $gameInstance->gameSessionId;
		// validated game status is started
		if ($gameInstance->status !== GameStatus::STARTED) {
			$info = new CheatInfoDto("$itemType was attempted but was not requested.");
			return;
			//return new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		}
		$players = self::GetPlayersWithItemType($sessionId, $itemType);
		if (count($players) == 0) {
			return;
		}
		// if all players requested the face melter, then everyone will get a face card.
		// only concerned with giving requestors a face card, possible for a requestor to lose a
		// face card from another requestor but be given another face card.
		$i = 0;
		$gameCards = new GameInstanceCards($gameInstance->id);
		$faceCardIndex = $gameCards->GetFaceCardIndexes();
		// replace first card unless a face card
		foreach ($players as $playerId) {
			$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstance->id);
			if (CardHelper::IsFaceCard($playerHand->pokerCard1Code)) {
				$cardCode = $playerHand->pokerCard2Code;
			} else {
				$cardCode = $playerHand->pokerCard1Code;
			}
			$otherCard = $faceCardIndex[$i++];
			$gameCards->SwapCardByCode($cardCode, $otherCard);

			// log active items: end face melter and log use
			$activeItem = new PlayerActiveItem($playerId, $sessionId, ItemType::KEEP_FACE_CARDS_APPLIED, null);
			$activeItem->RecordItemUse();
			$melterItem = CheatingHelper::GetPlayerWithItemType($sessionId, $playerId, $itemType);
			$melterItem->SetEndDate('');
			$melterItem->UpdateItemEndLock();
			// delete after logging end date
			$melterItem->Delete();
			$info = new CheatInfoDto("$itemType applied. Replaced $cardCode with " . $otherCard);
			$dtos = array(new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
			CheatingHelper::_communicateCheatingOutcome($playerId, $dtos, $sessionId);
		}
	}

	/**
	 * Set a particular item to inactive for all players in instance
	 * currently only river swapping
	 */
	public static function SetItemToInactiveForInstance($gameInstanceId, $itemType) {
		$statusDateTime = Context::GetStatusDTString();
		$query = "SELECT * FROM PlayerActiveItem WHERE GameInstanceId =
            $gameInstanceId AND ItemType = '$itemType' "
				. "AND EndDateTime <= '$statusDateTime'";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$i = 0;
		$items = null;

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$playerId = (int) $row["PlayerId"];
			$sessionId = (int) $row["GameSessionId"];
			$itemType = $row["ItemType"];
			$items[$i] = new PlayerActiveItem($playerId, $sessionId, $itemType);
			$items[$i]->id = (int) $row["Id"];
			$items[$i]->gameInstanceId = (int) $row["GameInstanceId"];
			$items[$i]->SetInstanceItemToInactive();
			$i++;
		}
	}

	public static function UpdateSleeveSession($playerId, $gameSessionId) {
		$sleeves = self::GetActiveItemsOfItemTypeforPlayer($playerId, null, ItemType::LOAD_CARD_ON_SLEEVE);
		if (count($sleeves) == 0) {
			return;
		}
		foreach ($sleeves as $s) {
			$s->gameSessionId = $gameSessionId;
			$s->UpdateItemSession();
		}
	}

	/**
	 * 
	 * @param type $playerId
	 * @param CheatOutcomeDto[] $messages
	 */
	public static function _communicateCheatingOutcome($playerId, $events, $gameSessionId) {
		$ex = Context::GetExchangePlayer();
		$message = new QueueMessage(EventType::CHEATED, $events, $gameSessionId);
		QueueManager::SendToPlayer($ex, $playerId, json_encode($message));
	}

}

?>
