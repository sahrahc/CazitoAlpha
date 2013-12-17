<?php

/*
 * The reusable tests across all regression tests.
 */

function execLogin($playerName) {
	global $printAPI;
	$args = json_encode(array("playerName" => $playerName));

	$userDtoEncoded = login($args);
	$userDto = json_decode($userDtoEncoded);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
	return $userDto->userPlayerId;
}

function execLogout($playerId) {
	global $printAPI;
	$args = json_encode(array("requestingPlayerId" => $playerId));

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
	$ok = logout($args);
	if ($ok != '"OK"') {
		echo "*** FAILED: Log out should return OK rest response but returned $ok<br/>";
	}
}

function execJoinTable($casinoTableId, $playerId, $tableCode) {
	global $printAPI;

	if (is_null($casinoTableId)) {
		return;
	}
	$args = json_encode(array(
		"tableId" => $casinoTableId,
		"requestingPlayerId" => $playerId,
		"tableCode" => $tableCode));

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}

	$gameStatusDtoEncoded = JoinTable($args);

	$gameStatusDto = json_decode($gameStatusDtoEncoded);

	return $gameStatusDto;
}

function execCreateTable($tableName, $tableCode, $playerId, $tableSize) {
	global $printAPI;

	$args = json_encode(array(
		"tableName" => $tableName,
		"tableCode" => $tableCode,
		"requestingPlayerId" => $playerId,
		"tableSize" => $tableSize));

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}

	$gameStatusDtoEncoded = CreateTable($args);

	$gameStatusDto = json_decode($gameStatusDtoEncoded);

	return $gameStatusDto;
}

function execLoadSleeve($playerId, $cardNames) {
	global $printCheatingAPI;
	global $gameSessionId;
	$args = json_encode(array("itemType" => ItemType::LOAD_CARD_ON_SLEEVE,
		"userPlayerId" => $playerId,
		"gameSessionId" => $gameSessionId,
		"gameInstanceId" => null,
		"cardNameList" => $cardNames));
	$cardNamesEncoded = cheatLoadSleeve($args);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo "API Request " . __FUNCTION__ . ": $args <br />";
	}
	return json_decode($cardNamesEncoded);
}

function queueLeaveTable($gameSessionId, $playerId) {
	global $printAPI;

	$args = json_encode(array(
		"eventType" => ActionType::LeaveTable,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId));

	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
}

function queueStartLiveGame($gameSessionId, $playerId, $indexCards = null) {
	global $printAPI;
	$par = array(
		"eventType" => ActionType::StartGame,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId);

	if ($indexCards) {
		$par2 = array_merge($par, array('eventData' => $indexCards));
		$par = $par2;
	}
	$args = json_encode($par);

	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
}

function execStartPracticeSession($playerId, $indexCards) {
	global $printAPI;
	$args = json_encode(array("requestingPlayerId" => $playerId,
		"eventData" => $indexCards));
	$gameStatusDtoEncoded = startPracticeSession($args);
	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
	return json_decode($gameStatusDtoEncoded);
}

function queueStartPracticeGame($gameSessionId, $playerId) {
	global $printAPI;

	$args = json_encode(array(
		"eventType" => ActionType::StartPracticeGame,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId));

	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
}

function queueEndPracticeGame($gameSessionId, $playerId) {
	global $printAPI;

	$args = json_encode(array(
		"eventType" => ActionType::EndPractice,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId));

	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
}

function queueSendPlayerAction($gameSessionId, $gameInstanceId, $playerId, $actionType, $actionValue) {
	global $printAPI;
	global $dateTimeFormat;
	$currentDT = date($dateTimeFormat);
	$args = json_encode(array(
		"eventType" => ActionType::MakePokerMove,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"pokerActionType" => $actionType,
			"actionTime" => $currentDT,
			"actionValue" => $actionValue
	)));

	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printAPI) && $printAPI) {
		echo __FUNCTION__ . ": $args <br />";
	}
}

