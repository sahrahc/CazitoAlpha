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
    
    /* same as the constructor but for an array*/
    public static function mapPlayers($playerList) {
                $obj = null;
        for ($i = 0, $l = count($playerList); $i < $l; $i++) {
            $obj[$i] = new PlayerDto(null);
            $obj[$i]->playerId = $playerList[$i]->id;
            $obj[$i]->playerName = $playerList[$i]->name;
            $obj[$i]->playerImageUrl = $playerList[$i]->imageUrl;
            $obj[$i]->isVirtual = $playerList[$i]->isVirtual;
            $obj[$i]->casinoTableId = $playerList[$i]->currentCasinoTableId;
            $obj[$i]->currentSeatNumber = $playerList[$i]->currentSeatNumber;
            $obj[$i]->reservedSeatNumber = $playerList[$i]->reservedSeatNumber;
        }
        return $obj;

    }
}

?>
