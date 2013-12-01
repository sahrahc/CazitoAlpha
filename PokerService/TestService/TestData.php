<?php

/**
 * Create and init a new GameStatusDto to default values
 * Check for hours are for ranges and for sequencing only.
 */
function InitGameStatusDto($gameSessionId) {
	global $dateTimeFormat;
	$dto = new GameStatusDto();

	$dto->gameSessionId = $gameSessionId;
	$dto->gameStatus = GameStatus::NONE;
	$dto->statusDateTime = date($dateTimeFormat);
	$dto->waitingListSize = 0;
	$dto->currentPotSize = 0;
	return $dto;
}

function nextPlayerNumber($current, $offset) {
	global $playerIds;
	return ($current + $offset) % count($playerIds);
}

function InitGameStart($firstNumber, $numberActivePlayers) {
	global $expectedDto;
	global $playerIds;
	global $blind1Size;
	global $blind2Size;
	global $gameInstanceId;
	global $lastBet;
	global $tableSize;
	global $activePlayers;
	global $startingPlayers;

	$activePlayers = $numberActivePlayers;
	$startingPlayers = $numberActivePlayers;
// 0 is dealer, 1 and 2 are the blinds, 3 is the first play
	$expectedDto->gameStatus = GameStatus::STARTED;
	$expectedDto->dealerPlayerId = $playerIds[$firstNumber];
	UpdateExpPlayerStatusDto(nextPlayerNumber($firstNumber, 1), PlayerStatusType::BLIND_BET, $blind1Size, 0);
	UpdateExpPlayerStatusDto(nextPlayerNumber($firstNumber, 2), PlayerStatusType::BLIND_BET, $blind2Size, 0);
	for ($i = 3; $i <= $numberActivePlayers; $i++) {
		UpdateExpPlayerStatusDto(nextPlayerNumber($firstNumber, $i), PlayerStatusType::WAITING, 0, 0);
	}
	$nextNumber = nextPlayerNumber($firstNumber, 3);
	$expectedDto->firstPlayerId = $playerIds[$nextNumber];
	$expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[$nextNumber], $blind2Size, 0, $blind2Size * 2);
	$expectedDto->casinoTableId = null;
	$expectedDto->userSeatNumber = null;
	$expectedDto->currentPotSize = $blind1Size + $blind2Size;

	$lastBet = $tableSize;
}

/**
 * Create and init a new PlayerStatusDto with the given parameter values.
 * @param type $playerId
 * @param type $playerName
 * @param type $seatNumber
 * @param type $stake
 * @return type
 */
function InitPlayerStatusDto($playerId, $playerName, $seatNumber, $stake) {
	$dto = new PlayerStatusDto();
	$dto->playerId = $playerId;
	$dto->playerName = $playerName;
	$dto->seatNumber = $seatNumber;
	$dto->status = PlayerStatusType::WAITING;
	$dto->currentStake = $stake;
// if game not started, play amounts are null
//$playerStatus->lastPlayAmount = 0;
//$playerStatus->lastPlayInstanceNumber = 0;
	return $dto;
}

/*
 * Updated the value of a single player status on the expected DTO.
 */

function UpdateExpPlayerStatusDto($playerNumber, $status, $amount, $playNumber) {
	global $expectedDto;
	$expectedDto->playerStatusDtos[$playerNumber]->status = $status;
	$expectedDto->playerStatusDtos[$playerNumber]->currentStake -= $amount;
	$expectedDto->playerStatusDtos[$playerNumber]->lastPlayAmount = $amount;
	$expectedDto->playerStatusDtos[$playerNumber]->lastPlayInstanceNumber = $playNumber;
}