function queueCheatAcePusher($playerId, $cardNumber, $gameSessionId, $gameInstanceId) {
	global $printCheatingAPI;
	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::ACE_PUSHER,
			"playerCardNumber" => $cardNumber)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatSuitMarker($playerId, $suitType, $gameSessionId, $gameInstanceId) {
	global $printCheatingAPI;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => $suitType)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatRiverShuffler($playerId, $gameSessionId, $gameInstanceId) {
	global $printCheatingAPI;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::RIVER_SHUFFLER)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatRiverShufflerUse($playerId) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::RIVER_SHUFFLER_USE)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatUseCardOnSleeve($playerId, $playerCardNumber, $hiddenCardNumber) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::USE_CARD_ON_SLEEVE,
			"playerCardNumber" => $playerCardNumber,
			"hiddenCardNumber" => $hiddenCardNumber)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatStartCardMarking($playerId) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::SOCIAL_SPOTTER)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queuePokerPeeker($playerId, $otherPlayerId, $otherPlayerCardNumber) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::POKER_PEEKER,
			"otherPlayerId" => $otherPlayerId,
			"playerCardNumber" => $otherPlayerCardNumber)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatTuckLoad($playerId, $cardNames) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::TUCKER_TABLE_SLIDE_UNDER,
			"cardNameList" => $cardNames)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatTuckUse($playerId, $playerCardNumber, $hiddenCardNumber) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::TUCKER_TABLE_EXCHANGE,
			"playerCardNumber" => $playerCardNumber,
			"hiddenCardNumber" => $hiddenCardNumber)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatStartOilMarker($playerId) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::SNAKE_OIL_MARKER)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatAntiOilMarker($playerId, $otherPlayerId) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::ANTI_OIL_MARKER,
			"otherPlayerId" => $otherPlayerId)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

function queueCheatFaceCards($playerId) {
	global $printCheatingAPI;
	global $gameSessionId;
	global $gameInstanceId;

	$args = json_encode(array("eventType" => ActionType::Cheat,
		"gameSessionId" => $gameSessionId,
		"requestingPlayerId" => $playerId,
		"gameInstanceId" => $gameInstanceId,
		"eventData" => array(
			"itemType" => ItemType::KEEP_FACE_CARDS)));
	$qConn = QueueManager::GetConnection();
	$qCh = QueueManager::GetChannel($qConn);
	$qEx = QueueManager::GetSessionExchange($qCh);
	$qEx->publish($args, 's' . $gameSessionId);

	if (isset($printCheatingAPI) && $printCheatingAPI) {
		echo __FUNCTION__ . ": $args <br /><br />";
	}
}

/**
 * Tests login for a player name
 * @param type $playerName
 * @return type
 */
function testPlayerEntry($playerName) {

	$playerId = execLogin($playerName);
// TODO: replace with ASSERT
	if (is_null($playerId)) {
		echo "***FAILED: Login for $playerName did not return playerId <br/>";
//exit;
	}
	return $playerId;
}

function testPlayerLogout($playerId) {
	execLogout($playerId);
// TODO: test sleeve empty
}

function testPlayerLeaveTable($playerNumber, $playerId, $playNumberSkipped = null) {
	global $playerIds;
	global $q;
	global $expectedDto;
	global $numberPlayers;
	global $gameSessionId;

	echo " Testing leaving table for player # $playerNumber id " . $playerIds[$playerNumber] . " <br/><br/>";

	queueLeaveTable($gameSessionId, $playerId);
	ConsumeTableQueue();
	// if user who left had turn, skip turn is generated
	if (!is_null($playNumberSkipped)) {
		$eventData = verifyQMessage($playerIds[$playerNumber], $q[$playerNumber], EventType::UserEjected);
		testMove(0, $playerIds[1], PlayerStatusType::SKIPPED, null, $playNumberSkipped, true);
	}
	for ($i = 0; $i < $numberPlayers; $i++) {
		if ($i == $playerNumber) {
			continue;
		}
		$eventData = verifyQMessage($playerIds[$i], $q[$i], EventType::UserLeft);
// TODO: full compare, partial only for now
		$actualPlayerId = $eventData[0]->playerId;
		$failed = false;
		if ($actualPlayerId != $playerId) {
			$failed = true;
			echo "***FAILED: Player " . $playerIds[$i] . " told $actualPlayerId left instead of $playerId.<br />";
		}
	}
	if (!$failed) { // update player status dtos
		echo "PASSED User Leaving Table Test<br />";
	}
}

