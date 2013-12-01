<?php

class CheatingHidingItem extends CheatingItem {

	public function __construct($base) {
		parent::__construct($base->playerId, $base->gameSessionId, $base->itemType);
	}
	public function CheatLoadHidden($cardCodeList) {
// TODO: validate if user has the levels

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		/* PlayerActiveItem */
		$activeItem = new PlayerActiveItem($playerId, $this->gameSessionId, $itemType);
		$activeItem->RecordItemUse();

		$hiddenCards = new PlayerHiddenCards($playerId, $cardCodeList, $itemType);
		$hiddenCards->Save();
		$cardCodes = $hiddenCards->GetSavedCardCodes();
		return $cardCodes;
	}

	public function CheatUseHidden($gameInstance, $hiddenCardNumber, $prevItemType = null) {
		if ($prevItemType != null) {
			$errorOut = $this->VerifyUnlocked($prevItemType);
			if ($errorOut) {
				return $errorOut;
			}
		}

		$activeItem = new PlayerActiveItem($this->playerId, $gameInstance->gameSessionId, $this->itemType);
		$activeItem->RecordItemUse();

		$hidden = new PlayerHiddenCards($this->playerId, null, $prevItemType);
		$messagesOut = $hidden->UseHiddenCard($gameInstance->id, $this->playerCardNumber, $hiddenCardNumber, $this->itemType);
		return $messagesOut;
	}

	/*
	 * Validates a table tuck is allowed and places a card under the table.
	 * Deduct cost.
	 */

	public function TuckCardUnder($cardCodeList) {
		// TODO: check level
		$updatedHiddenList = $this->CheatLoadHidden($cardCodeList);

		$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::CheatedHidden, $updatedHiddenList);
		$info = new CheatInfoDto("$this->itemType - Tucked " . json_encode($cardCodeList) 
				. " under table");
		$info->isDisabled = 0;
		$messagesOut[1] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 * Removes a card from under the table. Should not be used very much as it wastes a lot of money
	 * @param type $cardCode
	 */
	public function TuckCardOut($cardCode) {
		$updatedHiddenList = CheatingHelper::DeleteHiddenCard($this->playerId, array($cardCode), $this->itemType);

		$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::CheatedHidden, $updatedHiddenList);
		$info = new CheatInfoDto("$this->itemType - Remove $cardCode from under table");
		$info->isDisabled = 0;
		$messagesOut[1] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

}

?>
