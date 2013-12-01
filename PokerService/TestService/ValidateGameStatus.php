<?php

/**
 * TODO: Replace this function with the next one
 * @param type $playerId
 * @param type $queue
 * @param type $eventType
 * @return type
 */
function verifyQMessage($playerId, $queue, $eventType) {
	global $printAPI;

	$message = $queue->get(AMQP_AUTOACK);
	if (!$message) {
		echo "***FAILED: no expected message $eventType received by player Id $playerId <br />";
		return;
	}
	// to get the queue name and message date
	//$message->getRoutineKey();
	//$message->getTimeStamp();
	$messageBody = $message->getBody();
	if (isset($printAPI) && $printAPI) {
		echo "Info: Message for player id $playerId: $messageBody <br /> <br />";
	}
	$messageObject = json_decode($messageBody);
	if (!is_null($eventType) && $messageObject->eventType != $eventType) {
		echo "Warning: Player $playerId received message $messageObject->eventType instead of $eventType";
	}
	return $messageObject->eventData;
}

function verifyQCheatingMessage($playerId, $queue, $itemType, $eventTypes, $isDisabled) {
	global $printCheatingAPI;

	$countFailed = 0;
	$message = $queue->get(AMQP_AUTOACK);
	if (!$message) {
		echo "***FAILED: no expected message " . json_encode($eventTypes) . " received by $playerId <br />";
		return array('countFailed' => $countFailed, 'eventData' => null);
	}   // to get the queue name and message date
	//$message->getRoutineKey();
	//$message->getTimeStamp();
	$i = 0;
	$j = 0;
	$messageBody = $message->getBody();
	if ($printCheatingAPI) {
		echo "Info: Message for player id $playerId: $messageBody <br /> <br />";
	}
	$messages = json_decode($messageBody)->eventData;
	if (count($messages) != count($eventTypes)) {
		echo "***FAILED: Number of expected messages is " . count($eventTypes) . " but " . count($messages) . ' received<br/>';
		$countFailed++;
	}
	$eventData = null;
	foreach ($messages as $messageObject) {
		if (!is_null($eventTypes[$i]) && $messageObject->dtoType != $eventTypes[$i]) {
			$countFailed++;
			echo "***FAILED: Player $playerId received message $messageObject->dtoType instead of $eventTypes[$i] <br/>";
		}
		if ($eventTypes[$i] == CheatDtoType::ItemLog) {
			$countFailed += NotEquals('CheatingMessage - ItemType', $messageObject->itemType, $itemType);
			$countFailed += NotEquals('CheatingMessage - IsDisabled', $messageObject->dto->isDisabled, $isDisabled);
		}
		$i++;
		if ($messageObject->dtoType == CheatDtoType::CheatedCards ||
				$messageObject->dtoType == CheatDtoType::CheatedHands ||
				$messageObject->dtoType == CheatDtoType::CheatedHidden ||
				$messageObject->dtoType == CheatDtoType::CheatedNext) {
			$eventData[$j++] = $messageObject->dto;
		}
	}
	return array('countFailed' => $countFailed, 'eventData' => $eventData);
}

// returns 1 if failed, 0 if passed
// does this work if both are null? Verify it should
function NotEquals($type, $current, $expected) {
	if ($type === "StatusDateTime" || $current instanceof DateTime) {
		$interval = date_diff($current, $expected);
		if ($interval->format('%s') > 60) {
			return 1;
		} else {
			return 0;
		}
	}
// TODO: within 1 minute and expected should be earlier
	if ($current != $expected) {
		$currentString = json_encode($current);
		$expectedString = json_encode($expected);
		echo "*** FAILED $type - " . $currentString . " is not " . $expectedString . "<br />";
		return 1;
	}
	return 0;
}

function NotGreater($type, $earlier, $later) {
	$earlierJson = json_encode($earlier);
	$laterJson = json_encode($later);
	if ($earlier > $later) {
		echo "*** FAILED $type - $earlierJson is greater/later than $laterJson <br />";
		return 1;
	}
	return 0;
}

function IsNotNull($type, $value) {
	if (!is_null($value)) {
		$valueString = json_encode($value);
		echo "*** FAILED $type - null expected found $valueString <br />";
		return 1;
	}
	return 0;
}

function IsNull($type, $value) {
	if (is_null($value)) {
		echo "*** FAILED $type - $value expected null  <br />";
		return 1;
	}
	return 0;
}

