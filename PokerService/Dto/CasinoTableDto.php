<?php

/* Type: Response DTO
 * Primary Source: CasinoTable
 * Description: A casino table that is available for a user to join.
 */

class CasinoTableDto {

    // from source --------------------------
    public $casinoTableId;
    public $casinoTableName;
    public $gameSessionId;
    // additional data ----------------------
    public $gameInstanceId;
    // waiting players do not have seat numbers
    public $playerDtos;
    // if the request was unauthenticated an id is generated.
    public $userPlayerId;

    public function __construct($entity) {
        $dto->casinoTableId = $entity->id;
        $dto->casinoTableName = $entity->name;
        $dto->gameSessionId = $entity->currentGameSessionId;
    }

}

?>
