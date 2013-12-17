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
//	public $isWinningHand;
	// transient 2+2
	public $handInfo;
	public $handCategory;
	public $rankWithinCategory;
	public $currentStake;
	public $status;
	public $gameInstanceId;
	private $history;

	function __construct($playerId, $pokerCard1, $pokerCard2) {
		$this->history = Logger::getLogger(__CLASS__);
		$this->playerId = $playerId;
		$this->pokerCard1 = $pokerCard1;
		$this->pokerCard2 = $pokerCard2;
	}

	public function SetPlayerState($gameInstanceId, $currentStake, $status) {
		$this->gameInstanceId = $gameInstanceId;
		$this->currentStake = $currentStake;
		$this->status = $status;
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

	public function Update() {
		$statusDTQ = "'" . Context::GetStatusDTString() . "'";
		$playerId = $this->playerId;
		$handType = $this->pokerHandType;
/*		$status = $this->status;

		$stake = $this->currentStake; */
		// 2+2 evaluator only
		$handInfo = $this->handInfo;
		$handCategory = $this->handCategory;
		$handRank = $this->rankWithinCategory;
		$vars = "LastUpdateDateTime, HandType, HandInfo, HandCategory, HandRankWithinCategory";
		$values = "$statusDTQ, '$handType', $handInfo, $handCategory, $handRank";
		$where = "PlayerId = $playerId AND GameInstanceId = $this->gameInstanceId";
		$event = "UPDATE PlayerState SET LastUpdateDateTime = $statusDTQ, "
				. "HandType = '$handType', "
				. "HandInfo = $handInfo, "
				. "HandCategory = $handCategory, "
				. "HandRankWithinCategory = $handRank WHERE $where";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$log = $vars . " -TO- " . $values . " -WHERE- $where";
		$this->history->info("UPDATED " . $eventCount . ": $log");
	}

}

?>
