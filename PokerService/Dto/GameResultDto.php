<?php

/* Type: Object and Partial Response DTO
 * Primary Entity: GameInstance
 * Description: The end result of a game instance.
 */

class GameResultDto {

    public $playerHands;
    public $winningPlayerId;
    public $playerStatusDtos;

    function __construct($playerHands, $winningPlayerId, $gInstId) {
        $this->playerHands = $playerHands;
        //$this->potSize = $potSize;
        $this->winningPlayerId = $winningPlayerId;

        $this->playerStatusDtos = EntityHelper::getPlayerStatusDtosForInstance($gInstId);
    }

}

?>
