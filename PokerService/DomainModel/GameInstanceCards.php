<?php

/* This object allows all cards for a game to be passed.
 */

class GameInstanceCards {

	public $gameInstanceId;
	public $numberCommunityCardsShown;
	public $communityCards;
	public $playerHands;
	private $log;

	function __construct($gameInstanceId, $numberCommunityCardsShown = null) {
		$this->log = Logger::getLogger(__CLASS__);
		$this->gameInstanceId = $gameInstanceId;
		$this->numberCommunityCardsShown = $numberCommunityCardsShown;
	}

	/**
	 * Get one of a player's two cards
	 * @param int $pId
	 * @param type $cardNum
	 * @return GameCard 
	 */
	public function GetPlayerGameCard($pId, $cardNum) {
		$gInstId = $this->gameInstanceId;
		if ($pId == null) {
			$query = "SELECT * FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId is null ORDER BY DeckPosition LIMIT 1";
		} else {
			$query = "SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = $pId AND PlayerCardNumber = $cardNum";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
// populate the return object
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$gameCard = new GameCard();
		$gameCard->mapRow($row);
		return $gameCard;
	}

	public function GetSavedCards($excludeFoldCondition = false) {
		// get all the cards, order with community cards first.
		$query = "SELECT g.*, ps.Status AS Status, ps.HandType AS HandType, 
			ps.CurrentStake as CurrentStake, ps.Status as Status
			FROM GameCard g 
                LEFT JOIN PlayerState ps ON g.GameInstanceId = ps.GameInstanceId
                AND g.PlayerId = ps.PlayerId 
				WHERE g.GameInstanceId = $this->gameInstanceId
                AND ((g.PlayerId is not null AND ps.PlayerId is not null)
				  OR (g.PlayerId = -1 AND ps.PlayerId is null))
                ORDER BY g.PlayerId, PlayerCardNumber";

		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		// initialize
		$playerIndex = 0; // index on array of players
		$ccIndex = 0;  // index on array of community cards
		$playerHands = null;
		$communityCards = null;
		// this won't work if $result is not sorted by playerid
		while ($rowCard = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$isFolded = false;
			if ($excludeFoldCondition) {
				$isFolded = $rowCard["Status"] === PlayerStatusType::FOLDED ||
					$rowCard["Status"] === PlayerStatusType::LEFT;
			}
			$gameCard = new GameCard();
			$gameCard->mapRow($rowCard);
			if ($rowCard["PlayerId"] == -1) {
				// process community cards
				$communityCards[$ccIndex] = $gameCard;
				$communityCards[$ccIndex++]->cardIndex = (int) $rowCard["CardIndex"];
			} else if (//$rowCard["Status"] != PlayerStatusType::LEFT &&
					!$isFolded) {
				$playerHands[$playerIndex] = new PlayerHand((int) $rowCard['PlayerId'], $gameCard, null);
				$playerHands[$playerIndex]->SetPlayerState($this->gameInstanceId, $rowCard["CurrentStake"], $rowCard["Status"]);
				$playerHands[$playerIndex]->pokerHandType = $rowCard['HandType'];
//				$playerHands[$playerIndex]->isWinningHand = $rowCard["Status"] === PlayerStatusType::WON ? 1 : 0;
				
				// get second card
				$rowCard2 = mysql_fetch_array($result, MYSQL_ASSOC);
				if ($rowCard2["PlayerId"] != $rowCard['PlayerId'] || $rowCard2["PlayerCardNumber"] != 2) {
					$this->log->warn(__FUNCTION__ . ": There was no second card for game $this->gameInstanceId "
						. "and player " . $rowCard['PlayerId']);
				continue;
				}
				$gameCard2 = new GameCard();
				$gameCard2->mapRow($rowCard2);
				$playerHands[$playerIndex++]->pokerCard2 = $gameCard2;
			}
		}

		$this->communityCards = $communityCards;
		$this->playerHands = $playerHands;
	}

	/**
	 * @param array($pokerCard) $pokerCards: list of cards, randomly shuffled, for all players + 5 community cards
	 */
	public function InitDealGameCards($testCardCodes = null) {
		if (is_null($testCardCodes)) {
			$pokerCards = CardHelper::initRandomDeck();
		} else {
			$pokerCards = CardHelper::initTestingDeck($testCardCodes);
		}
		// NOTE: the hands don't get saved on the player state until all the hands are known.
		// 1. player cards; no need to worry about player status
		$playerStatuses = PlayerInstance::GetPlayerInstancesForGame($this->gameInstanceId);

		$cardCounter = 0;
		//$this->log->debug(__FUNCTION__ . " - number players in db: $numberRows");
		foreach ($playerStatuses as $playerStatus) {
			// assign and store player cards
			$card1Index = $pokerCards[$cardCounter]->cardIndex;
			$card1Code = $pokerCards[$cardCounter]->cardCode;
			$card1DeckPosition = $pokerCards[$cardCounter]->deckPosition;
			$game1Card = GameCard::InitShuffledCard($card1Index, $card1DeckPosition, $card1Code);
			$game1Card->Insert($this->gameInstanceId, $playerStatus->playerId, 1);
			$cardCounter++;

			$card2Index = $pokerCards[$cardCounter]->cardIndex;
			$card2Code = $pokerCards[$cardCounter]->cardCode;
			$card2DeckPosition = $pokerCards[$cardCounter]->deckPosition;
			$game2Card = GameCard::InitShuffledCard($card2Index, $card2DeckPosition, $card2Code);
			$game2Card->Insert($this->gameInstanceId, $playerStatus->playerId, 2);
			$cardCounter++;
		}
		// 2. pick the next 10 to be community cards
		for ($i = 0; $i < 5; $i++) {
			// store community cards database
			$playerCardNumber = $i + 1;

			$cardIndex = $pokerCards[$cardCounter]->cardIndex;
			$deckPosition = $pokerCards[$cardCounter]->deckPosition;
			$cardCode = $pokerCards[$cardCounter]->cardCode;
			$communityCard = GameCard::InitShuffledCard($cardIndex, $deckPosition, $cardCode);
			$communityCard->Insert($this->gameInstanceId, -1, $playerCardNumber);
			$cardCounter++;
		}
		/* save the rest without player info */
		for ($i = $cardCounter; $i < count($pokerCards); $i++) {
			$cardIndex = $pokerCards[$i]->cardIndex;
			$cardCode = $pokerCards[$i]->cardCode;
			$deckPosition = $pokerCards[$i]->deckPosition;
			$remainingCard = GameCard::InitShuffledCard($cardIndex, $deckPosition, $cardCode);
			$remainingCard->Insert($this->gameInstanceId, 'null', 'null');
		}
	}

	/**
	 * Returns the number of community cards for a game instance starting with the first up to the number requested.
	 * Used to retrieve the fold, turn or river cards to the client.
	 * @param int $gInstId The game
	 * @param int $numCards The number of cards to return
	 * @return array[string] array of card codes
	 */
	public function GetSavedCommunityCardCodes($numCards = null) {
		if ($numCards == null) {
			$numCards = $this->numberCommunityCardsShown;
		}
		$gInstId = $this->gameInstanceId;
		$this->log->Debug(__FUNCTION__ . " - Requested card count: $numCards");

		if (is_null($numCards) || $numCards == 0) {
			return null;
		}

		$query = "SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = -1 AND PlayerCardNumber <= $numCards
                ORDER BY PlayerId, PlayerCardNumber";

// populate the return object
		$counter = 0;
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		while ($rowCard = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$cardCodes[$counter] = $rowCard['CardCode'];
			$counter++;
		}
		return $cardCodes;
	}

	/**
	 * This logic belongs to Poker Coordinator EndRound. No return object,
	 * communcation via queue
	 * Evaluate whether community cards need to be dealt on table based on turns,
	 * and return additional cards
	 * @return array[string] array of card codes
	 */
	public function DealCommunityCards($roundNumber) {
		if ($roundNumber > 3) {
			return;
		}
		$numberCards = $roundNumber + 2; // 3 on round 1, 4 on round 2, 5 on round 3.
		$allCards = $this->GetSavedCommunityCardCodes($numberCards);
		$length = $numberCards == 3 ? 3 : 1;
		$cardsToSend = array_slice($allCards, $this->numberCommunityCardsShown, $length);
		return $cardsToSend;
	}

	public function GetFaceCardIndexes() {
		$gameCards = new GameInstanceCards($this->gameInstanceId);
		$allCards = $gameCards->GetCardCodesForInstance(true, true);
		$indexes = array();
		foreach ($allCards as $cardCode) {
			if (CardHelper::IsFaceCard($cardCode[0])) {
				array_push($indexes, $cardCode);
			}
		}
		return $indexes;
	}

	public function SwapCardByCode($code1, $code2) {
		$card1 = $this->GetGameCardByCode($code1);
		$card2 = $this->GetGameCardByCode($code2);
		if ($card1 == null || $card2 == null) {
			return false;
		}
		$card1->UpdateCodeByDeckPosition($card2->cardCode, $card2->cardIndex);
		$card2->UpdateCodeByDeckPosition($card1->cardCode, $card1->cardIndex);
		return true;
	}

	public function SwapPlayersCards($playerId1, $gameCard1, $playerId2, $gameCard2) {
		if ($playerId2 == null) {
			$playerId2 = 'null';
		}
		if ($playerId1 == null) {
			$playerId1 = 'null';
		}
		$gInstId = $this->gameInstanceId;
		$cardNumber1 = 'null';
		if ($gameCard1->playerCardNumber != null) {
			$cardNumber1 = $gameCard1->playerCardNumber;
		}
		$cardNumber2 = 'null';
		if ($gameCard2->playerCardNumber != null) {
			$cardNumber2 = $gameCard2->playerCardNumber;
		}
		$gameCard1->UpdatePlayerByDeckPosition($playerId1, $cardNumber2);
		$gameCard2->UpdatePlayerByDeckPosition($playerId2, $cardNumber1);
	}

	/**
	 * For a given instance, return the list of all cards by code used in a game instance.
	 * @param type $gInstId
	 * @param type $unassigned: option to return all cards in the deck, not just assigned ones (default).
	 * @return array[string] array of card codes
	 */
	public function GetCardCodesForInstance($unassigned, $all = false) {
		$gInstId = $this->gameInstanceId;
		if ($all) {
			$query = "SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                ORDER BY DeckPosition";
		} else if ($unassigned) {
			$query = "SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                    AND PlayerId IS NULL
                ORDER BY DeckPosition";
		} else {
			$query = "SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId IS NOT NULL
                ORDER BY PlayerId, PlayerCardNumber";
		}
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		$pokerCardCodes = null;
		$counter = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
//$card = new PokerCard($row["PlayerCardNumber"], $row["DeckPosition"], $row["CardCode"]);
			$pokerCardCodes[$counter++] = $row["CardCode"];
		}
		return $pokerCardCodes;
	}

	/**
	 * Return the card index
	 * @param type $cardCode
	 * @return int
	 */
	public function GetGameCardByCode($cardCode) {
		$gameInstanceId = $this->gameInstanceId;
		$query = "SELECT * FROM GameCard WHERE GameInstanceID = $gameInstanceId AND 
            CardCode = '" . $cardCode . "' LIMIT 1 ";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_Rows($result) == 0) {
			return null;
		}
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$gameCard = new GameCard();
		$gameCard->mapRow($row);
		return $gameCard;
	}

}

?>