function UpdateExpTurnPlayerStatusDto($playerNumber, $status, $amount, $stake, $playNumber) {
	global $expectedDto;
	global $playerIds;

	$expectedDto->turnPlayerStatusDto = new PlayerStatusDto();
	$expectedDto->turnPlayerStatusDto->playerId = $playerIds[$playerNumber];
	$expectedDto->turnPlayerStatusDto->status = $status;
	$expectedDto->turnPlayerStatusDto->currentStake = $stake - $amount;
/// null amount if folded
	if (is_null($amount) && $status != PlayerStatusType::CHECKED) {
		$expectedDto->turnPlayerStatusDto->lastPlayAmount = $expectedDto->playerStatusDtos[$playerNumber]->lastPlayAmount;
	} else {
		$expectedDto->turnPlayerStatusDto->lastPlayAmount = $amount;
		$expectedDto->playerStatusDtos[$playerNumber]->lastPlayAmount = $amount;
	}
	$expectedDto->turnPlayerStatusDto->lastPlayInstanceNumber = $playNumber;
	$expectedDto->turnPlayerStatusDto->seatNumber = $expectedDto->playerStatusDtos[$playerNumber]->seatNumber;
// new player stake and status
	$expectedDto->playerStatusDtos[$playerNumber]->lastPlayInstanceNumber = $playNumber;
	$expectedDto->playerStatusDtos[$playerNumber]->status = $status;
	$expectedDto->playerStatusDtos[$playerNumber]->currentStake = $stake - $amount;
}

function UpdateExpPlayerStatusDtoFinal($actualDto, $updatedFlag = null) {
	global $playerIds;
	global $numberPlayers; // all players, not just active players
	global $expectedDto;

	if (!is_null($updatedFlag) && $updatedFlag) {
		return $updatedFlag;
	}
	if (!is_null($updatedFlag) && !$updatedFlag) {
		$updatedFlag = true;
	}

	$winnerNumber = array_search($actualDto->winningPlayerId, $playerIds);
	for ($i = 0; $i < $numberPlayers; $i++) {
		if (!isset($expectedDto->playerStatusDtos[$i])) {
			continue;
		}
		if ($expectedDto->playerStatusDtos[$i]->status == PlayerStatusType::WAITING) {
			continue;
		}
		if ($expectedDto->playerStatusDtos[$i]->status != PlayerStatusType::LEFT) {
			$expectedDto->playerStatusDtos[$i]->status = PlayerStatusType::LOST;
		}
		if ($i == $winnerNumber) {
			$expectedDto->playerStatusDtos[$i]->status = PlayerStatusType::WON;
			$expectedDto->playerStatusDtos[$i]->currentStake += $expectedDto->currentPotSize;
			// no player status dtos
			//$expectedDto->playerStatusDtos[$i]->status = $playerStatusDtos[$i]->status;
			//$expectedDto->playerStatusDtos[$i]->currentStake = $playerStatusDtos[$i]->currentStake;
		}
		$expectedDto->playerStatusDtos[$i] = $expectedDto->playerStatusDtos[$i];
	}
	return $updatedFlag;
}

/* * *************************************************************** */
/* Set test data */

/**
 * At beginning of game, a player's cards are sent back
 *
 * Create and initialize a new player hand dto.
 * @param type $playerId
 * @param type $code1
 * @param type $code2
 * @return type
 */
function InitPlayerHandDtoStart($playerId, $code1, $code2) {
	$playerHand = new PlayerHandDto($playerId, $code1, $code2);
	return $playerHand;
}

/**
 * Create and init a new ExpectedPokerMove 
 * @global type $playExpiration
 * @param type $instId
 * @param type $playerId
 * @param type $call
 * @param type $isCheckOk
 * @param type $raise
 * @return type
 */
function InitMove($instId, $playerId, $call, $isCheckOk, $raise) {
	global $playExpiration;
	$expDT = new DateTime();
	$expDT->add(new DateInterval($playExpiration)); // 20 seconds        

	$move = new ExpectedPokerMove();
	$move->gameInstanceId = $instId;
	$move->playerId = $playerId;
	$move->expirationDate = $expDT;
	$move->callAmount = $call;
	$move->isCheckAllowed = $isCheckOk;
	$move->raiseAmount = $raise;
	return $move;
}

/**
 * At end of game, player cards have type
 */
function InitPlayerHandDtoEnd($playerHand, $handType) {
	$updatedHand = new PlayerHandDto($playerHand->playerId, $playerHand->pokerCard1Code, $playerHand->pokerCard2Code);
	$updatedHand->pokerHandType = $handType;
	return $updatedHand;
}

function UpdateTurnsNextGame() {
	global $playerIds;
	global $expectedDto;
	global $q;

	array_push($playerIds, $playerIds[0]);
	array_shift($playerIds);
	array_push($expectedDto->playerStatusDtos, $expectedDto->playerStatusDtos[0]);
	array_shift($expectedDto->playerStatusDtos);
	array_push($q, $q[0]);
	array_shift($q);
}

