<?php

/* Type: Request DTO
 * Primary Source: None
 * Description: The metadata of a request to apply a cheater option
 */

class CheatRequestDto {

    public $itemType;
    public $userPlayerId;
    public $gameSessionId;
    // optional data
    public $otherPlayerId;
    public $cardToUseLabel;
}

?>
