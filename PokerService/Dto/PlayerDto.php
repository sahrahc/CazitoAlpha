<?php

/* Type: Response DTO (not full response)
 * Primary Entity: Player
 * Description: A player at a casino table. There is no game instance status on this Dto because it is
 * used for seating.
 */

class PlayerDto {

    public $playerId;
    public $isVirtual;
    public $playerName;
    public $playerImageUrl;
    /* on a table game being played, the position of the player */
    public $casinoTableId;
    public $currentSeatNumber;
    public $reservedSeatNumber;
    /* the initial stake when joining the table */
    public $buyin;

    function __construct($id, $name, $imageUrl, $isVirtual) {
        $this->playerId = $id;
        $this->playerName = $name;
        $this->playerImageUrl = $imageUrl;
        $this->isVirtual = $isVirtual;
    }

}

?>
