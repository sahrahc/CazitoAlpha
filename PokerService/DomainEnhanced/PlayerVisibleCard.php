<?php

/* Type: 
 * Primary Table: none
 * Description: all the poker cards in a game, both community and player
 */

class PlayerVisibleCard {

    public $playerId;
    public $cardCode;
    public $expirationDateTime;
    
    function __construct($playerId, $cardCode, $expDT) {
        $this->playerId = $playerId;
        $this->cardCode = $cardCode;
        $this->expirationDateTime = $expDT;
    }

}
?>
