<?php

/* Type: Object and partial response Dto.
 * Primary Table: none
 * Description: all the poker cards in a game, both community and player
 */

class GameCards {

    public $communityCards;
    public $playerHands;
    
    function __construct($communityCards, $playerHands) {
        $this->communityCards = $communityCards;
        $this->playerHands = $playerHands;
    }

}
?>
