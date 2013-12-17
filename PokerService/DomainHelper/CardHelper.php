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
		$query = "SELECT * from GameCard where GameInstanceId = $gInstId
                AND PlayerId = $pId ORDER BY PlayerCardNumber";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		if (mysql_num_rows($result) == 0) {
			return null;
		}

		$i = 0;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$pokerCardCode[$i++] = $row["CardCode"];
		}

		return new PlayerHandDto($pId, $pokerCardCode[0], $pokerCardCode[1]);
	}

	public static function IsFaceCard($cardCode) {
		return in_array($cardCode, array('J', 'Q', 'K'));
	}

}

?>
