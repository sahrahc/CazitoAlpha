<?php

class CheatingAnotherItem extends CheatingItem {

	public $otherPlayerId;
	public $originalOtherPlayerItem;

	public function __construct($base, $otherPlayerId) {
		parent::__construct($base->playerId, $base->gameSessionId, $base->itemType);
		$this->otherPlayerId = $otherPlayerId;
	}

	public function ApplyPokerPeeker($gameInstance) {
		global $cPokerPeekerTimeOut;
		global $dateTimeFormat;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		$otherPlayerId = $this->otherPlayerId;
		$playerCardNumber = $this->playerCardNumber;
		$sessionId = $gameInstance->gameSessionId;
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}
		$activeItem = new PlayerActiveItem($playerId, $sessionId, $itemType, $cPokerPeekerTimeOut);
		$activeItem->otherPlayerId = $this->otherPlayerId;
		$activeItem->RecordItemUse();

		$otherHand = CardHelper::getPlayerHandDto($otherPlayerId, $gameInstance->id);
		//$dto1 = new PlayerCardDto($otherPlayerId, 1, $otherHand->pokerCard1Code, null);
		//$dto2 = new PlayerCardDto($otherPlayerId, 2, $otherHand->pokerCard2Code, null);
		$dto = new PlayerCardDto($otherPlayerId, $playerCardNumber, $otherHand->pokerCard2Code, null);
		$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::CheatedCards, array($dto));
		//$messagesOut[1] = new CheatOutcomeDto($itemType, CheatDtoType::CheatedHands, $dto2);
		$player = EntityHelper::getPlayer($otherPlayerId);

		$dateString = Context::GetStatusDTString();
		$lockEndString = $activeItem->lockEndDateTime->format($dateTimeFormat);
		$info = new CheatInfoDto("$itemType - Revealed " . $player->name . "'s cards on $dateString." .
				"This item will be available again at $lockEndString.");
		$info->isDisabled = $activeItem->isLocked;
		//$messagesOut[2] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		$messagesOut[1] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 * Replaces an Oil Marker with a countered Oil Marker and randomly selects 50% of cards to 
	 * be visible cards. PlayerVisibleCard::RevealCards is applied against the visible instead.
	 * @param type $playerId
	 * @param type $otherPlayerId
	 * @param type $sessionId
	 * @param type $itemType
	 */
	public function ApplyAntiOilMarker($gameInstance) {
		global $cAntiOilMarkerTimeOut;
		global $dateTimeFormat;
		
		$itemType = $this->itemType;
		$otherPlayerId = $this->otherPlayerId;
		$otherItemType = ItemType::SNAKE_OIL_MARKER;
		$newItemType = ItemType::SNAKE_OIL_MARKER_COUNTERED;
		$sessionId = $gameInstance->gameSessionId;

		// ******** casting player
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}
		$activeItem = new PlayerActiveItem($this->playerId, $sessionId, $itemType, $cAntiOilMarkerTimeOut);
		$activeItem->otherPlayerId = $this->otherPlayerId;
		$activeItem->RecordItemUse();

		// ******* changes to other player
		$otherError = $this->VerifyAndReplaceOtherPlayerItem($otherItemType, $newItemType, $gameInstance->id);
		if ($otherError) {
			return $otherError;
		}
		// remove all visible items and re-insert half
		$playerHiddenItems = new PlayerVisibleCards($otherPlayerId, $sessionId, $otherItemType, null);
		$playerHiddenItems->ResetVisible(false);

		$indexArray = range(1, 52);
		shuffle($indexArray);
		// take the first 26, randomized anyway
		$playerHiddenItems->itemType = $newItemType;
		for ($i = 0; $i <= 26; $i++) {
			$playerHiddenItems->SaveSingleCard($indexArray[$i], $gameInstance->id);
		}
		if ($this->originalOtherPlayerItem->gameInstanceId != $gameInstance->id) {
			// re-do cards already revealed for other player
			$gameCards = new GameInstanceCards($gameInstance->id);
			$gameCards->GetSavedCards();
			$otherCheatingItem = new CheatingItem($otherPlayerId, $sessionId, $newItemType);
			$otherCheatingItem->RevealOpponentsCards($gameInstance, $gameCards->playerHands);
		}

		// ****** message to current player
		$otherPlayer = EntityHelper::getPlayer($otherPlayerId);
		$dateString = Context::GetStatusDTString();
		$lockEndString = $activeItem->lockEndDateTime->format($dateTimeFormat);
		$info = new CheatInfoDto("$itemType - Applied to " . $otherPlayer->name . "'s cards on $dateString. "
				. "Item expires on $lockEndString.");
		$info->isDisabled = $activeItem->isLocked;
// leave isDisabled unchanged
		$messagesOut = array(new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
		return $messagesOut;
	}

	/**
	 * Original player needs to be found
	 */
	public function NotifyAntiOilMarkerUse() {
		$player = EntityHelper::getPlayer($this->otherPlayerId);
		$castingItemType = ItemType::ANTI_OIL_MARKER;
		$castingItems = CheatingHelper::GetActiveItemsWithOtherPlayer($this->gameSessionId, 
				$castingItemType, $this->otherPlayerId);
		if ($castingItems == null) {
			return;
		}
		foreach ($castingItems as $caster) {
			$info = new CheatInfoDto("$castingItemType - Applied on game start on " . $player->name);
			$info->isDisabled = null;
			$messagesOut = array(new CheatOutcomeDto($castingItemType, CheatDtoType::ItemLog, $info));
			CheatingHelper::_communicateCheatingOutcome($caster->playerId, $messagesOut, $this->gameSessionId);
		}
	}

	/**
	 * Used by anti oil marker
	 * @param type $otherItemType
	 * @param type $newItemType
	 * @param type $gameInstanceId
	 * @return type
	 */
	public function VerifyAndReplaceOtherPlayerItem($otherItemType, $newItemType, $gameInstanceId) {
		$otherPlayerId = $this->otherPlayerId;
		$sessionId = $this->gameSessionId;
		$itemType = $this->itemType;
		$otherPlayer = EntityHelper::getPlayer($otherPlayerId);
		if ($otherPlayer == null) {
			$info = new CheatInfoDto("$itemType on invalid user");
			$messagesOut[0] = new CheatOutcomeDto($itemType, $itemTypeCheatDtoType::ItemLog, $info);
			return $messagesOut;
		}
		$otherItem = new PlayerActiveItem($otherPlayerId, $sessionId, $otherItemType);
		$otherItem->GetSavedPlayerItem();
		if ($otherItem->startDateTime == null) {
			$info = new CheatInfoDto("$itemType does not take effect, "
					. $otherPlayer->name . " is not using $otherItemType. ");
			$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
			return $messagesOut;
		}
		$this->originalOtherPlayerItem = $otherItem;
		// clone before setting end date
		$newOtherPlayerItem = clone $otherItem;
		$otherItem->SetEndDate('');
		$otherItem->UpdateItemEndLock();
		// delete after logging end date time.
		$otherItem->Delete();
		$newOtherPlayerItem->itemType = $newItemType;
		$newOtherPlayerItem->gameInstanceId = $gameInstanceId;
// end date defines original oil marker as inactive but lock end date still applies
// the lock is on the original item but keep in affected so that it is kept in memory
		$newOtherPlayerItem->RecordItemUse();
	}

}

?>
