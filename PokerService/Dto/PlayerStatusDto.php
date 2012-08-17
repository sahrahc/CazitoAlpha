<?php

/* Type: Response DTO (not full response).
 * Source: PlayerInstanceStatus
 * Description: The status of a player at an instance, excluding blind bets, cards and other information
 * which is sent separately. Noticed that turn number is not exposed.
 */

class PlayerStatusDto {

    public $playerId;
    public $playerName;
    public $playerImageUrl;
    public $seatNumber;
    public $status;
    public $blindBet;
    public $stake;
    public $playAmount;
    public $playerPlayNumber;

    function __construct($entity) {
        if (!is_null($entity)) {
            $this->playerId = $entity->playerId;
            $this->playerName = $entity->playerInstanceSetup->playerName;
            $this->playerImageUrl = $entity->playerInstanceSetup->playerImageUrl;
            $this->seatNumber = $entity->playerInstanceSetup->seatNumber;
            $this->status = $entity->status;
            $this->blindBet = $entity->playerInstanceSetup->blindBet;
            $this->stake = $entity->stake;
            $this->playAmount = $entity->lastPlayAmount;
            $this->playerPlayNumber = $entity->playerPlayNumber;
        }
    }

    public static function mapPlayerDtos($playerDtos, $status) {
        $obj = null;
        for ($i = 0, $l = count($playerDtos); $i < $l; $i++) {
            $obj[$i] = new PlayerStatusDto(null);
            $obj[$i]->playerId = $playerDtos[$i]->playerId;
            $obj[$i]->playerName = $playerDtos[$i]->playerName;
            $obj[$i]->playerImageUrl = $playerDtos[$i]->playerImageUrl;
            $obj[$i]->seatNumber = $playerDtos[$i]->currentSeatNumber;
            $obj[$i]->stake = $playerDtos[$i]->buyin;
            $obj[$i]->status = $status;
            $obj[$i]->playAmount = null;
            $obj[$i]->playerPlayNumber = 0;
        }
        return $obj;
    }
}
?>
