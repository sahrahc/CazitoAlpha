<?php

/* Type: Response DTO
 * Primary Source: None
 * Description: Card information that the user only has because he is cheating.
 */

class CheaterCardDto {

    public $playerId;
    public $cardNumber;
    public $cardName;
    public $suit;

    public function __construct($playerId, $cardNumber, $cardName, $suit) {
        $this->playerId = $playerId;
        $this->cardNumber = $cardNumber;
        $this->cardName = $cardName;
        $this->suit = $suit;
    }

}

?>