function ValidateGameStatusDtoNoPlay($dto, $expected) {
	global $dateTimeFormat;
	$status = 'No Play Started Yet';
	echo "Validating Game Status $status<br />";

	$countFailed = IsNotNull('Game Instance Id', $dto->gameInstanceId);
	$countFailed += NotEquals('Game Session', $dto->gameSessionId, $expected->gameSessionId);
	$countFailed += NotEquals('Casino Table Id', $dto->casinoTableId, $expected->casinoTableId);
	$countFailed += NotEquals('GameStatus', $dto->gameStatus, GameStatus::NONE);
	$dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
	$expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
	$countFailed += NotEquals('StatusDateTime', $dtoDateTime, $expDateTime);
	$countFailed += NotEquals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId);
	if (NotEquals('# Player Statuses', count($dto->playerStatusDtos), count($expected->playerStatusDtos))) {
		$countFailed++;
	} else {
		$countFailed += ValidatePlayerStatusDtos($dto->playerStatusDtos, $expected->playerStatusDtos, PlayerStatusType::WAITING);
	}
	$countFailed += NotEquals('Pot Size', $dto->currentPotSize, $expected->currentPotSize);
	$countFailed += IsNotNull('Updated Player', $dto->turnPlayerStatusDto);
	$countFailed += IsNotNull('Next Move', $dto->nextMoveDto);
	$countFailed += IsNotNull('Community Cards', $dto->communityCards);
	$countFailed += IsNotNull('New Cards', $dto->newCommunityCards);
	$countFailed += NotEquals('Waiting List', $dto->waitingListSize, 0);
	$countFailed += IsNotNull('User Hand', $dto->userPlayerHandDto);
	$countFailed += NotEquals('User Seat', $dto->userSeatNumber, $expected->userSeatNumber);
	$countFailed += IsNotNull('Winner Id', $dto->winningPlayerId);
	$countFailed += IsNotNull('All Hands', $dto->playerHandsDto);
	if ($countFailed === 0) {
		echo "*** PASSED ALL TESTS <br />";
	}
}

function ValidateGameStatusDtoStart($dto, $expected) {
	global $dateTimeFormat;
	$status = 'Game Started';
	echo "Validating Game Status $status<br />";

	$countFailed = NotEquals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId);
	$countFailed += NotEquals('Game Session', $dto->gameSessionId, $expected->gameSessionId);
	$countFailed += IsNotNull('Casino Table Id', $dto->casinoTableId);
	$countFailed += NotEquals('GameStatus Started', $dto->gameStatus, GameStatus::STARTED);
	$countFailed += NotEquals('GameStatus Expected', $dto->gameStatus, $expected->gameStatus);
	$dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
	$expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
	$countFailed += NotEquals('StatusDateTime', $dtoDateTime, $expDateTime);
	$countFailed += NotEquals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId);
	if (NotEquals('# Player Statuses', count($dto->playerStatusDtos), count($expected->playerStatusDtos))) {
		$countFailed++;
	} else {
		$countFailed += ValidatePlayerStatusDtos($dto->playerStatusDtos, $expected->playerStatusDtos);
	}
	$countFailed += NotEquals('Pot Size', $dto->currentPotSize, $expected->currentPotSize);
	$countFailed += IsNotNull('Updated Player', $dto->turnPlayerStatusDto);
	if (IsNull('Next Move', $dto->nextMoveDto)) {
		$countFailed++;
	} else {
		$actual = $dto->nextMoveDto;
		$exp = $dto->nextMoveDto;
		$countFailed += NotEquals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId);
		$countFailed += NotEquals("Next Game Player Id", $actual->playerId, $exp->playerId);
		$countFailed += NotEquals("Expiration Date", $actual->expirationDate, $exp->expirationDate);
		$countFailed += NotEquals("Call Amount", $actual->callAmount, $exp->callAmount);
		$countFailed += NotEquals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed);
		$countFailed += NotEquals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount);
	}
	$countFailed += IsNotNull('New Cards', $dto->newCommunityCards);
	$countFailed += NotEquals('Waiting List', $dto->waitingListSize, 0);
	if (IsNull('User Hand', $dto->userPlayerHandDto)) {
		$countFailed++;
	} else {
		$actual = $dto->userPlayerHandDto;
		$exp = $dto->userPlayerHandDto;
		$countFailed += NotEquals('User Player Id', $actual->playerId, $exp->playerId);
		$countFailed += NotEquals('User Hand Card 1', $actual->pokerCard1Code, $exp->pokerCard1Code);
		$countFailed += NotEquals('User Hand Card 2', $actual->pokerCard2Code, $exp->pokerCard2Code);
		$countFailed += IsNotNull('Hand Type', $actual->pokerHandType);
	}
	$countFailed += NotEquals('User Seat', $dto->userSeatNumber, $expected->userSeatNumber);
	$countFailed += IsNotNull('Winner Id', $dto->winningPlayerId);
	$countFailed += IsNotNull('All Hands', $dto->playerHandsDto);
	if ($countFailed === 0) {
		echo "*** PASSED - Validated Game Status $status<br />";
	}
}

