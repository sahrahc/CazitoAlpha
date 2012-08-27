<?php

/* Type: Response DTO
 * Primary Source: None
 * Description: The cards in the stack that are revealed to a specific user.
 */

class NextCardListDto {

    public $playerId;
    public $cardNameList;

    public function __construct($playerId, $cardNameList) {
        $this->playerId = $playerId;
        $this->cardNameList = $cardNameList;
    }

}

?>