/**
 * 
 * @global type $previousGameCards
 * @global type $playerHands
 * @global array $communityCards
 * @global type $playerIds
 * @param type $indexCards
 * @param type $previousNumber - number of players
 */
function InitPlayerHands($indexCards, $isFirstGame = false)  {
	global $previousGameCards;
	global $playerHands;
	global $communityCards;
	global $playerIds;

	if ($isFirstGame) {
//		$previousGameCards = null;
		$previousGameCards = array();
	} else {
		$playerPreviousCards = array();
		for ($i = 0; $i < count($playerHands); $i++) {
				$playerPreviousCards = array_merge($playerPreviousCards, $playerHands[$i]);
		}
//		if ($previousGameCards == null) {
//			$currentGameCards = $playerPreviousCards;
//		} else {
			$currentGameCards = array_merge($previousGameCards, $playerPreviousCards);
//		}
		$previousGameCards = array_values(array_merge($currentGameCards, $communityCards));
	}
	// player hands for new game; need to be calculated at end of game because hands may have changed
	$playerHands = null;
	for ($i = 0; $i < count($playerIds); $i++) {
		$playerHands[$i] = array($indexCards[$i * 2], $indexCards[$i * 2 + 1]);
	}
	$communityCards = array(
		$indexCards[$i * 2],
		$indexCards[$i * 2 + 1],
		$indexCards[$i * 2 + 2],
		$indexCards[$i * 2 + 3],
		$indexCards[$i * 2 + 4]);
}

function RemovePlayer($playerNumber) {
	global $playerIds;
	global $playerNames;
	global $expectedDto;
	global $q;
	global $numberPlayers;
	global $activePlayers;

	connectToStateDB();

	$leavingPlayerId = $playerIds[$playerNumber];
// update $expectedDto and playerStatusDtos to remove the user who left
	array_splice($playerIds, $playerNumber, 1);
	//array_splice($playerNames, $playerNumber, 1);
	array_splice($q, $playerNumber, 1);
	if (!is_null($expectedDto->playerStatusDtos)) {
		array_splice($expectedDto->playerStatusDtos, $playerNumber, 1);
	}
	cleanUpPlayerById($leavingPlayerId);
	$numberPlayers--;
	$activePlayers--;
}

function InsertInactivePlayer($playerId) {
	global $playerIds;
	global $q;
	global $numberPlayers;
	//global $activePlayers;
	global $qCh;

	connectToStateDB();

	$playerNumber = count($playerIds);
	array_push($playerIds, $playerId);
	$qNew = QueueManager::GetPlayerQueue($playerId, $qCh);
	array_push($q, 'x');
	$q[$playerNumber] = $qNew;
	// player status dtos not added here but by UpdateTurnsNextGame
	$numberPlayers++;
	//$activePlayers++;
}

function MakePlayerActive($oldPlayerNumber, $playerId, $seatNumber, $playerName, $buyIn, $position) {
	global $expectedDto;
	global $playerIds;
	global $q;

	/* find where the player to be made active is currently positioned first 
	  while ($currentPlayerId = current($playerIds)) {
	  if ($currentPlayerId == $playerId) {
	  $oldPlayerNumber = key($playerIds);
	  }
	  next($playerIds);
	  }
	  if (!isset($oldPlayerNumber)) {
	  echo "*** CANNOT FIND PLAYER ID $playerId TO MAKE ACTIVE <br />";
	  exit;
	  }
	 * 
	 */
	/* remove first before putting back in right place */
	$qNew = $q[$oldPlayerNumber];
	array_splice($playerIds, $oldPlayerNumber, 1);
	array_splice($q, $oldPlayerNumber, 1);

	/* put in right place */
	array_splice($playerIds, $position, 0, $playerId);
	array_splice($expectedDto->playerStatusDtos, $position, 0, 'x');
	// last position
	/* $x = count($expectedDto->playerStatusDtos);
	  while ($x > $position) {
	  $expectedDto->playerStatusDtos[$x] =  $expectedDto->playerStatusDtos[$x-1];
	  $x--;
	  } */
	$expectedDto->playerStatusDtos[$position] = InitPlayerStatusDto(
			$playerId, $playerName, $seatNumber, $buyIn);
	array_splice($q, $position, 0, 'x');
	$q[$position] = $qNew;
	// active players reset somewhere else
}

?>