function ValidateGameStatusDtoAfterMove($dto, $expected) {
	global $dateTimeFormat;

	$status = 'after a move (no round or game end)';
	echo "Validating Game Status $status<br />";
	$countFailed = NotEquals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId);
	$countFailed += NotEquals('Game Session', $dto->gameSessionId, $expected->gameSessionId);
	$countFailed += IsNotNull('Casino Table Id', $dto->casinoTableId);
	$countFailed += NotEquals('GameStatus', $dto->gameStatus, $expected->gameStatus);
	$dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
	$expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
	$countFailed += NotEquals('StatusDateTime', $dtoDateTime, $expDateTime);
	$countFailed += NotEquals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId);
	$countFailed += IsNotNull('Player Statuses', $dto->playerStatusDtos);
	$countFailed += NotEquals('Pot Size', $dto->currentPotSize, $expected->currentPotSize);
	if (IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
		$countFailed++;
	} else {
		ValidateTurnPlayerStatusDto($dto->turnPlayerStatusDto, $expected->turnPlayerStatusDto);
	}
	if (IsNull('Next Move', $dto->nextMoveDto)) {
		$countFailed++;
	} else {
		$actual = $dto->nextMoveDto;
		$exp = $dto->nextMoveDto;
		$countFailed += NotEquals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId);
		$countFailed += NotEquals("Next Game Player Id", $actual->playerId, $exp->playerId);
		$countFailed += NotEquals("Expiration Date", $actual->expirationDate, $exp->expirationDate);
		$countFailed += NotEquals("Call Amount", $actual->callAmount, $exp->callAmount);
		$countFailed += NotEquals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed);
		$countFailed += NotEquals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount);
	}
	$countFailed += IsNotNull('Community Cards', $dto->communityCards);
	$countFailed += IsNotNull('New Cards', $dto->newCommunityCards);
	$countFailed += IsNotNull('Waiting List', $dto->waitingListSize);
	$countFailed += IsNotNull('User Hand', $dto->userPlayerHandDto);
	$countFailed += IsNotNull('User Seat', $dto->userSeatNumber);
	$countFailed += IsNotNull('Winner Id', $dto->winningPlayerId);
	$countFailed += IsNotNull('All Hands', $dto->playerHandsDto);
	if ($countFailed === 0) {
		echo "*** PASSED - Validated Game Status Should be $status<br />";
	}
}

function ValidateGameStatusDtoRoundEnd($dto, $expected, $isNewlyJoined = false) {
	global $dateTimeFormat;
	$status = 'at Round End';
	echo "Validating Game Status $status<br />";
	$countFailed = NotEquals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId);
	$countFailed += NotEquals('Game Session', $dto->gameSessionId, $expected->gameSessionId);

	$countFailed += NotEquals('GameStatus Expected', $dto->gameStatus, $expected->gameStatus);
	$dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
	$expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
	$countFailed += NotEquals('StatusDateTime', $dtoDateTime, $expDateTime);
	$countFailed += NotEquals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId);
	$countFailed += NotEquals('Pot Size', $dto->currentPotSize, $expected->currentPotSize);
	if (IsNull('Next Move', $dto->nextMoveDto)) {
		$countFailed++;
	} else {
		$actual = $dto->nextMoveDto;
		$exp = $dto->nextMoveDto;
		$countFailed += NotEquals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId);
		$countFailed += NotEquals("Next Game Player Id", $actual->playerId, $exp->playerId);
		$countFailed += NotEquals("Expiration Date", $actual->expirationDate, $exp->expirationDate);
		$countFailed += NotEquals("Call Amount", $actual->callAmount, $exp->callAmount);
		$countFailed += NotEquals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed);
		$countFailed += NotEquals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount);
	}