function testJoinTable($playerNumber, $playerId, $playerName, $buyIn, $firstTest = false) {
	global $tableName;
	global $tableSize;
	global $tableCode;
// out
	global $gameSessionId;
	global $casinoTableId;
	global $buyIn;
	global $expectedDto;

	if ($firstTest) {
		$tableDto = execCreateTable($tableName, $tableCode, $playerId, $tableSize);
		$gameSessionId = $tableDto->gameSessionId;
		$casinoTableId = $tableDto->casinoTableId;

		$expectedDto = InitGameStatusDto($gameSessionId);
		$expectedDto->casinoTableId = $casinoTableId;
	}
	$actualDto = execJoinTable($casinoTableId, $playerId, $tableCode);
	$expectedDto->userSeatNumber = $playerNumber;
	$expectedDto->playerStatusDtos[$playerNumber] = InitPlayerStatusDto(
			$playerId, $playerName, $playerNumber, $buyIn);
	ValidateGameStatusDtoNoPlay($actualDto, $expectedDto);
}

function scriptLoginJoinEarly($playersToJoin) {
	global $playerIds;
	global $playerNames;
	global $buyIn;
	global $qCh;
	global $q;

	for ($i = 0; $i < $playersToJoin; $i++) {
		$firstTest = false;
		if ($i == 0) {
			$firstTest = true;
		}
		$playerIds[$i] = testPlayerEntry($playerNames[$i]);
		testJoinTable($i, $playerIds[$i], $playerNames[$i], $buyIn, $firstTest);
		$q[$i] = QueueManager::GetPlayerQueue($playerIds[$i], $qCh);
// verify previously joined users received message
		for ($j = 0; $j < $i; $j++) {
			verifyQMessage($playerIds[$j], $q[$j], EventType::SeatTaken);
		}
	}
}

function scriptStartPractice($playerId, $buyIn, $indexCards) {
	global $numberPlayers;
	global $blind1Size;
	global $blind2Size;
// out
	global $gameSessionId;
	global $gameInstanceId;
	global $expectedDto;
	global $playerIds;

	$actualDto = execStartPracticeSession($playerId, $indexCards);

// initialize values
	$gameSessionId = $actualDto->gameSessionId;
	$gameInstanceId = $actualDto->gameInstanceId;
	$expectedDto = InitGameStatusDto($gameSessionId);
	$expectedDto->currentPotSize = $blind1Size + $blind2Size;
	$expectedDto->gameInstanceId = $gameInstanceId;

	// add test players
	for ($i = 0; $i < $numberPlayers; $i++) {
		$practicePlayerId = $actualDto->playerStatusDtos[$i]->playerId;
		$playerIds[$i] = $practicePlayerId;
		$practicePlayerName = $actualDto->playerStatusDtos[$i]->playerName;
		$expectedDto->playerStatusDtos[$i] = InitPlayerStatusDto(
				$practicePlayerId, $practicePlayerName, $i, $buyIn);
	}
	$expectedDto->userPlayerHandDto = $actualDto->userPlayerHandDto;
	InitGameStart(0, 4);
	/*
	  // 0 is dealer, 1 and 2 are the blinds, 3 is the first play
	  $expectedDto->dealerPlayerId = $playerIds[0];
	  UpdateExpPlayerStatusDto(1, PlayerStatusType::BLIND_BET, $blind1Size, 0);
	  UpdateExpPlayerStatusDto(2, PlayerStatusType::BLIND_BET, $blind2Size, 0);
	  UpdateExpPlayerStatusDto(3, PlayerStatusType::WAITING, 0, 0);
	  UpdateExpPlayerStatusDto(0, PlayerStatusType::WAITING, 0, 0);

	  $expectedDto->firstPlayerId = $playerIds[3];
	  $expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[3], $blind2Size, 0, $blind2Size * 2);
	  $expectedDto->casinoTableId = null;
	 */
	$expectedDto->userSeatNumber = 0;
	ValidateGameStatusDtoStart($actualDto, $expectedDto);
}

// join in middle of game
function testJoinTableMiddle($playerName, $gameStatus) {
	global $playerIds;
	global $q;
	global $casinoTableId;
	global $tableCode;
	global $expectedDto;
	global $numberPlayers;
	global $buyIn;

	$playerId = testPlayerEntry($playerName);

	echo "Player Id $playerId ($playerName) joining table... <br />";
	$actualDto = execJoinTable($casinoTableId, $playerId, $tableCode);

	InsertInactivePlayer($playerId, $playerName, $actualDto->userSeatNumber, $buyIn);

// validate everyone got message user joined and user got seat number
	switch ($gameStatus) {
		case GameStatus::STARTED:
			ValidateGameStatusDtoStart($actualDto, $expectedDto);
			break;
		case GameStatus::NONE:
			ValidateGameStatusDtoNoPlay($actualDto, $expectedDto);
			break;
		case GameStatus::ENDED:
			// not validating the play hands...
			//$expectedDto->currentPotSize =
			ValidateGameStatusDtoGameEnd($actualDto, $expectedDto);
			break;
		case GameStatus::IN_PROGRESS:
			ValidateGameStatusDtoAfterMove($actualDto, $expectedDto);
			break;
		case 'RoundEnd':
			ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto, true);
			break;
	}
