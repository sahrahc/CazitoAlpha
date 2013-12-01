<?php

class CheatingItem {

	public $playerId;
	public $gameSessionId;
	public $itemType;
	public $playerCardNumber;
	private $log;

	public function __construct($playerId, $gameSessionId, $itemType) {
		$this->log = Logger::getLogger(__CLASS__);
		$this->playerId = $playerId;
		$this->gameSessionId = $gameSessionId;
		$this->itemType = $itemType;
	}

	/**
	 * Starts the process of marking cards which are seen by a player. The marking of cards
	 * only needs to happen at the end of the game, but spans multiple games
	 * @global type $cSocialSpotterTimeOut
	 * @global type $cSocialSpotterDuration
	 */
	public function ApplySocialSpotter() {
		global $cSocialSpotterTimeOut;
		global $cSocialSpotterDuration;
		global $dateTimeFormat;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		$gSessionId = $this->gameSessionId;
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}

		$activeItem = new PlayerActiveItem($playerId, $gSessionId, $itemType, $cSocialSpotterTimeOut);
		$activeItem->SetEndDate($cSocialSpotterDuration);
		$activeItem->RecordItemUse();

		$dateString = Context::GetStatusDTString();
		$endString = $activeItem->endDateTime->format($dateTimeFormat);
		$lockEndString = $activeItem->lockEndDateTime->format($dateTimeFormat);
//		$info = new CheatInfoDto("$itemType was activated on $dateString. Cards you see at this table until $endString will be marked so you know the values in subsequent games. After $endString, this item wil be available again at $lockEndString.");
		$info = new CheatInfoDto("$itemType activated on $dateString until $endString and locked till $lockEndString.");
		$info->isDisabled = $activeItem->isLocked;
		$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 * Gets the list of all players cards and flags the cards that match the suit.
	 * Validate the item is not locked.
	 * @param id playerId
	 * @param GameInstance $gameInstance
	 * @param string $itemType
	 * @param DateTime $currentDT
	 */
	public function ApplySuitMarker($gameInstance) {
		global $dateTimeFormat;
		global $itemTypeTimeOut;
		global $itemTypeSuit;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}

		$cheaterListDto = null;
		$gameCards = new GameInstanceCards($gameInstance->id);
		$gameCards->GetSavedCards();

		$counter = 0;
		$expSuite = $itemTypeSuit[$itemType];

		foreach ($gameCards->playerHands as $pH) {
			$cheaterListDto[$counter++] = $pH->getPlayerCardFromHand($expSuite, 1);
			$cheaterListDto[$counter++] = $pH->getPlayerCardFromHand($expSuite, 2);
		}
		/* ------------------------------------------------------------------------------ */
// create record with time out
		$timeOut = $itemTypeTimeOut[$itemType];
		if ($timeOut == null) {
			throw new Exception("Can only mark clubs, diamonds and hearts, not " . $itemType);
		}

		/* PlayerActiveItem */
		$activeItem = new PlayerActiveItem($playerId, $gameInstance->gameSessionId, $itemType, $timeOut);
		$activeItem->gameInstanceId = $gameInstance->id;
		$activeItem->RecordItemUse();

