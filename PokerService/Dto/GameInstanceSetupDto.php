<?php
/* Type: Response DTO
 * Primary Source: GameInstance
 * Description: The initial setup for a game just starting.
 */
class GameInstanceSetupDto {
    // from source --------------------------
    public $gameSessionId;
    public $gameInstanceId;
    public $dealerPlayerId;
    public $firstPlayerId;
    public $userPlayerId;
    // additional data ----------------------
    public $blindBets;
    public $playerStatusDtos;
    public $userPlayerHandDto;
    
    public function __construct($entity){
        $this->gameSessionId = $entity->gameInstanceSetup->gameSessionId;
        $this->gameInstanceId = $entity->id;
        $this->dealerPlayerId = $entity->gameInstanceSetup->dealerPlayerId;
        $this->firstPlayerId = $entity->nextPlayerId;
    }
}

?>
