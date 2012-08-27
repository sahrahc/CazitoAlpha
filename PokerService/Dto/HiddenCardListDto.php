<?php

/* Type: Response DTO
 * Primary Source: None
 * Description: The list of cards that a player has hidden under the table or sleeve
 */

class HiddenCardListDto {

    public $playerId;
    public $cardNameList;

    public function __construct($playerId, $cardNameList) {
        $this->playerId = $playerId;
        $this->cardNameList = $cardNameList;
    }

}

?>
