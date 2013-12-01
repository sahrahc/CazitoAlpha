<?php

/* Type: Response DTO
 * Primary Source: None
 * Description: The list of cards that a player has hidden under the table or sleeve
 */

class CardNameListDto {

    public $cardNameList;

    public function __construct($cardNameList) {
        $this->cardNameList = $cardNameList;
    }

}

?>
