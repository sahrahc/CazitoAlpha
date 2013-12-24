<?php

/* Type: Response DTO (not full response)
 * Primary Entity: Player
 * Description: Player information when first signed in and while joined to a  
 * casino table - has seating info without active participation in a game 
 * (wait list)
 * PlayerName and playeImageUrl are only returned when the user first
 * logs in.
 */

class PlayerDto {

    public $playerId;
    public $playerName;
    public $playerImageUrl;
    public $isVirtual;
    /* on a table game being played, the position of the player */
    public $casinoTableId;
    public $currentSeatNumber;
    public $reservedSeatNumber;
    /* the initial stake when joining the table */
    public $buyIn;

    /*
     * Use constructor when first logged in
     */
    function __construct($entity) {
    //function __construct($id, $name, $imageUrl, $isVirtual) {
        $this->playerId = $entity->id;
        $this->playerName = $entity->name;
        $this->playerImageUrl = $entity->imageUrl;
        $this->isVirtual = $entity->isVirtual;
        $this->casinoTableId = $entity->currentCasinoTableId;
        $this->currentSeatNumber = $entity->currentSeatNumber;
        $this->reservedSeatNumber = $entity->reservedSeatNumber;
    }
}
?>
