<?php

/* Type: Object and Partial Response DTO
 * Primary Entity: None
 * Description: An instance of a player making a bet.
 */

class BetDto {

    public $playerId;
    public $betSize;

    function __construct($playerId, $betSize) {
        $this->playerId = $playerId;
        $this->betSize = $betSize;
    }

}

?>