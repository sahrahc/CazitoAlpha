<?php

/* Type: Object and Partial Response DTO
 * Primary Entity: GameInstance
 * Description: The end result of a game instance.
 */

class GameResultDto {

    public $playerHandDtos;
    public $winningPlayerId;
    public $playerStatusDtos;

    function __construct($playerHands, $winningPlayerId, $gInstId) {
        $this->playerHandDtos = $playerHands;
        //$this->potSize = $potSize;
        $this->winningPlayerId = $winningPlayerId;

        $this->playerStatusDtos = EntityHelper::getPlayerStatusDtosForInstance($gInstId);
    }

}

?>
