<?php

/* Type: Object and partial response Dto.
 * Primary Table: GameCard
 * Description: card within the game. The card number is for the player or community card
 * and is necessary because a card's position, not just the value, must be given.
 */

class PokerCard {
    public $playerCardNumber;
    public $cardIndex; /* REMOVE when replacing 2+2 poker evaluator */
    /* the position in the deck. Cheating will cause the cards in the deck to be used out of order */
    public $deckPosition;
    public $cardCode;

    function __construct($pCardNum, $deckPos, $cardCode) {
        $this->playerCardNumber = $pCardNum;
        $this->deckPosition = $deckPos;
        $this->cardCode = $cardCode;
    }

}

?>
