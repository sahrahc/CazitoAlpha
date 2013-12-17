<?php

/* Type: Object and partial response Dto.
 * Primary Table: GameCard
 * Description: card within the game. The card number is for the player or community card
 * and is necessary because a card's position, not just the value, must be given.
 */

class GameCard {

	public $gameInstanceId;
	public $playerId;
	public $playerCardNumber;
	/* the position in the deck. Cheating will cause the cards in the deck to be used out of order */
	public $deckPosition;
	public $cardCode;
	public $cardIndex; // used after shuffling deck, only for 2+2
	private $history;

	public function __construct() {
		$this->history = Logger::getLogger(__CLASS__);
	}

	public function SetInstance($gInstId, $pId, $cardIdx) {
		$this->gameInstanceId = $gInstId;
		$this->playerId = $pId;
		$this->cardIndex = $cardIdx;
	}

	public static function InitShuffledCard($cardIndex, $deckPosition, $cardCode) {
		$gameCard = new GameCard();
		$gameCard->cardIndex = $cardIndex;
		$gameCard->deckPosition = $deckPosition;
		$gameCard->cardCode = $cardCode;
		return $gameCard;
	}

	public static function InitPlayerCard($playerCardNumber, $deckPosition, $cardCode) {
		$gameCard = new GameCard();
		$gameCard->playerCardNumber = $playerCardNumber;
		$gameCard->deckPosition = $deckPosition;
		$gameCard->cardCode = $cardCode;
		return $gameCard;
	}

	public static function InitInstanceCard($gInstId, $deckPosition, $code) {
		$gameCard = new GameCard();
		$gameCard->gameInstanceId = $gInstId;
		$gameCard->deckPosition = $deckPosition;
		$gameCard->cardCode = $code;
		return $gameCard;
	}

	/**
	 * The player and game instance only needed to save in database
	 * cardIndex to be removed when replacing 2+2 evaluator
	 * @param type $gameInstanceId
	 * @param type $playerId
	 * @param type $cardIndex
	 */
	public function Insert($gameInstanceId, $playerId, $playerCardNumber) {
		$vars = "GameInstanceId, PlayerId, PlayerCardNumber, DeckPosition, CardCode, CardIndex";
		$values = "$gameInstanceId, $playerId, $playerCardNumber, $this->deckPosition, "
                 . "'$this->cardCode', $this->cardIndex";
		$event = "INSERT INTO GameCard ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
	}

	public function UpdateCodeByDeckPosition($code, $index) {
		$vars = "CardCode, CardIndex";
		$values = "'$code', $index";
		$where = "GameInstanceId = $this->gameInstanceId AND DeckPosition = $this->deckPosition";
		$event = "UPDATE GameCard SET CardCode = '$code', CardIndex = $index WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
			$log = $vars . " -TO- " . $values . " -WHERE- $where";
			$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	public function UpdatePlayerByDeckPosition($playerId, $cardNumber) {
		$vars = "PlayerId, PlayerCardNumber";
		$values = "$playerId, $cardNumber";
		$where = "GameInstanceId = $this->gameInstanceId AND DeckPosition = $this->deckPosition";
		$event = "UPDATE GameCard SET PlayerId = $playerId, "
				. "PlayerCardNumber = $cardNumber WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
			$log = $vars . " -TO- " . $values . " -WHERE- $where";
			$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * Update the value of one of a player hand's two cards.
	 * @param type $pId
	 * @param type $gInstId
	 * @param type $cNumber
	 * @param type $cIndex
	 * @param type $cCode 
	 */
	public function UpdatePlayerCard() {
		$vars = "CardIndex, CardCode";
		$values = "$this->cardIndex, $this->cardCode";
		$where = "GameInstanceId = $this->gameInstanceId AND PlayerId = $this->playerId AND "
                . "PlayerCardNumber = $this->playerCardNumber";
		$event = "UPDATE GameCard SET CardIndex = $this->cardIndex, "
				. "CardCode = '$this->cardCode' WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
			$log = $vars . " -TO- " . $values . " -WHERE- $where";
			$this->history->info("UPDATED " . $eventCount . ": $log");
	}

	/**
	 * The player state expires with the game instance
	 * @param type $endString
	 * @return type
	 */
	public static function DeleteExpired($endString) {
		$query = "SELECT * FROM GameCard WHERE GameInstanceId in
            (SELECT ID FROM GameInstance WHERE LastUpdateDateTime <= '$endString')";
		$result = executeSQL($query, __CLASS__ . "-" . __FUNCTION__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$gameCard = new GameCard();
			$gameCard->gameInstanceId = (int) $row["GameInstanceId"];
			$gameCard->deckPosition = (int) $row["DeckPosition"];
			$gameCard->Delete($row);
		}
	}

	public function Delete($row) {
		if (is_null($row)) {$row = $this;}
		$where = "GameInstanceId = $this->gameInstanceId AND DeckPosition = $this->deckPosition";
		$event = "DELETE FROM GameCard WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("DELETED " . $eventCount . ": $where -RECORD- " . json_encode($row));
	}

	public function mapRow($row) {
		$this->gameInstanceId = (int) $row['GameInstanceId'];
		$this->playerId = (int) $row['PlayerId'];
		$this->playerCardNumber = (int) $row['PlayerCardNumber'];
		$this->deckPosition = (int) $row['DeckPosition'];
		$this->cardCode = $row['CardCode'];
		$this->cardIndex = (int) $row['CardIndex'];
	}

}

?>
