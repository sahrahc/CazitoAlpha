<?php

/* Type: Response DTO (not full response).
 * Source: PokerCard
 * Description: card within the game. The card number is for the player or community card
 * and is necessary because a card's position, not just the value, must be given.
 * DTO separate than entity is required because deck index number and code are not exposed to
 * client.
 */

class PokerCardDto {

    public $playerCardNumber;
    /* canonical form for matching image files */
    public $cardName;

    function __construct($playerCardNumber, $cardCode) {
        global $pokerCardName;
        $this->playerCardNumber = $playerCardNumber;
        $this->cardName = $pokerCardName[$cardCode];
    }

}

?>
