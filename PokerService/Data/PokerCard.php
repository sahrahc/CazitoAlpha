<?php

/* Type: Object and partial response Dto.
 * Primary Entity: None
 * Description: poker card index and label. Generally used to return community
 *      card info.
 */

class PokerCard {

    public $cardNumber;
    public $cardIndex;
    public $cardName;
    // optional
    public $suit;
    public $rank;

    function __construct($cardNumber, $cardIndex, $cardName) {
        $this->cardNumber = $cardNumber;
        $this->cardIndex = $cardIndex;
        $this->cardName = $cardName;
    }

}

?>
