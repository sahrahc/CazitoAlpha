<?php

/* * ************************************************************************************** */

/**
 * Helper class with static methods for retrieving and processing game cards.  The card helper retrieves data and context from the data store while applying poker rules.
 */
class CardHelper {

	private static $log = null;

	public static function log() {
		if (is_null(self::$log))
			self::$log = Logger::getLogger(__CLASS__);
		return self::$log;
	}

	/**
	 * Get a deck of 52 cards randomly shuffled for use in a game. 
	 * There is an index value for the card order after shuffling. 
	 * @return GameCard array
	 */
	private static function shuffle_assoc(&$array) {
		$keys = array_keys($array);

		shuffle($keys);

		foreach ($keys as $key) {
			$new[$key] = $array[$key];
		}

		$array = $new;

		return true;
	}

	public static function initRandomDeck() {
		global $CARDS;
		// FIXME: cache these!
		/* $DECK = EvalHelper::init2x2deck();
		  $indexArray = range(1, 52);
		  shuffle($indexArray);

		  for ($i = 1; $i <= 52; $i++) {

		  $cardCode = EvalHelper::findCardCode($DECK[$indexArray[$i - 1]]);

		  $returnList[$i] = GameCard::InitShuffledCard($indexArray[$i - 1], $i, $cardCode);
		  }
		 * 
		 */
		$newDeck = range(1,52);
		shuffle($newDeck);
		for ($i = 0; $i <= 51; $i++) {
			// return list of GameCard objects
			$cardCode = array_search($newDeck[$i], $CARDS);
			$returnList[$i] = GameCard::InitShuffledCard($newDeck[$i], $i, $cardCode);
		}

		return $returnList;
	}

	/**
	 * Given a list of 2-digit card codes, return a list of GameCards
	 * @param type $testCardCodes
	 * @return type
	 */
	public static function initTestingDeck($testCardCodes) {
		global $CARDS;
		/*
		  $DECK = EvalHelper::init2x2deck();
		  for ($i = 0; $i < count($testCardCodes); $i++) {
		  $ranks = array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A');
		  //$ranks = array('A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2');
		  $rank = array_search($testCardCodes[$i][0], $ranks);
		  $suitBit = EvalHelper::getSuitBit($testCardCodes[$i][1]);
		  $cardIndex = EvalHelper::find2x2DeckIndex($rank, $suitBit, $DECK);

		  $returnList[$i + 1] = GameCard::InitShuffledCard($cardIndex, $i, $testCardCodes[$i]);
		  }
		 *
		 */
		for ($i = 0; $i < count($testCardCodes); $i++) {
			$returnList[$i] = GameCard::InitShuffledCard($CARDS[$testCardCodes[$i]], $i, $testCardCodes[$i]);
		}
		return $returnList;
	}

	/**
	 * FIXME: 2+2 EVALUATOR SPECIFIC - first parameter cards should be changed to cardCodes
	 * Wrapper for Eval Helper API to convert it into domain specific object.
	 * Given a set of seven cards, return the hand information, which includes evaluator's specific data (e.g., 2+2 evaluator's info, category and rank within the category) and the English name of the hand.
	 * This function and the next getHigherCard are used together.
	 * @param array(int) $cards The list of seven cards.
	 * @param PlayerHand $playerHand The player information, which gets updated and returned
	 * @return PlayerHand The updated player hand.
	 */
	public static function identifyPlayerHand($cards, &$playerHand) {
		global $HANDTYPES;
		self::log()->debug(__FUNCTION__ . " - cards given " . json_encode($cards));
		self::log()->debug(__FUNCTION__ . " - player hands given " . json_encode($playerHand));
		//$pH = $playerHand;

		$playerHand->handInfo = EvalHelper::getHandValue($cards);
		$playerHand->handCategory = EvalHelper::urshift($playerHand->handInfo, 12); // 12
		$playerHand->pokerHandType = $HANDTYPES[$playerHand->handCategory];
//		$playerHand->pokerHandType = EvalHelper::findHandName($playerHand->handCategory);
		$playerHand->rankWithinCategory = $playerHand->handInfo & 0x00000FFF;

		//return $pH;
	}

	/**
	 * Takes the highest hand to date and compares to another given hand. Returns the highest card after the comparison.
	 * @param HighestHand $hH An object with the player hand info without the player
	 * @param PlayerHand $playerHand The player info, poker cards and hand info.
	 * @return type
	 */
	public static function getHigherCard($hH, $playerHand) {
		$hH2 = $hH;
		if ($hH->handCategory < $playerHand->handCategory) {
			$hH2->handCategory = $playerHand->handCategory;
			$hH2->rankWithinCategory = $playerHand->rankWithinCategory;
			$hH2->winningPlayerId = $playerHand->playerId;
		} else if ($hH->handCategory === $playerHand->handCategory AND
				$hH->rankWithinCategory < $playerHand->rankWithinCategory) {
			$hH2->handCategory = $playerHand->handCategory;
			$hH2->rankWithinCategory = $playerHand->rankWithinCategory;
			$hH2->winningPlayerId = $playerHand->playerId;
		}
		return $hH2;
	}

	/**
	 * Get the player hand information. Used on every non virtual player 
	 * at the beginning of the game.
	 * @param type $pId: The player whose hand is being sent
	 * @param type $gInstId
	 * @return PlayerHandDto
	 */
	public static function getPlayerHandDto($pId, $gInstId) {
		$result = executeSQL("SELECT * from GameCard where GameInstanceId = $gInstId
                AND PlayerId = $pId ORDER BY PlayerCardNumber", __FUNCTION__ . "
                : ERROR selecting game card for game instance id $gInstId and player
                id $pId");
		if (mysql_num_rows($result) == 0) {
			return null;
		}

		$i = 0;
		while ($row = mysql_fetch_array($result)) {
			$pokerCardCode[$i++] = $row["CardCode"];
		}

		return new PlayerHandDto($pId, $pokerCardCode[0], $pokerCardCode[1]);
	}

	/**
	 * Update the value of one of a player hand's two cards.
	 * @param type $pId
	 * @param type $gInstId
	 * @param type $cNumber
	 * @param type $cIndex
	 * @param type $cCode 
	 */
	public static function updatePlayerCard($pId, $gInstId, $cNumber, $cIndex, $cCode) {
		executeSQL("UPDATE GameCard SET CardIndex = $cIndex, CardCode = '$cCode'
                WHERE GameInstanceId = $gInstId AND PlayerId = $pId and
                PlayerCardNumber = $cNumber", __FUNCTION__ . ": 
                Error updating game card for instance $gInstId,
                player $pId and card number $cNumber");
	}

	public static function IsFaceCard($cardCode) {
		return in_array($cardCode, array('J', 'Q', 'K'));
	}

}

?>
