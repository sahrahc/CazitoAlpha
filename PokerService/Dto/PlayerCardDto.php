<?php

/* Type: Response DTO
 * Primary Source: None
 * Description: Card information that the user only has because he is cheating.
 * Use as CheatedHands (new hand) and CheatedCards (info on other player hand).
 * Former case is single, latter case is list
 */

class PlayerCardDto {

    public $playerId;
    public $playerCardNumber;
    public $cardCode;
    public $suit;

    public function __construct($pId, $pCardNum, $cardName, $suit) {
        $this->playerId = $pId;
        $this->playerCardNumber = $pCardNum;
        $this->cardCode = $cardName;
        $this->suit = $suit;
    }

}

?>