// if newly joined return all
	if ($isNewlyJoined) {
		$countFailed += IsNull('Casino Table Id', $dto->casinoTableId);
		$countFailed += ValidatePlayerStatusDtos($dto->playerStatusDtos, $expected->playerStatusDtos, null, true);
		$countFailed += IsNotNull('Updated Player', $dto->turnPlayerStatusDto);
		$countFailed += IsNull('Community Cards', $dto->communityCards);
		$countFailed += IsNotNull('New Community Cards', $dto->newCommunityCards);
		$countFailed += IsNull('Waiting List', $dto->waitingListSize);
	} else {
		$countFailed += IsNotNull('Casino Table Id', $dto->casinoTableId);
		$countFailed += IsNotNull('Player Statuses', $dto->playerStatusDtos);
		if (IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
			$countFailed++;
		} else {
			ValidateTurnPlayerStatusDto($dto->turnPlayerStatusDto, $expected->turnPlayerStatusDto);
		}
		$countFailed += IsNotNull('Community Cards', $dto->communityCards);
		$countFailed += NotEquals('New Community Cards', count($dto->newCommunityCards), count($expected->newCommunityCards));
		if (NotGreater('New Community Cards', 0, count($dto->newCommunityCards))) {
			$countFailed++;
		} else {
			if (count($dto->newCommunityCards)) {
				$i = 0;
				foreach ($dto->newCommunityCards as $actual) {
					$exp = $expected->newCommunityCards[$i];
					$countFailed += NotEquals('New Community Card', $actual, $exp);
					$i++;
				}
			}
			$countFailed += IsNotNull('User Hand', $dto->userPlayerHandDto);
			$countFailed += IsNotNull('User Seat', $dto->userSeatNumber);
		}
		$countFailed += IsNotNull('Waiting List', $dto->waitingListSize);
	}
	$countFailed += IsNotNull('Winner Id', $dto->winningPlayerId);
	$countFailed += IsNotNull('All Hands', $dto->playerHandsDto);
	if ($countFailed === 0) {
		echo "*** PASSED - Validated Game Status Should be $status<br />";
	}
}

