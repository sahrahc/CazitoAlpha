<?php

/* Type: Response DTO
 * Primary Source: CasinoTable
 * Description: A casino table that is available for a user to join.
 */

class CasinoTableDto {

    // from source --------------------------
    public $casinoTableId;
    public $casinoTableName;
    public $casinoTableDescription;
    public $tableMinimum;
    public $numberSeats;
    public $numberCurrentPlayers;
    public $numberWaitingPlayers;
    public $gameSessionId;
    // additional data ----------------------
    //public $gameInstanceId;
    // waiting players do not have seat numbers
    //public $playerDtos;
    // if the request was unauthenticated an id is generated.
    //public $userPlayernId;

    public function __construct($entity) {
        $this->casinoTableId = $entity->id;
        $this->casinoTableName = $entity->name;
        $this->casinoTableDescription = $entity->description;
        $this->tableMinimum = $entity->tableMinimum;
        $this->numberSeats = $entity->numberSeats;
        $this->gameSessionId = $entity->currentGameSessionId;
    }

}

?>
