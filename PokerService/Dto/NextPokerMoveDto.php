<?php

/**
 * Null means the move is not allowed. Fold is always allowed.
 */
final class NextPokerMoveDto {
    public $gameInstanceId;
    public $nextPlayerId;
    public $expirationDate;
    public $isEndGameNext;
    public $callAmount;
    public $checkAmount;
    public $raiseAmount;

    public function __construct($nextPokerMove) {
        $this->gameInstanceId = $nextPokerMove->gameInstanceId;
        $this->nextPlayerId = $nextPokerMove->playerId;
        $this->expirationDate = $nextPokerMove->expirationDate;
        $this->isEndGameNext = $nextPokerMove->isEndGameNext;
        $this->callAmount = $nextPokerMove->callAmount;
        $this->checkAmount = $nextPokerMove->checkAmount;
        $this->raiseAmount = $nextPokerMove->raiseAmount;
    }
}
?>
