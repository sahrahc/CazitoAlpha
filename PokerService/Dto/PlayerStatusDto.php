<?php

/* Type: Response DTO (not full response).
 * Source: PlayerInstanceStatus
 * Description: The status of a player at an instance, excluding blind bets, cards and other information
 * which is sent separately. Noticed that turn number is not exposed.
 */

class PlayerStatusDto {

    public $playerId;
    public $playerName; // only if returning player status for user first joined
    public $playerImageUrl; // only if returning player status for user first joined
    public $seatNumber;
    public $status;
    public $currentStake;
    public $lastPlayAmount;
    public $lastPlayInstanceNumber;

    public static function mapPlayerStatus($entity, $addNames=false) {
        $playerStatusDto = new PlayerStatusDto();
        if (!is_null($entity)) {
            $playerStatusDto->playerId = $entity->playerId;
            if ($addNames) {
              $player = Player::GetPlayer($entity->playerId);
              $playerStatusDto->playerName = $player->name;
              $playerStatusDto->playerImageUrl = $player->imageUrl;
            }
            $playerStatusDto->seatNumber = $entity->seatNumber;
            $playerStatusDto->status = $entity->status;
            $playerStatusDto->currentStake = $entity->currentStake;
            $playerStatusDto->lastPlayAmount = $entity->lastPlayAmount;
            $playerStatusDto->lastPlayInstanceNumber = $entity->lastPlayInstanceNumber;
        }
        return $playerStatusDto;
    }

    public static function MapPlayerStatuses($playerStatuses, $addNames=false) {
        $obj = null;
        for ($i = 0, $l = count($playerStatuses); $i < $l; $i++) {
            $obj[$i] = self::mapPlayerStatus($playerStatuses[$i], $addNames);
        }
        return $obj;
    }

    /**
     * Maps a list of Players to a list of PlayerStatusDto. Having problems
     * with array_map.
     * @param type $players
     * @param type $status
     * @return \PlayerStatusDto
     */
    public static function mapPlayers($players, $status, $addName=false) {
        $obj = null;
        for ($i = 0, $l = count($players); $i < $l; $i++) {
            $obj[$i] = new PlayerStatusDto();
            $obj[$i]->playerId = $players[$i]->id;
            if ($addName) {
            $obj[$i]->playerName = $players[$i]->name;
            $obj[$i]->playerImageUrl = $players[$i]->imageUrl;
            }
            $obj[$i]->seatNumber = $players[$i]->currentSeatNumber;
            $obj[$i]->currentStake = $players[$i]->buyIn;
            $obj[$i]->status = $status;
            $obj[$i]->lastPlayAmount = null;
            $obj[$i]->lastPlayInstanceNumber = null;
        }
        return $obj;
    }

}

?>