// test other users got user joined message
	for ($i = 0; $i < $numberPlayers; $i++) {
		if ($playerIds[$i] != $playerId) {
			verifyQMessage($playerIds[$i], $q[$i], EventType::SeatTaken);
		}
	}
}

function testGameStart($playerNumber, $firstTest = false) {
	global $playerIds;
	global $q;
	global $playerHands;
	global $expectedDto;
	global $gameInstanceId;
// need to get verify response off message queue
	$actualDto = verifyQMessage($playerIds[$playerNumber], $q[$playerNumber], EventType::GameStarted);
	if ($firstTest) {
		$gameInstanceId = $actualDto->gameInstanceId;
		$expectedDto->gameInstanceId = $gameInstanceId;
	}
	$playerCardCode1 = $actualDto->userPlayerHandDto->pokerCard1Code;
	$playerCardCode2 = $actualDto->userPlayerHandDto->pokerCard2Code;
	if (isset($playerHands[$playerNumber])) {
		$playerCardCode1 = $playerHands[$playerNumber][0];
		$playerCardCode2 = $playerHands[$playerNumber][1];
	}
	$expectedDto->userPlayerHandDto = InitPlayerHandDtoStart($playerIds[$playerNumber], $playerCardCode1, $playerCardCode2);
	ValidateGameStatusDtoStart($actualDto, $expectedDto);
}

function testPracticeGameStart($playerNumber) {
	global $playerIds;
	global $q;
	global $expectedDto;
	global $gameInstanceId;
// need to get verify response off message queue, for practice only one queue
	$actualDto = verifyQMessage($playerIds[$playerNumber], $q[0], EventType::GameStarted);
	$gameInstanceId = $actualDto->gameInstanceId;
	$expectedDto->gameInstanceId = $gameInstanceId;
	$expectedDto->userPlayerHandDto = InitPlayerHandDtoStart($playerIds[$playerNumber], $actualDto->userPlayerHandDto->pokerCard1Code, $actualDto->userPlayerHandDto->pokerCard2Code);
	ValidateGameStatusDtoStart($actualDto, $expectedDto);
}

function testMove($playerNumber, $nextPlayerId, $type, $amount, $playNumber, $left = false) {
	global $expectedDto;
	global $playerIds;
	global $q;
	global $gameInstanceId;
	global $gameSessionId;
	global $startingPlayers; // all players receive messages
	//global $activePlayers;
	global $numberPlayers;


	if ($type === PlayerStatusType::SKIPPED && !$left) {
		$eventType = PlayerStatusType::SKIPPED;
	} else if ($type === PlayerStatusType::SKIPPED && $left) {
		$eventType = PlayerStatusType::LEFT;
	} else {
		$eventType = EventType::ChangeNextTurn;
	}
	$playerId = $playerIds[$playerNumber];
	$stake = $expectedDto->playerStatusDtos[$playerNumber]->currentStake;
	UpdateExpTurnPlayerStatusDto($playerNumber, $type, $amount, $stake, $playNumber, $left);
	if ($left) {
		$expectedDto->playerStatusDtos[$playerNumber]->status = PlayerStatusType::LEFT;
	}
	$expectedDto->currentPotSize +=$amount;
	if ($type !== PlayerStatusType::SKIPPED) {
		queueSendPlayerAction($gameSessionId, $gameInstanceId, $playerId, $type, $amount);
		ConsumeTableQueue();
	}

	/* compare results 
	  1) get next move (none if last play) */
	if ($playNumber !== 4 * $startingPlayers) {
		$isCheckOk = 0;
		if ($playNumber >= $startingPlayers) {
			$isCheckOk = 1;
		}
		$expectedDto->nextMoveDto = InitMove($gameInstanceId, $nextPlayerId, $amount, $isCheckOk, $amount * 2);
	}
	/* comparisons are for all player's messages */
	$updatedFlag = false;
	for ($i = 0; $i < $numberPlayers; $i++) {
		// may be null because of inactive players
		if (!isset($expectedDto->playerStatusDtos[$i]) ||
				$expectedDto->playerStatusDtos[$i]->status === PlayerStatusType::LEFT) {
			continue;
		}
		if ($left && $i === $playerNumber) {
			continue;
		}
		$actualDto = verifyQMessage($playerIds[$i], $q[$i], $eventType);
		/* 2) get community cards for comparison - round end comparison */
		if ($playNumber % $startingPlayers === 0 && $playNumber < 4 * $startingPlayers) {
			$expectedDto->newCommunityCards = $actualDto->newCommunityCards;
			ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto);
			/* 3) game end comparison */
		} else if ($playNumber == 4 * $startingPlayers) {
			$updatedFlag = UpdateExpPlayerStatusDtoFinal($actualDto, $updatedFlag);
			$expectedDto->gameStatus = GameStatus::ENDED;
			ValidateGameStatusDtoGameEnd($actualDto, $expectedDto);
			/* 4) not game end or round end comparison */
		} else {
			$expectedDto->newCommunityCards = null;
			ValidateGameStatusDtoAfterMove($actualDto, $expectedDto);
		}
	}
}

