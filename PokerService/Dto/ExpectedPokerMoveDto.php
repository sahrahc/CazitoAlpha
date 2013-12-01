<?php

/**
 * Null means the move is not allowed. Fold is always allowed.
 */
final class ExpectedPokerMoveDto {
    public $gameInstanceId;
    public $playerId;
    public $expirationDate;
    public $callAmount;
    public $isCheckAllowed;
    public $raiseAmount;

    public function __construct($pokerMove) {
        global $dateTimeFormat;
        $this->gameInstanceId = $pokerMove->gameInstanceId;
        $this->playerId = $pokerMove->playerId;
        $this->expirationDate = $pokerMove->expirationDate->format($dateTimeFormat);
        $this->callAmount = $pokerMove->callAmount;
        $this->isCheckAllowed = $pokerMove->isCheckAllowed;
        $this->raiseAmount = $pokerMove->raiseAmount;
    }
}
?>