function ValidateGameStatusDtoGameEnd($dto, $expected) {
	global $printAPI;
	global $dateTimeFormat;

	$status = 'Game END';
	echo "Validating Game Status $status<br />";
	$countFailed = NotEquals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId);
	$countFailed += NotEquals('Game Session', $dto->gameSessionId, $expected->gameSessionId);
	$countFailed += IsNotNull('Casino Table Id', $dto->casinoTableId);

	$countFailed += NotEquals('GameStatus Ended', $dto->gameStatus, GameStatus::ENDED);
	$countFailed += NotEquals('GameStatus Expected', $dto->gameStatus, $expected->gameStatus);
	$dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
	$expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
	$countFailed += NotEquals('StatusDateTime', $dtoDateTime, $expDateTime);
	$countFailed += NotEquals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId);
	// number of player status may be different because new user may have joined
	$activePlayers = array();
	foreach ($expected->playerStatusDtos as $exp) {
		if ($exp->status != PlayerStatusType::WAITING) {
			array_push($activePlayers, $exp);
		}
	}
	if (NotEquals('# Player Statuses', count($dto->playerStatusDtos), count($activePlayers))) {
		$countFailed++;
	} else {
		$countFailed += ValidatePlayerStatusDtos($dto->playerStatusDtos, $activePlayers, null, true);
	}
	$countFailed += NotEquals('Pot Size', $dto->currentPotSize, $expected->currentPotSize);
	if (IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
		$countFailed++;
	} else {
		$actual = $dto->turnPlayerStatusDto;
		$exp = $expected->turnPlayerStatusDto;
		$countFailed += NotEquals("Updated Player Id", $actual->playerId, $exp->playerId);
		$countFailed += IsNotNull("Updated Player Name", $actual->playerName);
		$countFailed += NotEquals("Updated Player Seat", $actual->seatNumber, $exp->seatNumber);
		$countFailed += NotEquals("Updated Player Status", $actual->status, $exp->status);
		$countFailed += NotEquals("Updated Player Stake", $actual->currentStake, $exp->currentStake);
		$countFailed += NotEquals("Updated Player Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount);
		$countFailed += NotEquals("Updated Player Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber);
	}
	$countFailed += IsNotNull('Next Move', $dto->nextMoveDto);
	$countFailed += IsNotNull('Community Cards', $dto->communityCards);
	$countFailed += IsNotNull('New Cards', $dto->newCommunityCards);
	$countFailed += IsNotNull('Waiting List', $dto->waitingListSize);
	$countFailed += IsNotNull('User Hand', $dto->userPlayerHandDto);
	$countFailed += IsNotNull('User Seat', $dto->userSeatNumber);
	$countFailed += IsNull('Winner Id', $dto->winningPlayerId);
	if (IsNull('All Hands', $dto->playerHandsDto)) {
		$countFailed++;
	} else if ($printAPI) {
		$i = 0;
		foreach ($dto->playerHandsDto as $actual) {
			echo "Player $i Id:  $actual->playerId <br />";
			echo "Player $i Card 1: $actual->pokerCard1Code <br />";
			echo "Player $i Card 2: $actual->pokerCard2Code <br />";
			echo "Player $i Hand: $actual->pokerHandType <br />";
			$i++;
		}
	}
	if ($countFailed === 0) {
		echo "*** PASSED - Validated Game Status Should be $status<br />";
	}
}

function ValidatePlayerStatusDtos($actualDtos, $expectedDtos, $status = null, $ignoreName = false) {
	$i = 0;
	foreach ($actualDtos as $actual) {
		$exp = $expectedDtos[$i];
		$countFailed = NotEquals("Player $i Id", $actual->playerId, $exp->playerId);
		if (!$ignoreName) {
			$countFailed += NotEquals("Player $i Name", $actual->playerName, $exp->playerName);
		}
		$countFailed += NotEquals("Player $i Seat", $actual->seatNumber, $exp->seatNumber);
		if ($status != null) {
			$countFailed += NotEquals("Player $i Status $status", $actual->status, $status);
		}
		$countFailed += NotEquals("Player $i Status", $actual->status, $exp->status);
		$countFailed += NotEquals("Player $i Stake", $actual->currentStake, $exp->currentStake);
		$countFailed += NotEquals("Player $i Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount);
		$countFailed += NotEquals("Player $i Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber);
		$i++;
	}
	return $countFailed;
}

function ValidateTurnPlayerStatusDto($actual, $exp) {
	$countFailed = NotEquals("Updated Player Id", $actual->playerId, $exp->playerId);
	$countFailed += IsNotNull("Updated Player Name", $actual->playerName);
	$countFailed += NotEquals("Updated Player Seat", $actual->seatNumber, $exp->seatNumber);
	$countFailed += NotEquals("Updated Player Status", $actual->status, $exp->status);
	$countFailed += NotEquals("Updated Player Stake", $actual->currentStake, $exp->currentStake);
	$countFailed += NotEquals("Updated Player Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount);
	$countFailed += NotEquals("Updated Player Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber);
	return $countFailed;
}

function validateGameCards($indexCards) {
	global $printCheatingAPI;
	global $gameInstanceId;
	global $activePlayers;
	global $playerIds;
	global $playerHands;
	global $communityCards;
	/* testing eval helper DECK 
	  $DECK = EvalHelper::init2x2deck();
	  echo "deck is: " . json_encode($DECK). '<br />';
	  for ($i=1; $i<=count($DECK); $i++) {
	  $actualCardCodes[$i] = EvalHelper::findCardCode($DECK[$i]);
	  }
	  echo "deck cards are: " . json_encode($actualCardCodes) . '<br/>';
	 */
	connectToStateDB();

	echo "<br/>Validating game cards<br/>";
	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "Cards before cheating <br>";
	}
	// compare with values returned from queue - this tests... 
	// verify player hands - from queue message
	for ($i = 0; $i < $activePlayers; $i++) {
		$playerHand = CardHelper::getPlayerHandDto($playerIds[$i], $gameInstanceId);
		if (isset($printCheatingAPI) && $printCheatingAPI) {
			echo " - Player $i id " . $playerIds[$i] . ': ' . json_encode($playerHand) . "<br />";
		}
		$loadedCards = array($playerHand->pokerCard1Code, $playerHand->pokerCard2Code);
		validateCardValues($playerIds[$i], $loadedCards, $playerHands[$i]);
	}
	// verify community Cards - from queue message
	$gameCards = new GameInstanceCards($gameInstanceId);
	$actualCommunityCards = $gameCards->GetSavedCommunityCardDtos(5);
	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo " - Community cards: " . json_encode($actualCommunityCards) . '<br/>';
		$nextCards = array_slice($indexCards, $activePlayers * 2 + 5, 5);
		echo " - Five cards after: " . json_encode($nextCards) . '<br/><br/>';
	}
	validateCardValues(-1, $actualCommunityCards, $communityCards);
}

function validateCardValues($playerId, $actualCards, $playerCards) {
	$countFailed = 0;
	if (NotEquals('Loaded Cards', count($playerCards), count($actualCards))) {
		$countFailed++;
		echo "*** FAILED - expected card values to be " . json_encode($playerCards);
		echo " got " . json_encode($actualCards) . " instead. <br/>";
	} else {
		$c = 0;
		foreach ($actualCards as $actual) {
			$exp = $playerCards[$c++];
			$countFailed += NotEquals("Card Value $c", $actual, $exp);
		}
	}
	if ($countFailed === 0) {
		echo "*** PASSED Card Validation for playerId $playerId <br/>";
	}
}

?>