/* tests player generated practice play moves */

function testPracticeMove($playerNumber, $nextPlayerId, $type, $amount, $playNumber, $isPractice) {
	global $playerIds;
	global $q;
	global $gameSessionId;
	global $gameInstanceId;
	global $startingPlayers;
	global $expectedDto;
	global $lastBet;

	// if real player, move is not randomly generated
	$eventType = EventType::ChangeNextTurn;
	$playerId = $playerIds[$playerNumber];
	if (!$isPractice && $type != PlayerStatusType::SKIPPED) {
		queueSendPlayerAction($gameSessionId, $gameInstanceId, $playerId, $type, $amount);
		ConsumeTableQueue();
	} else {
		sleep(3);
		ProcessExpiredPokerMoves();
	}
	ConsumeTableQueue();

	// player 0 is the only one who gets a message.
	$i = 0;
	$actualDto = verifyQMessage($playerIds[$i], $q[$i], $eventType);
	// update expected player DTO, including move parameters randomly generated
	if ($isPractice) {
		$amount = $actualDto->turnPlayerStatusDto->lastPlayAmount;
		$type = $actualDto->turnPlayerStatusDto->status;
		if (!is_null($amount)) {
			$lastBet = $amount;
		}
	}
	$stake = $expectedDto->playerStatusDtos[$playerNumber]->currentStake;
	UpdateExpTurnPlayerStatusDto($playerNumber, $type, $amount, $stake, $playNumber);
	$expectedDto->currentPotSize +=$amount;

	/* compare results 
	  1) get next move for comparison (none if last play) */
	if ($playNumber !== 4 * $startingPlayers) {
		$isCheckOk = 0;
		if ($playNumber >= $startingPlayers) {
			$isCheckOk = 1;
		}
		$expectedDto->nextMoveDto = InitMove($gameInstanceId, $nextPlayerId, $amount, $isCheckOk, $amount * 2);
	}
	/* 2) get community cards for comparison - round end comparison */
	if ($playNumber % $startingPlayers === 0 && $playNumber < 4 * $startingPlayers) {
		$expectedDto->newCommunityCards = $actualDto->newCommunityCards;
		ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto);
		/* 3) game end comparison */
	} else if ($playNumber == 4 * $startingPlayers) {
		$expectedDto->gameStatus = GameStatus::ENDED;
		UpdateExpPlayerStatusDtoFinal($actualDto);
		ValidateGameStatusDtoGameEnd($actualDto, $expectedDto);
		/* 4) not game end or round end comparison */
	} else {
		$expectedDto->newCommunityCards = null;
		ValidateGameStatusDtoAfterMove($actualDto, $expectedDto);
	}
}

/* * *********************************************************************************** */

function scriptPlayerLeaves($playerNumber, $playNumberSkipped = NULL) {
	global $playerIds;
	global $expectedDto;
	// remove info since leaving in between games
	$leavingPlayerId = $playerIds[$playerNumber];
	testPlayerLeaveTable($playerNumber, $leavingPlayerId, $playNumberSkipped);
	testPlayerLogout($leavingPlayerId);
	array_splice($expectedDto->playerStatusDtos, $playerNumber, 1);
	}

?>
