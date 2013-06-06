<?php

/* Type: Response DTO
 * Primary Source: GameInstance
 * Description:
 * All the information for a game, as sent when a user first joins a table
 * (a game may be actively played or not but same DTO is used or to all players
 * (waiting users also) after every move. 
 * The properties populated depend on the status of the game.
 * 
 * None       - Users on tables have not started playing yet. 
 * Started    - UserPlayerHandDto is sent
 * InProgress - After every poker move/skip.
 * Ended      - after final poker move/skip
 * 
 */

class GameStatusDto {

    public $gameInstanceId;
    public $gameSessionId;
    public $gameStatus;
    public $statusDateTime;
    public $dealerPlayerId;
    public $playerStatusDtos;
    public $updatePlayerStatusDto;
    public $nextMoveDto;
    public $communityCards;
    public $newCommunityCards;
    public $waitingListSize; // not really part of the game but needed if new user
    // user info 
    public $userPlayerHandDto; // if status=gameStarted or user comes back
    public $userSeatNumber; // for a user who just joins a table
    // if game ended:
    public $winningPlayerId; // if status=gameEnded
    public $playerHandsDto;  // if status=gameEnded

    public function SetUserHand($playerId) {
        $this->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $this->gameInstanceId);
    }

    public function SetUserSeat($seatNumber) {
        $this->userSeatNumber = $seatNumber;
    }

    /**
     * Populates a GameStatusDto for a newly joined user, parameters
     * with information already retrieved
     * @param type $requestingPlayer
     * @param type $players
     * @param type $casinoTable
     */
    public static function Init($requestingPlayer, $players, $casinoTable) {
        $gameStatusDto = new GameStatusDto();

        $gameStatusDto->gameSessionId = $casinoTable->currentGameSessionId;

        $userDto = new PlayerDto($requestingPlayer);
        $gameInstance = EntityHelper::getSessionLastInstance($casinoTable->currentGameSessionId);

        if ($gameInstance) {
            $gameStatusDto->gameInstanceId = $gameInstance->id;
            $gameStatusDto->gameStatus = $gameInstance->status;
            $gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime;
            $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
            $gameStatusDto->playerStatusDtos = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);
            $gameStatusDto->nextMoveDto = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstance->id);

            $gameStatusDto->communityCards = CardHelper::getCommunityCardDtos($gameInstance->id, $gameInstance->numberCommunityCardsShown);
            $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($requestingPlayer->id, $casinoTable);
            if ($gameInstance->status === GameStatus::ENDED) {
                $gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
                $gameStatusDto->playerHandsDto = PlayerHandDto::mapPlayerHands($gameInstance->playerHands);
            }
        } else {
            $gameStatusDto->playerStatusDtos = PlayerStatusDto::mapPlayers($players, GameStatus::NONE, true);
        }
        $gameStatusDto->userSeatNumber = $userDto->currentSeatNumber;

        $gameStatusDto->waitingListSize = $casinoTable->getWaitingListSize;
        return $gameStatusDto;
    }

    /**
     * 
     * @param type $entity
     * @param type $requestingPlayerId
     * @param type $blindBetDtos
     */
    public static function SetStartedGame($gameInstance) {
        $gameStatusDto = new GameStatusDto();
        $gameStatusDto->gameSessionId = $gameInstance->gameSessionId;
        $gameStatusDto->gameInstanceId = $gameInstance->id;
        $gameStatusDto->gameStatus = GameStatus::STARTED;
        $gameStatusDto->statusDateTime = Context::GetStatusDT();
        $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
        return $gameStatusDto;
    }

}

?>
