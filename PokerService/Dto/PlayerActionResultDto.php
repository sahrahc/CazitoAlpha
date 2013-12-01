<?php

/**
 * Null means the move is not allowed. Fold is always allowed.
 */
class PlayerActionResultDto {
    public $gameInstanceId;
    public $nextPokerMoveDto;
    public $gameResultDto;
    public $cardsToSend;
    public $playerStatusDto;

    public function __construct($nextPokerMove) {
        $this->nextPokerMoveDto = new NextPokerMoveDto($nextPokerMove);
        $this->gameInstanceId = $nextPokerMove->gameInstanceId;
    }
}
?>
