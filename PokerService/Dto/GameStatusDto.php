<?php
/* Type: Response DTO
 * Primary Source: GameInstanceStatus
 * Description: The complete status of a game in progress.
 */
class GameStatusDto {
    // from source --------------------------
    public $casinoTableId;
    public $gameSessionId;
    public $gameInstanceId;
    public $gameStatus;
    public $statusDateTime; // all the events change the game instance.
    public $dealerPlayerId;
    // FIXME: need t send next move 
    public $nextPlayerId;
    public $userPlayerId; // because request had the name only, not the id
    public $userSeatNumber; // for a user who just joins a table
    public $userPlayerHand; // if a user is returning after closing browser
    public $communityCards;
    public $playerStatusDtos;
    public $gameResultDto;
    public $nextMoveDto; // FIXME: actully next move, should rename
    public $waitingListSize;

    public function updateInstanceData($entity){
        $this->gameInstanceId = $entity->id;
        $this->statusDateTime = $entity->lastUpdateDateTime;
        $this->nextPlayerId = $entity->nextPlayerId;
        $this->gameSessionId = $entity->gameInstanceSetup->gameSessionId;
        $this->dealerPlayerId = $entity->gameInstanceSetup->dealerPlayerId;
    }
}
?>
