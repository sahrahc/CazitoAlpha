<?php

/* Type: Request DTO
 * Primary Source: None
 * Description: The metadata of a request to apply a cheater option
 */

class CheatRequestDto {

    public $itemType;
    // optional data - multipurpose
    public $playerCardNumber;
    // optional data - using hidden list
    public $hiddenCardNumber;
    // optional data - changing another user's cards
    public $otherPlayerId;
    public $cardName;
    public $cardNameList;
}

?>
