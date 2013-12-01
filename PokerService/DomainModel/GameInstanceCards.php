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
	 * Only used once, to get all the cards in order to identify the winner and publish everyone's hands at the end of the game.
	 * @return GameInstanceCards 
	 */
	public function GetSavedCards($excludeFoldCondition = false) {
		// get all the cards, order with community cards first.
		$result = executeSQL("SELECT g.*, ps.Status AS Status, ps.HandType AS HandType 
			FROM GameCard g 
                LEFT JOIN PlayerState ps ON g.GameInstanceId = ps.GameInstanceId
                AND g.PlayerId = ps.PlayerId 
				WHERE g.GameInstanceId = $this->gameInstanceId
                AND ((g.PlayerId is not null AND ps.PlayerId is not null)
				  OR (g.PlayerId = -1 AND ps.PlayerId is null))
                ORDER BY g.PlayerId, PlayerCardNumber", __FUNCTION__ . "
                : Error selecting all GameCard for instance id $this->gameInstanceId");

		// initialize
		$playerIndex = 0; // index on array of players
		$ccIndex = 0;  // index on array of community cards
		$playerHands = null;
		$communityCards = null;
		$prevPlayerId = null;
		// this won't work if $result is not sorted by playerid
		while ($rowCard = mysql_fetch_array($result)) {
			$isNotFolded = $rowCard["Status"] != PlayerStatusType::FOLDED;
			if ($excludeFoldCondition) {
				$isNotFolded = true;
			}
			if ($rowCard["PlayerId"] == -1) {
				// process community cards
				$communityCards[$ccIndex] = GameCard::InitPlayerCard(
						(int)$rowCard['PlayerCardNumber'], 
						(int)$rowCard['DeckPosition'], $rowCard['CardCode']);
				$communityCards[$ccIndex++]->cardIndex = (int)$rowCard["CardIndex"];
			} else if ( //$rowCard["Status"] != PlayerStatusType::LEFT &&
					$isNotFolded) {
				// get first card
					$gameCard1 = GameCard::InitPlayerCard(
							(int)$rowCard['PlayerCardNumber'],
							(int)$rowCard['DeckPosition'], $rowCard['CardCode']);
					$gameCard1->cardIndex = (int)$rowCard["CardIndex"];
					$playerHands[$playerIndex] = new PlayerHand((int)$rowCard['PlayerId'], $gameCard1, null);
					$playerHands[$playerIndex]->pokerHandType = $rowCard['HandType'];
					$playerHands[$playerIndex]->isWinningHand = 0;
					if ($rowCard["Status"] === PlayerStatusType::WON) {
						$playerHands[$playerIndex]->isWinningHand = 1;
					}
				/*if (is_null($prevPlayerId) || $prevPlayerId != $rowCard["PlayerId"]) {
					$playerIndex = is_null($prevPlayerId) ? 0 : $playerIndex + 1; 
					// Not validating playercardnumber, in poker there is only two
					B&// and the insert needs to make sure the values are only 1 and 2 and
					// both are present. Anything else is data becoming corrupted.
					$gameCard1 = GameCard::InitPlayerCard($rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
					$gameCard1->cardIndex = $rowCard["CardIndex"];
					$playerHands[$playerIndex] = new PlayerHand($rowCard['PlayerId'], $gameCard1, null);
				} else { 
				 * 
				 */
					// get second card
					$rowCard2 = mysql_fetch_array($result);
					$gameCard2 = GameCard::InitPlayerCard(
							(int)$rowCard2['PlayerCardNumber'], 
							(int)$rowCard2['DeckPosition'], $rowCard2['CardCode']);
					$gameCard2->cardIndex = (int)$rowCard2["CardIndex"];
					$playerHands[$playerIndex++]->pokerCard2 = $gameCard2;
					/* $gameCard2 = GameCard::InitPlayerCard(
									$rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
					$gameCard2->cardIndex = $rowCard["CardIndex"];
					$playerHands[$playerIndex]->pokerCard2 = $gameCard2;
					// increase index when second and last card is found
					 * 
					 */
				}
					 /*
				$prevPlayerId = $rowCard["PlayerId"];
					 * 
					 */
		}

        $this->communityCards = $communityCards;
        $this->playerHands = $playerHands;
	}

    /**
     * Separate saving the entity into the database to EntityHelper
     * Assign cards dealt on a game to players and store them in database. Returns the hand for the user who requested the operation, which is used when a game first starts.
     * This method is an optimization in that it combines two operations in one.
     * Restrictions: Must be called after game reset.
     * shuffling of cards (EvalHelper) either here or called from PokerCoordinator
     * cleaner that way
     * @param array($pokerCard) $pokerCards: list of cards, randomly shuffled, for all players + 5 community cards
     */
    public function InitDealGameCards($testCardCodes = null) {
        if (is_null($testCardCodes)) {
            $pokerCards = CardHelper::initRandomDeck();
        }
        else {
            $pokerCards = CardHelper::initTestingDeck($testCardCodes);
        }
        // NOTE: the hands don't get saved on the player state until all the hands are known.
        // 1. player cards; no need to worry about player status
        $playerStatuses = PlayerInstance::GetPlayerInstancesForGame($this->gameInstanceId);

        $cardCounter = 0;
        //$this->log->debug(__FUNCTION__ . " - number players in db: $numberRows");
        foreach($playerStatuses as $playerStatus) {
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
	 * @return array[string] array of card names
	 */
	public function GetSavedCommunityCardDtos($numCards= null) {
		if ($numCards == null) {
			$numCards = $this->numberCommunityCardsShown;
		}
		$gInstId = $this->gameInstanceId;
		$this->log->Debug(__FUNCTION__ . " - Requested card count: $numCards");

		if (is_null($numCards) || $numCards == 0) {
			return null;
		}

		$resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = -1 AND PlayerCardNumber <= $numCards
                ORDER BY PlayerId, PlayerCardNumber", __FUNCTION__ . ":
                Error selecting $numCards cards from GameCard instance id $gInstId");

// populate the return object
		$counter = 0;
		while ($rowCard = mysql_fetch_array($resultCard)) {
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
     */
    public function DealCommunityCards($roundNumber) {
        if ($roundNumber > 3) {
            return;
        }
        $numberCards = $roundNumber + 2; // 3 on round 1, 4 on round 2, 5 on round 3.
        $allCards = $this->GetSavedCommunityCardDtos($numberCards);
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
		$gameInstanceId = $this->gameInstanceId;
		$result1 = executeSQL("SELECT DeckPosition FROM GameCard WHERE "
				. "GameInstanceId = $gameInstanceId AND CardCode = '$code1'"
				, __FUNCTION__ . ": Error selecting GameCard deck position for card code $code1");
		$result2 = executeSQL("SELECT DeckPosition FROM GameCard WHERE "
				. "GameInstanceId = $gameInstanceId AND CardCode = '$code2'"
				, __FUNCTION__ . ": Error selecting GameCard deck position for card code $code2");
		$row1 = mysql_fetch_array($result1);
		$cardIndex1 = $row1['DeckPosition'];
		$row2 = mysql_fetch_array($result2);
		$cardIndex2 = $row2['DeckPosition'];
		if ($cardIndex1 == null || $cardIndex2 == null) {
			return false;
		}
		executeSQL("UPDATE GameCard SET CardCode = '$code1'"
				. "WHERE GameInstanceId = $gameInstanceId AND DeckPosition = $cardIndex2", 
				__FUNCTION__ . " Error updating Game Card code $code1 for instance $gameInstanceId");
		executeSQL("UPDATE GameCard SET CardCode = '$code2'"
				. "WHERE GameInstanceId = $gameInstanceId AND DeckPosition = $cardIndex1",
				__FUNCTION__ . " Error updating Game Card code $code2 for instance $gameInstanceId");
		return true;
	}
	
	public  function SwapPlayersCards($playerId1, $gameCard1, $playerId2, $gameCard2) {
		if ($playerId2 == null) {$playerId2 = 'null';}
		if ($playerId1 == null) {$playerId1 = 'null';}
		$gInstId = $this->gameInstanceId;
		$cardNumber1 = 'null';
		if ($gameCard1->playerCardNumber != null) {
			$cardNumber1 = $gameCard1->playerCardNumber;
		}
		$cardNumber2 = 'null';
		if ($gameCard2->playerCardNumber != null) {
			$cardNumber2 = $gameCard2->playerCardNumber;
		}
/*		executeSQL("UPDATE GameCard SET PlayerId = -1, PlayerCardNumber = 5 WHERE
            GameInstanceId = $gInstId AND DeckPosition = $availGameCard->deckPosition", __FUNCTION__ . ":
                Error updating DeckPosition $availGameCard->deckPosition instance $gInstId to be
                the next river card");
		executeSQL("UPDATE GameCard SET PlayerId = null, PlayerCardNumber = null WHERE
            GameInstanceId = $gInstId AND DeckPosition = $curGameCard->deckPosition", __FUNCTION__ . ":
                Error removing DeckPosition $curGameCard->deckPosition as river card for instance $gInstId"); */
		executeSQL("UPDATE GameCard SET PlayerId = $playerId1, "
				. "PlayerCardNumber = $cardNumber2 WHERE
            GameInstanceId = $gInstId AND DeckPosition = $gameCard1->deckPosition", __FUNCTION__ . ":
                Error updating DeckPosition $gameCard1->deckPosition instance $gInstId for player $playerId1");
		executeSQL("UPDATE GameCard SET PlayerId = $playerId2, "
				. "PlayerCardNumber = $cardNumber1 WHERE
            GameInstanceId = $gInstId AND DeckPosition = $gameCard2->deckPosition", __FUNCTION__ . ":
                Error updating DeckPosition $gameCard2->deckPosition instance $gInstId for player $playerId2");
		
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
			$resultCard = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId is null ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
		}
		else {
		$resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = $pId AND PlayerCardNumber = $cardNum", __FUNCTION__ . ":
                Error selecting GameCards for instance id $gInstId player id $pId
                and card number $cardNum");
		}
// populate the return object
		$rowCard = mysql_fetch_array($resultCard);

		$gameCard = GameCard::InitPlayerCard($cardNum, $rowCard['DeckPosition'], $rowCard['CardCode']);
		return $gameCard;
	}

	/**
	 * For a given instance, return the list of all cards by code used in a game instance.
	 * @param type $gInstId
	 * @param type $unassigned: option to return all cards in the deck, not just assigned ones (default).
	 * @return string array of card codes
	 */
	public function GetCardCodesForInstance($unassigned, $all=false) {
		$gInstId = $this->gameInstanceId;
		$error_msg = __FUNCTION__ . ": Error selecting GameCard for instance $gInstId";
		if ($all) {
			$result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                ORDER BY DeckPosition", $error_msg);	
		}
		else if ($unassigned) {
			$result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                    AND PlayerId IS NULL
                ORDER BY DeckPosition", $error_msg);
		} else {
			$result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId IS NOT NULL
                ORDER BY PlayerId, PlayerCardNumber", $error_msg);
		}
		$pokerCardCodes = null;
		$counter = 0;
		while ($row = mysql_fetch_array($result)) {
//$card = new PokerCard($row["PlayerCardNumber"], $row["DeckPosition"], $row["CardCode"]);
			$pokerCardCodes[$counter++] = $row["CardCode"];
		}
		return $pokerCardCodes;
	}

	public function GetCardIndexForInstance( $cardCode) {
		$gameInstanceId = $this->gameInstanceId;
		$result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceID = $gameInstanceId AND 
            CardCode = '" . $cardCode . "' LIMIT 1 "
				, __FUNCTION__ . "Error selecting CardCode " . $cardCode . " from GameCard");
		$row = mysql_fetch_array($result);
		$cardIndex = $row['CardIndex'];
		return $cardIndex;
	}

}
?>
