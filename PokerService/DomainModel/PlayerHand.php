<?php

/* Type: Object and partial response Dto.
 * Primary Table: PlayerState
 * Description: the cards held by a player.  */

class PlayerHand {

	public $playerId;
	public $pokerCard1;
	public $pokerCard2;
	// optional
	public $pokerHandType;
	public $isWinningHand;
	// transient 2+2
	public $handInfo;
	public $handCategory;
	public $rankWithinCategory;

	function __construct($playerId, $pokerCard1, $pokerCard2) {
		$this->playerId = $playerId;
		$this->pokerCard1 = $pokerCard1;
		$this->pokerCard2 = $pokerCard2;
	}

	public function getPlayerCardFromHand($expSuit, $cardNumber) {
		global $pokerCardName;

		$suitValue = null;
		if ($cardNumber == 1) {
			$cardName = $pokerCardName[$this->pokerCard1->cardCode];
		} else {
			$cardName = $pokerCardName[$this->pokerCard2->cardCode];
		}
		if (!is_null($expSuit) && strpos($cardName, $expSuit) !== false) {
			$suitValue = $expSuit;
		}
		return new PlayerCardDto($this->playerId, $cardNumber, null, $suitValue);
	}

}

?>
