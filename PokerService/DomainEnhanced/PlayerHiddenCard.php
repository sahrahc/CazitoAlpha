<?php

/* Type: Object and partial response Dto.
 * Primary Table: none
 * Description: all the poker cards in a game, both community and player
 */

class PlayerHiddenCard {

    public $playerId;
    public $cardCode;
    public $cardPosition;

    function __construct($playerId, $cardCode, $cardPosition) {
        $this->playerId = $playerId;
        $this->cardCode = $cardCode;
        $this->cardPosition = $cardPosition;
    }

}
?>
