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
    public $casinoTableId;
    public $gameSessionId;
    public $gameStatus;
    public $statusDateTime;
    public $dealerPlayerId;
    public $currentPotSize;
    public $playerStatusDtos;
    public $turnPlayerStatusDto;
    public $nextMoveDto;
    public $communityCards;
    public $newCommunityCards;
    public $waitingListSize; // not really part of the game but needed if new user
    // user info
    public $userPlayerId;
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
    public static function InitForTable($requestingPlayer, $players, $casinoTable, $addNames=false) {
        global $dateTimeFormat;
        $gameStatusDto = new GameStatusDto();

        $gameStatusDto->gameSessionId = $casinoTable->currentGameSessionId;
        $gameStatusDto->casinoTableId = $casinoTable->id;
        $userDto = new PlayerDto($requestingPlayer);
        $gameInstance = EntityHelper::getSessionLastInstance($casinoTable->currentGameSessionId);
		if ($gameInstance) {
        $gameCards = new GameInstanceCards($gameInstance->id, $gameInstance->numberCommunityCardsShown);
            $gameStatusDto->gameInstanceId = $gameInstance->id;
            $gameStatusDto->gameStatus = $gameInstance->status;
            $gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime->format($dateTimeFormat);
            $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
			// doesn't get players seated but not in game
            $gameStatusDto->playerStatusDtos = PlayerInstance::GetPlayersWithStates($gameInstance->id, $casinoTable->id);
			$gameStatusDto->nextMoveDto = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstance->id);
            $gameStatusDto->currentPotSize = $gameInstance->currentPotSize;
            
            $gameStatusDto->communityCards = $gameCards->GetSavedCommunityCardDtos();
            $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($requestingPlayer->id, $gameInstance->id);
            if ($gameInstance->status === GameStatus::ENDED) {
                $gameStatusDto->winningPlayerId = $gameInstance->winningPlayerId;
				$gameCards = new GameInstanceCards($gameInstance->id);
				$gameCards->GetSavedCards();
                $gameStatusDto->playerHandsDto = PlayerHandDto::mapPlayerHands($gameCards->playerHands);
            }
        } else {
            $gameStatusDto->gameStatus = GameStatus::NONE;
            $gameStatusDto->statusDateTime = Context::GetStatusDTString();
            $gameStatusDto->playerStatusDtos = PlayerStatusDto::mapPlayers($players, PlayerStatusType::WAITING, true);
        }
		$gameStatusDto->userPlayerId = $userDto->playerId;
        $gameStatusDto->userSeatNumber = $userDto->currentSeatNumber;

        $gameStatusDto->waitingListSize = $casinoTable->getWaitingListSize();
        return $gameStatusDto;
    }
 
	public static function InitResetSession($players, $casinoTable) {
        global $dateTimeFormat;
        $gameStatusDto = new GameStatusDto();

        $gameStatusDto->gameSessionId = $casinoTable->currentGameSessionId;
        $gameStatusDto->casinoTableId = $casinoTable->id;
            $gameStatusDto->gameStatus = GameStatus::NONE;
            $gameStatusDto->statusDateTime = Context::GetStatusDTString();
            $gameStatusDto->playerStatusDtos = PlayerStatusDto::mapPlayers($players, PlayerStatusType::WAITING, true);
        $gameStatusDto->waitingListSize = $casinoTable->getWaitingListSize();
        return $gameStatusDto;
		
	}
    public static function InitForInstance($gameInstance) {
        global $dateTimeFormat;
        $gameStatusDto = new GameStatusDto();
		$gameStatusDto->gameSessionId = $gameInstance->gameSessionId;
		$gameStatusDto->gameInstanceId = $gameInstance->id;
		$gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
		$gameStatusDto->statusDateTime = $gameInstance->lastUpdateDateTime->format($dateTimeFormat);

        return $gameStatusDto;
    }

    /**
     * 
     * @param type $entity
     * @param type $requestingPlayerId
     */
    public static function SetStartedGame($gameInstance) {
        $gameStatusDto = new GameStatusDto();
        $gameStatusDto->gameSessionId = $gameInstance->gameSessionId;
        $gameStatusDto->gameInstanceId = $gameInstance->id;
        $gameStatusDto->gameStatus = GameStatus::STARTED;
        $gameStatusDto->statusDateTime = Context::GetStatusDTString();
        $gameStatusDto->dealerPlayerId = $gameInstance->dealerPlayerId;
        $gameStatusDto->currentPotSize = $gameInstance->currentPotSize;
        return $gameStatusDto;
    }

}

?>