		/* ------------------------------------------------------------------------------ */
		$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::CheatedCards, $cheaterListDto);
		$info = new CheatInfoDto("$itemType was applied. This item is available again at "
				. $activeItem->lockEndDateTime->format($dateTimeFormat) . '.');
		$info->isDisabled = $activeItem->isLocked;
		$messagesOut[1] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 * Replaces the playerCardNumber with a random ace.
	 * No validation other than deducting price
	 * @param GameInstance $gameInstance
	 */
	public function ApplyAcePusher($gameInstance) {
		global $pokerCardName;

		$pId = $this->playerId;
		$pCardNum = $this->playerCardNumber;
		$deck = EvalHelper::init2x2deck();
		$suits = array('s', 'h', 'd', 'd');
		$suitsBit = array(0x1000, 0x2000, 0x4000, 0x8000);
		$randIndex = rand(0, 3);
		$randSuit = $suits[$randIndex];
		$cardCode = 'A' . $randSuit;
// Aces have rank = 12
		$cardIndex = EvalHelper::find2x2DeckIndex(12, $suitsBit[$randIndex], $deck);
		CardHelper::updatePlayerCard($pId, $gameInstance->id, $pCardNum, $cardIndex, $cardCode);
		/* ------------------------------------------------------------------------------ */
// create record without time out

		$activeItem = new PlayerActiveItem($pId, $gameInstance->gameSessionId, $this->itemType, null);
		$activeItem->gameInstanceId = $gameInstance->id;
		$activeItem->RecordItemUse();

		/* response is immediate, no need to check whether the message
		 * is sent to players who left session
		 */
		$dto = new PlayerCardDto($pId, $pCardNum, $cardCode, $randSuit);
		$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::CheatedHands, $dto);
		$cardName = $pokerCardName[$cardCode];
		$info = new CheatInfoDto("$this->itemType - Replaced card number $pCardNum with $cardName. You may push an ace again at any time");
		$info->isDisabled = $activeItem->isLocked;
		$messagesOut[1] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 *
	 * @global type $cRiverShufflerTimeOut
	 * @param type $playerId 
	 * @param type $gameInstance
	 * @param type $currentDT
	 * @return string (array of 1)
	 */
	public function CheatLookRiverCard($gameInstance) {
		global $cRiverShufflerTimeOut;
		global $dateTimeFormat;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}
		$gameCards = new GameInstanceCards($gameInstance->id);
		$gameCard = $gameCards->GetPlayerGameCard(-1, 5);
		$this->log->Debug(__FUNCTION__ . " - River card for instance $gameInstance->id is $gameCard->cardCode");
		$cardNameListCode = null;
		if (!is_null($gameCard->cardCode)) {
			$cardNameListCode[0] = $gameCard->cardCode;
		}
		$this->log->Debug(__FUNCTION__ . " - River card name for instance $gameInstance->id is " . json_encode($cardNameListCode));

		/* PlayerActiveItem */
		$activeItem = new PlayerActiveItem($playerId, $gameInstance->gameSessionId, $itemType, $cRiverShufflerTimeOut);
		$activeItem->gameInstanceId = $gameInstance->id;
		$activeItem->RecordItemUse();

		/* response is immediate, no need to check whether the message
		 * is sent to players who left session
		 */
		/* ------------------------------------------------------------------------------ */
		$messagesOut = array();
		if ($cardNameListCode != null) {
			$messagesOut = array(new CheatOutcomeDto($itemType, CheatDtoType::CheatedNext, $cardNameListCode));
		}
		$dateString = Context::GetStatusDTString();
		$lockEndString = $activeItem->lockEndDateTime->format($dateTimeFormat);
		$info = new CheatInfoDto("$itemType activated on $dateString. You may swap the river card for the current game for the next card in the deck. The river card may not the one you see if another player used an item. This item wil be available again at $lockEndString.");
		$info->isDisabled = $activeItem->isLocked;
		array_push($messagesOut, new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
		return $messagesOut;
	}

	/**
	 * TODO: create playerActiveItem for use
	 * @param type $playerId 
	 */
	public function CheatSwapRiverCard($gameInstance) {
		global $dateTimeFormat;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
// validate the item is active
		$sessionId = $gameInstance->gameSessionId;
		$errorOut = $this->VerifyUnlocked(ItemType::RIVER_SHUFFLER);
		if ($errorOut) {
			return $errorOut;
		}
		$activeItem = new PlayerActiveItem($playerId, $sessionId, ItemType::RIVER_SHUFFLER);
		$activeItem->GetSavedPlayerItem();
		$gInstId = $gameInstance->id;
		if ($activeItem->startDateTime != null && $gInstId != $activeItem->gameInstanceId) {
			$info = new CheatInfoDto("$itemType - Option not valid for this game instance");
// leave isDisabled unchanged
			$messagesOut = array(new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
			return $messagesOut;
		}
// get the next unassigned GameCard -
// FIXME: from the player's next list?
		$gameCards = new GameInstanceCards($gInstId, null);
		$availGameCard = $gameCards->GetPlayerGameCard(null, null);
		$curGameCard = $gameCards->GetPlayerGameCard(-1, 5);

// update the unassigned and the new game card
		$gameCards->SwapPlayersCards(-1, $availGameCard, null, $curGameCard);
		/* ------------------------------------------------------------------------------ */
		$newActiveItem = new PlayerActiveItem($playerId, $sessionId, $itemType);
		$newActiveItem->isLocked = 1; // locked for instance so it won't be used again
		$newActiveItem->RecordItemUse();

		/* ------------------------------------------------------------------------------ */
		$endString = $activeItem->lockEndDateTime->format($dateTimeFormat);
		$info = new CheatInfoDto("$itemType - Replaced $curGameCard->cardCode with "
				. "$availGameCard->cardCode. You may use this option again after $endString");
// leave isDisabled unchanged
		$info->isDisabled = $newActiveItem->isLocked;
		$messagesOut = array(new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
		return $messagesOut;
	}

	/**
	 * Creates an active item. PlayerVisibleCard::RevealCards applies to this item type, so 
	 * that all cards are revealed
	 * 
	 * @param int $playerId
	 * @param int $sessionId
	 * @param string $itemType
	 */
	public function ApplyOilMarker($gameInstanceId) {
		global $cSnakeOilMarkerTimeOut;
		global $cSnakeOilMarkerDuration;
		global $dateTimeFormat;

		$playerId = $this->playerId;
		$itemType = $this->itemType;
		$sessionId = $this->gameSessionId;
		$errorOut = $this->VerifyUnlocked();
		if ($errorOut) {
			return $errorOut;
		}
		$activeItem = new PlayerActiveItem($playerId, $sessionId, $itemType, $cSnakeOilMarkerTimeOut);
		$activeItem->SetEndDate($cSnakeOilMarkerDuration);
		$activeItem->gameInstanceId = $gameInstanceId;
		$activeItem->RecordItemUse();

		$currentDTString = Context::GetStatusDTString();
		$lockEndDTString = $activeItem->lockEndDateTime->format($dateTimeFormat);
		$info = new CheatInfoDto("$itemType started on $currentDTString until $lockEndDTString");
		$info->isDisabled = $activeItem->isLocked;
		$messagesOut[0] = new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	/**
	 * A face melter takes effect on next game. Game Instance Id is null until used
	 * @param type $playerId
	 * @param type $sessionId
	 * @param type $itemType
	 */
	public function RequestFaceMelter() {
		// create record without time out
		// verify none active for next game
		$previousItem = CheatingHelper::GetPlayerWithItemType($this->gameSessionId, $this->playerId, $this->itemType);
		if ($previousItem !== null) {
			$info = new CheatInfoDto("$this->itemType - Already requested.");
			$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
			return $messagesOut;
		}
		$activeItem = new PlayerActiveItem($this->playerId, $this->gameSessionId, $this->itemType, null);
		$activeItem->RecordItemUse();
		$info = new CheatInfoDto("$this->itemType - Requested for next game");
		$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	protected function VerifyUnlocked($previousType = null) {
		global $dateTimeFormat;

		$previousItem = new PlayerActiveItem($this->playerId, $this->gameSessionId, $previousType);
		if ($previousType) {
			$previousItem->GetSavedPlayerItem();
			if ($previousItem->startDateTime == null) {
				$info = new CheatInfoDto("$this->itemType was attempted but $previousType was skipped.");
			}
		} else {
			$previousItem->itemType = $this->itemType;
			$previousItem->GetSavedPlayerItem();
			if ($previousItem->startDateTime != null && $previousItem->isLocked == 1) {
				$info = new CheatInfoDto("$this->itemType was attempted but is locked until "
						. $previousItem->lockEndDateTime->format($dateTimeFormat) . '.');
			}
		}
		if (!isset($info)) {
			return null;
		}
		$messagesOut[0] = new CheatOutcomeDto($this->itemType, CheatDtoType::ItemLog, $info);
		return $messagesOut;
	}

	public function RevealOpponentsCards($gameInstance, $playerHands) {
		$playerId = $this->playerId;
		$itemType = $this->itemType;

		$cheaterListDto = array();
		if ($itemType === ItemType::SNAKE_OIL_MARKER) {
			$cheaterListDto = $this->IdentifyAllOpponentsCards($playerHands);
		} else {
			$visibles = new PlayerVisibleCards($playerId, $gameInstance->gameSessionId, $itemType);
			$playerCardCodes = $visibles->GetSavedCardCodes();
			if (!is_null($playerCardCodes)) {
				// find which of the opponents cards are reveals given player's visible
				$cheaterListDto = $this->IdentifyOpponentsVisibleCards($playerHands, $playerCardCodes);
			}
		}
		$count = count($cheaterListDto);

		// send message to player with opponents' hands
		$messagesOut = array();
		if ($count > 0) {
			$messagesOut = array(new CheatOutcomeDto($itemType, CheatDtoType::CheatedCards, $cheaterListDto));
			$encodedDto = json_encode($cheaterListDto);
			$this->log->Debug(__FUNCTION__ . " - Matched list: " . $encodedDto);
		}
		if ($itemType === ItemType::SNAKE_OIL_MARKER_COUNTERED) {
			$info = new CheatInfoDto(ItemType::SNAKE_OIL_MARKER . ": " . ItemType::ANTI_OIL_MARKER
					. " applied on you by opponent, $count cards revealed.");
			$info->isDisabled = null;
			array_push($messagesOut, new CheatOutcomeDto(ItemType::SNAKE_OIL_MARKER, CheatDtoType::ItemLog, $info));
		} else {
			$info = new CheatInfoDto("$itemType - looked for marked cards for game "
					. $gameInstance->id . " and found $count.");
			$info->isDisabled = null;
			array_push($messagesOut, new CheatOutcomeDto($itemType, CheatDtoType::ItemLog, $info));
		}
		CheatingHelper::_communicateCheatingOutcome($playerId, $messagesOut, $gameInstance->gameSessionId);
	}

	public function IdentifyAllOpponentsCards($playerHands) {
		// all cards are visible
		if ($this->itemType !== ItemType::SNAKE_OIL_MARKER) {
			return;
		}
		$cheaterListDto = null;
		$counter = 0;
		foreach ($playerHands as $pH) {
			if ($pH->playerId === $this->playerId) {
				continue;
			}
			$code1 = $pH->pokerCard1->cardCode;
			$cheaterListDto[$counter++] = new PlayerCardDto($pH->playerId, 1, $code1, null);
			$code2 = $pH->pokerCard2->cardCode;
			$cheaterListDto[$counter++] = new PlayerCardDto($pH->playerId, 2, $code2, null);
		}
		return $cheaterListDto;
	}

	public function IdentifyOpponentsVisibleCards($playerHands, $playerCardCodes) {
		$cheaterListDto = null;
		$counter = 0;
		foreach ($playerHands as $pH) {
			if ($pH->playerId === $this->playerId) {
				continue;
			}
			$code1 = $pH->pokerCard1->cardCode;
			if (in_array($code1, $playerCardCodes)) {
				$cheaterListDto[$counter++] = new PlayerCardDto($pH->playerId, 1, $code1, null);
			}
			$code2 = $pH->pokerCard2->cardCode;
			if (in_array($code2, $playerCardCodes)) {
				$cheaterListDto[$counter++] = new PlayerCardDto($pH->playerId, 2, $code2, null);
			}
		}
		return $cheaterListDto;
	}

}

?>
