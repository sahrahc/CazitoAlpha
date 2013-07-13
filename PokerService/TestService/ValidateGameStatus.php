<?php

function verifyQMessage($playerId, $queue, $eventType) {
    $message = $queue->get(AMQP_AUTOACK);
    if (!$message) {
        echo "***FAILED: no expected message $eventType received by $playerId <br />";
//exit;
    } else {
        $messageBody = $message->getBody();
        echo "Info: Message for player id $playerId: $messageBody <br /> <br />";
        $messageObject = json_decode($messageBody);
        if (!is_null($eventType) && $messageObject->eventType != $eventType) {
            echo "Warning: Player $playerId received message $messageObject->eventType instead of $eventType";
        }
        return $messageObject->eventData;
    }
}

function testPlayerEntry($playerName) {

// test login
    $_SESSION['param_playerName'] = $playerName;
    include('Feature_Login.php');
    $playerId = $_SESSION['param_playerId'];
// TODO: replace with ASSERT
    if (is_null($playerId)) {
        echo "***FAILED: Login for $playerName did not return playerId <br/>";
//exit;
    }
    return $playerId;
}

function testPlayerLogout($playerId) {
    $_SESSION['param_playerId'] = $playerId;
    include('Feature_Logout.php');
// TODO: test sleeve empty
}

function testPlayerLeaveTable($playerNumber, $playerId, $removeFlag) {
    global $playerIds;
    global $playerNames;
    global $q;
    global $expectedDto;
    global $playerStatusDtos;
    global $numberPlayers;

    $_SESSION['param_playerId'] = $playerId;
    include('Feature_LeaveTable.php');
    ConsumeTableQueue();
    for ($i = 0; $i < $numberPlayers; $i++) {
        if ($i != $playerNumber) {
            $eventData = verifyQMessage($playerIds[$i], $q[$i], EventType::UserLeft);
// TODO: full compare, partial only for now
            $actualPlayerId = $eventData[0]->playerId;
            $failed = false;
            if ($actualPlayerId != $playerId) {
                $failed = true;
                echo "***FAILED: Player " . $playerIds[$i] . " told $actualPlayerId left instead of $playerId.<br />";
            }
        }
    }
    if (!$failed) {
// update player status dtos
        $playerStatusDtos[$playerNumber]->status = PlayerStatusType::LEFT;
        echo "PASSED User Leaving Table Test<br />";
    }
    if ($removeFlag) {
// update $expectedDto and playerStatusDtos to remove the user who left
        array_splice($playerIds, $playerNumber, 1);
        array_splice($playerNames, $playerNumber, 1);
        array_splice($q, $playerNumber, 1);
        if (!is_null($playerStatusDtos)) {
            array_splice($playerStatusDtos, $playerNumber, 1);
        }
        if (!is_null($expectedDto) && !is_null($expectedDto->playerStatusDtos)) {
            array_splice($expectedDto->playerStatusDtos, $playerNumber, 1);
        }
    }
}

function testJoinTable($playerNumber, $playerId, $playerName, $buyIn, $firstTest = false) {
// out
    global $gameSessionId;
    global $casinoTableId;
    global $expectedDto;

    include('Feature_JoinTable.php');
    $actualDto = $_SESSION['param_gameStatusDto'];

    if ($firstTest) {

        $gameSessionId = $actualDto->gameSessionId;
        $_SESSION['param_gameSessionId'] = $gameSessionId;
        $expectedDto = InitGameStatusDto($gameSessionId);
        $casinoTableId = $actualDto->casinoTableId;
        $_SESSION['param_casinoTableId'] = $casinoTableId;
        $expectedDto->casinoTableId = $casinoTableId;

        // prior call to Feature_JoinTable created the table
        include('Feature_JoinTable.php');
        $actualDto = $_SESSION['param_gameStatusDto'];
    }
    $expectedDto->userSeatNumber = $playerNumber;
    $expectedDto->playerStatusDtos[$playerNumber] = InitPlayerStatus(
            $playerId, $playerName, $playerNumber, $buyIn);
    if (ValidateGameStatusDtoNoPlay($actualDto, $expectedDto) > 0) {
//exit;
    }
}

function testStartPractice($buyIn) {
    global $numberPlayers;
    global $blind1Size;
    global $blind2Size;
// out
    global $gameSessionId;
    global $gameInstanceId;
    global $expectedDto;
    global $playerIds;

    include('Feature_StartPracticeSession.php');
    $actualDto = $_SESSION['param_gameStatusDto'];

// initialize values
    $gameSessionId = $actualDto->gameSessionId;
    $gameInstanceId = $actualDto->gameInstanceId;
    $_SESSION['param_gameSessionId'] = $gameSessionId;
    $_SESSION['param_gameInstanceId'] = $gameInstanceId;
    $expectedDto = InitGameStatusDto($gameSessionId);
    $expectedDto->currentPotSize = $blind1Size + $blind2Size;
    $expectedDto->casinoTableId = null;
    $expectedDto->gameInstanceId = $gameInstanceId;
//$casinoTableId = $actualDto->casinoTableId;
//$_SESSION['param_casinoTableId'] = $casinoTableId;
//$expectedDto->casinoTableId = $casinoTableId;
    $expectedDto->userSeatNumber = 0;
//$expectedDto->playerStatusDtos[$playerNumber] = InitPlayerStatus(
//        $playerId, $playerName, $playerNumber, $buyIn);
// add test players
    for ($i = 0; $i < $numberPlayers; $i++) {
        $practicePlayerId = $actualDto->playerStatusDtos[$i]->playerId;
        $playerIds[$i] = $practicePlayerId;
        $practicePlayerName = $actualDto->playerStatusDtos[$i]->playerName;
        $expectedDto->playerStatusDtos[$i] = InitPlayerStatus(
                $practicePlayerId, $practicePlayerName, $i, $buyIn);
    }
    $expectedDto->userPlayerHandDto = $actualDto->userPlayerHandDto;
// 0 is dealer, 1 and 2 are the blinds, 3 is the first play
    UpdatePlayerStatus(1, PlayerStatusType::BLIND_BET, $blind1Size, 0);
    UpdatePlayerStatus(2, PlayerStatusType::BLIND_BET, $blind2Size, 0);
    UpdatePlayerStatus(3, PlayerStatusType::WAITING, 0, 0);
    UpdatePlayerStatus(0, PlayerStatusType::WAITING, 0, 0);

    $expectedDto->firstPlayerId = $playerIds[3];
    $expectedDto->dealerPlayerId = $playerIds[0];
    $expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[3], $blind2Size, 0, $blind2Size * 2);

    if (ValidateGameStatusDtoStart($actualDto, $expectedDto) > 0) {
//exit;
    }
// add player ids
}

// join in middle of game
function testJoinTableMiddle($playerNumber, $playerId, $playerName, $seatNumber, $buyIn, $gameStatus) {
    global $numberPlayers;
    global $playerIds;
    global $q;
// out
    global $expectedDto;
    global $playerStatusDtos;

    include('Feature_JoinTable.php');
    $actualDto = $_SESSION['param_gameStatusDto'];

// validate everyone got message user joined and user got seat number
    $expectedDto->userSeatNumber = $playerNumber;
    $expectedDto->playerStatusDtos[$playerNumber] = InitPlayerStatus(
            $playerId, $playerName, $seatNumber, $buyIn);
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
            for ($i = 0; $i < $numberPlayers; $i++) {
                $expectedDto->playerStatusDtos[$i] = $playerStatusDtos[$i];
            }
            ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto, true);
            break;
    }
// test other users got user joined message
    for ($i = 0; $i <= $numberPlayers; $i++) {
        if ($i == $playerNumber) {
            continue;
        }
        verifyQMessage($playerIds[$i], $q[$i], EventType::SeatTaken);
    }
}

function testGameStart($playerNumber, $firstTest = false) {
    global $playerIds;
    global $q;
// out
    global $expectedDto;
    global $gameInstanceId;
// need to get verify response off message queue
    $actualDto = verifyQMessage($playerIds[$playerNumber], $q[$playerNumber], EventType::GameStarted);
    if ($firstTest) {
        $gameInstanceId = $actualDto->gameInstanceId;
        $_SESSION['param_gameInstanceId'] = $gameInstanceId;
        $expectedDto->gameInstanceId = $gameInstanceId;
    }
    $expectedDto->userPlayerHandDto = InitPlayerHandStart($playerIds[$playerNumber], $actualDto->userPlayerHandDto->pokerCard1Code, $actualDto->userPlayerHandDto->pokerCard2Code);
    if (ValidateGameStatusDtoStart($actualDto, $expectedDto) > 0) {
//exit;
    }
}

function testPracticeGameStart($playerNumber) {
    global $playerIds;
    global $q;
// out
    global $expectedDto;
    global $gameInstanceId;
// need to get verify response off message queue, for practice only one queue
    $actualDto = verifyQMessage($playerIds[$playerNumber], $q[0], EventType::GameStarted);
    $gameInstanceId = $actualDto->gameInstanceId;
    $_SESSION['param_gameInstanceId'] = $gameInstanceId;
    $expectedDto->gameInstanceId = $gameInstanceId;
    $expectedDto->userPlayerHandDto = InitPlayerHandStart($playerIds[$playerNumber], $actualDto->userPlayerHandDto->pokerCard1Code, $actualDto->userPlayerHandDto->pokerCard2Code);
    if (ValidateGameStatusDtoStart($actualDto, $expectedDto) > 0) {
//exit;
    }
}

function testMove($playerNumber, $nextPlayerId, $type, $amount, $playNumber) {
    global $expectedDto;
    global $playerStatusDtos;
    global $playerIds;
    global $q;
    global $gameInstanceId;
    global $numberPlayers;

    $eventType = EventType::ChangeNextTurn;
    $playerId = $playerIds[$playerNumber];
    $stake = $playerStatusDtos[$playerNumber]->currentStake;
    UpdateMovePlayerStatus($playerNumber, $type, $amount, $stake, $playNumber);
    $expectedDto->currentPotSize +=$amount;
    if ($eventType != PlayerStatusType::SKIPPED) {
        $_SESSION['param_turnPlayerId'] = $playerId;
        $_SESSION['param_pokerActionType'] = $type;
        $_SESSION['param_pokerActionValue'] = $amount;
        include('Feature_SendPlayerAction.php');
        ConsumeTableQueue();
    }

// no next move for last play
    if ($playNumber !== 4 * $numberPlayers) {
        $isCheckOk = 0;
        if ($playNumber >= $numberPlayers) {
            $isCheckOk = 1;
        }
        $expectedDto->nextMoveDto = InitMove($gameInstanceId, $nextPlayerId, $amount, $isCheckOk, $amount * 2);
    }
    $updatedFlag = false;
    for ($i = 0; $i < $numberPlayers; $i++) {
        if ($playerStatusDtos[$i]->status == PlayerStatusType::LEFT) {
            continue;
        }
        $actualDto = verifyQMessage($playerIds[$i], $q[$i], $eventType);
        if ($playNumber % $numberPlayers === 0 && $playNumber < 4 * $numberPlayers) {
            $expectedDto->newCommunityCards = $actualDto->newCommunityCards;
            if (ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto) > 0) {
//exit;
            }
        } else if ($playNumber == 4 * $numberPlayers) {
            // update final player status only once
            if (!$updatedFlag) {
                $updatedFlag = true;
                UpdateFinalPlayerStatuses($actualDto);
            }
            if (ValidateGameStatusDtoGameEnd($actualDto, $expectedDto) > 0) {
//exit;
            }
        } else {
            $expectedDto->newCommunityCards = null;
            if (ValidateGameStatusDtoAfterMove($actualDto, $expectedDto) > 0) {
//exit;
            }
        }
    }
}

/* tests the move and triggers practice plays */

function testPracticeMove($playerNumber, $nextPlayerId, $type, $amount, $playNumber, $isPractice) {
    global $playerIds;
    global $q;
    global $gameInstanceId;
    global $numberPlayers;
// out
    global $expectedDto;
    global $playerStatusDtos;
    global $lastBet;
// if real player, move is not randomly generated
    $eventType = EventType::ChangeNextTurn;
    $playerId = $playerIds[$playerNumber];
    if (!$isPractice && $type != PlayerStatusType::SKIPPED) {
        $_SESSION['param_turnPlayerId'] = $playerId;
        $_SESSION['param_pokerActionType'] = $type;
        $_SESSION['param_pokerActionValue'] = $amount;
        include('Feature_SendPlayerAction.php');
    } else {
        sleep(3);
        ProcessExpiredPokerMoves();
    }
    ConsumeTableQueue();

    $i = 0;
    $actualDto = verifyQMessage($playerIds[$i], $q[$i], $eventType);
    $stake = $playerStatusDtos[$playerNumber]->currentStake;
    if ($isPractice) {
// moves are randomly generated
        $amount = $actualDto->turnPlayerStatusDto->lastPlayAmount;
        $type = $actualDto->turnPlayerStatusDto->status;
        if (!is_null($amount)) {
            $lastBet = $amount;
        }
    }
    UpdateMovePlayerStatus($playerNumber, $type, $amount, $stake, $playNumber);
    $expectedDto->currentPotSize +=$amount;
// no next move for last play
    if ($playNumber !== 4 * $numberPlayers) {
        $isCheckOk = 0;
        if ($playNumber >= $numberPlayers) {
            $isCheckOk = 1;
        }
        $expectedDto->nextMoveDto = InitMove($gameInstanceId, $nextPlayerId, $amount, $isCheckOk, $amount * 2);
    }
    if ($playNumber % $numberPlayers === 0 && $playNumber < 4 * $numberPlayers) {
        $expectedDto->newCommunityCards = $actualDto->newCommunityCards;
        if (ValidateGameStatusDtoRoundEnd($actualDto, $expectedDto) > 0) {
//exit;
        }
    } else if ($playNumber == 4 * $numberPlayers) {
        UpdateFinalPlayerStatuses($actualDto);
        if (ValidateGameStatusDtoGameEnd($actualDto, $expectedDto) > 0) {
//exit;
        }
    } else {
        $expectedDto->newCommunityCards = null;
        if (ValidateGameStatusDtoAfterMove($actualDto, $expectedDto) > 0) {
//exit;
        }
    }
}

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

function InitPlayerStatus($playerId, $playerName, $seatNumber, $stake) {
    $playerStatus = new PlayerStatusDto();
    $playerStatus->playerId = $playerId;
    $playerStatus->playerName = $playerName;
    $playerStatus->seatNumber = $seatNumber;
    $playerStatus->status = PlayerStatusType::WAITING;
    $playerStatus->currentStake = $stake;
// if game not started, play amounts are null
//$playerStatus->lastPlayAmount = 0;
//$playerStatus->lastPlayInstanceNumber = 0;
    return $playerStatus;
}

function UpdatePlayerStatus($playerNumber, $status, $amount, $playNumber) {
    global $expectedDto;
    $expectedDto->playerStatusDtos[$playerNumber]->status = $status;
    $expectedDto->playerStatusDtos[$playerNumber]->currentStake -= $amount;
    $expectedDto->playerStatusDtos[$playerNumber]->lastPlayAmount = $amount;
    $expectedDto->playerStatusDtos[$playerNumber]->lastPlayInstanceNumber = $playNumber;
}

function UpdateMovePlayerStatus($playerNumber, $status, $amount, $stake, $playNumber) {
    global $expectedDto;
    global $playerStatusDtos;
    global $playerIds;
    $expectedDto->turnPlayerStatusDto = new PlayerStatusDto();
    $expectedDto->turnPlayerStatusDto->playerId = $playerIds[$playerNumber];
    $expectedDto->turnPlayerStatusDto->status = $status;
    $expectedDto->turnPlayerStatusDto->currentStake = $stake - $amount;
/// null amount if folded
    if (is_null($amount) && $status != PlayerStatusType::CHECKED) {
        $expectedDto->turnPlayerStatusDto->lastPlayAmount = $playerStatusDtos[$playerNumber]->lastPlayAmount;
    } else {
        $expectedDto->turnPlayerStatusDto->lastPlayAmount = $amount;
        $playerStatusDtos[$playerNumber]->lastPlayAmount = $amount;
    }
    $expectedDto->turnPlayerStatusDto->lastPlayInstanceNumber = $playNumber;
    $expectedDto->turnPlayerStatusDto->seatNumber = $playerStatusDtos[$playerNumber]->seatNumber;
// new player stake and status
    $playerStatusDtos[$playerNumber]->lastPlayInstanceNumber = $playNumber;
    $playerStatusDtos[$playerNumber]->status = $status;
    $playerStatusDtos[$playerNumber]->currentStake = $stake - $amount;
}

function UpdateFinalPlayerStatuses($actualDto) {
    global $playerIds;
    global $numberPlayers;
    global $expectedDto;
    global $playerStatusDtos;

    $winnerNumber = array_search($actualDto->winningPlayerId, $playerIds);
    for ($i = 0; $i < $numberPlayers; $i++) {
        if ($playerStatusDtos[$i]->status == PlayerStatusType::WAITING) {
            continue;
        }
        if ($playerStatusDtos[$i]->status != PlayerStatusType::LEFT) {
            $playerStatusDtos[$i]->status = PlayerStatusType::LOST;
        }
        if ($i == $winnerNumber) {
            $playerStatusDtos[$i]->status = PlayerStatusType::WON;
            $playerStatusDtos[$i]->currentStake += $expectedDto->currentPotSize;
            // no player status dtos
            //$expectedDto->playerStatusDtos[$i]->status = $playerStatusDtos[$i]->status;
            //$expectedDto->playerStatusDtos[$i]->currentStake = $playerStatusDtos[$i]->currentStake;
        }
        $expectedDto->playerStatusDtos[$i] = $playerStatusDtos[$i];
    }
}

/**
 * At beginning of game, a player's cards are sent back
 */
function InitPlayerHandStart($playerId, $code1, $code2) {
    $playerHand = new PlayerHandDto($playerId, $code1, $code2);
    return $playerHand;
}

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
function InitPlayerHandEnd($playerHand, $handType) {
    $updatedHand = new PlayerHandDto($playerHand->playerId, $playerHand->pokerCard1Code, $playerHand->pokerCard2Code);
    $updatedHand->pokerHandType = $handType;
    return $updatedHand;
}

// returns 0 if failed, 1 if passed
// does this work if both are null? Verify it should
function Equals($type, $current, $expected) {
    if ($type === "StatusDateTime" || $current instanceof DateTime) {
        $interval = date_diff($current, $expected);
        if ($interval->format('%s') > 60) {
            return 0;
        } else {
            return 1;
        }
    }
// TODO: within 1 minute and expected should be earlier
    if ($current != $expected) {
        $currentString = json_encode($current);
        $expectedString = json_encode($expected);
        echo "*** FAILED $type - " . $currentString . " is not " . $expectedString . "<br />";
        return 0;
    }
    return 1;
}

function Greater($type, $earlier, $later) {
    $earlierJson = json_encode($earlier);
    $laterJson = json_encode($later);
    if ($earlier > $later) {
        echo "*** FAILED $type - $earlierJson is greater/later than $laterJson <br />";
        return 0;
    }
    return 1;
}

function IsNull($type, $value) {
    if (!is_null($value)) {
        $valueString = json_encode($value);
        echo "*** FAILED $type - null expected found $valueString <br />";
        return 0;
    }
    return 1;
}

function IsNotNull($type, $value) {
    if (is_null($value)) {
        echo "*** FAILED $type - $value expected null  <br />";
        return 0;
    }
    return 1;
}

/**
 * Expected:
 * GameInstanceId - null
 * *GameSessionId - compare
 * *CasinoTableId - compare
 * *GameStatus - compare
 * *StatusDateTime - compare
 * DealerPlayerId - null
 * *PlayerStatusDtos - compare, seats should have been given
 * TurnPlayerStatusDto - null
 * NextMoveDto - null
 * CommunityCards - null
 * NewCommunityCards - null
 * *WaitingListSize - compare
 * UserPlayerHandDto - null
 * *UserSeatNumber - compare
 * WinningPlayerId - null
 * PlayerHandsDto - null 
 */
function ValidateGameStatusDtoNoPlay($dto, $expected) {
    global $dateTimeFormat;
    echo "Validating Game Status Should be No Play Started Yet <br />";
    $countFailed = 0;

    if (!IsNull('Game Instance Id', $dto->gameInstanceId)) {
        $countFailed++;
    }
    if (!Equals('Game Session', $dto->gameSessionId, $expected->gameSessionId)) {
        $countFailed++;
    }
    if (!Equals('Casino Table Id', $dto->casinoTableId, $expected->casinoTableId)) {
        $countFailed++;
    }
    if (!Equals('GameStatus', $dto->gameStatus, GameStatus::NONE)) {
        $countFailed++;
    }
    $dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
    $expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
    if (!Equals('StatusDateTime', $dtoDateTime, $expDateTime)) {
        $countFailed++;
    }
    if (!Equals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId)) {
        $countFailed++;
    }
    if (!Equals('# Player Statuses', count($dto->playerStatusDtos), count($expected->playerStatusDtos))) {
        $countFailed++;
    } else {
        $i = 0;
        foreach ($dto->playerStatusDtos as $actual) {
            $exp = $expected->playerStatusDtos[$i];
            if (!Equals("Player $i Id", $actual->playerId, $exp->playerId)) {
                $countFailed++;
            }
            if (!Equals("Player $i Name", $actual->playerName, $exp->playerName)) {
                $countFailed++;
            }
            if (!Equals("Player $i Seat", $actual->seatNumber, $exp->seatNumber)) {
                $countFailed++;
            }
            if (!Equals("Player $i Status", $actual->status, PlayerStatusType::WAITING)) {
                $countFailed++;
            }
            if (!Equals("Player $i Stake", $actual->currentStake, $exp->currentStake)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
                $countFailed++;
            }
            $i++;
        }
    }
    if (!Equals('Pot Size', $dto->currentPotSize, $expected->currentPotSize)) {
        $countFailed++;
    }
    if (!IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
        $countFailed++;
    }
    if (!IsNull('Next Move', $dto->nextMoveDto)) {
        $countFailed++;
    }
    if (!IsNull('Community Cards', $dto->communityCards)) {
        $countFailed++;
    }
    if (!IsNull('New Cards', $dto->newCommunityCards)) {
        $countFailed++;
    }
    if (!Equals('Waiting List', $dto->waitingListSize, 0)) {
        $countFailed++;
    }
    if (!IsNull('User Hand', $dto->userPlayerHandDto)) {
        $countFailed++;
    }
    if (!Equals('User Seat', $dto->userSeatNumber, $expected->userSeatNumber)) {
        $countFailed++;
    }
    if (!IsNull('Winner Id', $dto->winningPlayerId)) {
        $countFailed++;
    }
    if (!IsNull('All Hands', $dto->playerHandsDto)) {
        $countFailed++;
    }
    if ($countFailed === 0) {
        echo "*** PASSED ALL TESTS <br />";
    }
}

/**
 * Expected when GAME STARTED
 * GameInstanceId - compare
 * *GameSessionId - compare
 * *CasinoTableId - null
 * *GameStatus - compare
 * *StatusDateTime - compare
 * *DealerPlayerId - compare
 * PlayerStatusDtos - compare
 * *TurnPlayerStatusDto - null
 * *NextMoveDto - compare
 * CommunityCards - null
 * NewCommunityCards - null
 * *WaitingListSize - compare
 * UserPlayerHandDto - compare
 * UserSeatNumber - null
 * WinningPlayerId - null
 * PlayerHandsDto - null 
 */
function ValidateGameStatusDtoStart($dto, $expected) {
    global $dateTimeFormat;
    echo "Validating Game Status Should be Game Started<br />";

    $countFailed = 0;
    if (!Equals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId)) {
        $countFailed++;
    }
    if (!Equals('Game Session', $dto->gameSessionId, $expected->gameSessionId)) {
        $countFailed++;
    }
    if (!IsNull('Casino Table Id', $dto->casinoTableId)) {
        $countFailed++;
    }
    if (!Equals('GameStatus', $dto->gameStatus, GameStatus::STARTED)) {
        $countFailed++;
    }
    $dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
    $expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
    if (!Equals('StatusDateTime', $dtoDateTime, $expDateTime)) {
        $countFailed++;
    }
    if (!Equals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId)) {
        $countFailed++;
    }
    if (!Equals('# Player Statuses', count($dto->playerStatusDtos), count($expected->playerStatusDtos))) {
        $countFailed++;
    } else {
        $i = 0;
        foreach ($dto->playerStatusDtos as $actual) {
            $exp = $expected->playerStatusDtos[$i];
            if (!Equals("Player $i Id", $actual->playerId, $exp->playerId)) {
                $countFailed++;
            }
            if (!Equals("Player $i Name", $actual->playerName, $exp->playerName)) {
                $countFailed++;
            }
            if (!Equals("Player $i Seat", $actual->seatNumber, $exp->seatNumber)) {
                $countFailed++;
            }
            if (!Equals("Player $i Status", $actual->status, $exp->status)) {
                $countFailed++;
            }
            if (!Equals("Player $i Stake", $actual->currentStake, $exp->currentStake)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
                $countFailed++;
            }
            $i++;
        }
    }
    if (!Equals('Pot Size', $dto->currentPotSize, $expected->currentPotSize)) {
        $countFailed++;
    }
    if (!IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
        $countFailed++;
    }
    if (!IsNotNull('Next Move', $dto->nextMoveDto)) {
        $countFailed++;
    } else {
        $actual = $dto->nextMoveDto;
        $exp = $dto->nextMoveDto;
        if (!Equals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId)) {
            $countFailed++;
        }
        if (!Equals("Next Game Player Id", $actual->playerId, $exp->playerId)) {
            $countFailed++;
        }
        if (!Equals("Expiration Date", $actual->expirationDate, $exp->expirationDate)) {
            $countFailed++;
        }
        if (!Equals("Call Amount", $actual->callAmount, $exp->callAmount)) {
            $countFailed++;
        }
        if (!Equals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed)) {
            $countFailed++;
        }
        if (!Equals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount)) {
            $countFailed++;
        }
    }
    /*
      if (!Equals('# Community Cards', $dto->communityCards, 3)) {$countFailed++;}
      else {
      $i = 0;
      foreach($dto->communityCards as $actual) {
      if (!Equals('Community Card 1', $actual, $expected[$i++])) {$countFailed++;}
      }
      }
     * 
     */
    if (!IsNull('New Cards', $dto->newCommunityCards)) {
        $countFailed++;
    }
    if (!Equals('Waiting List', $dto->waitingListSize, 0)) {
        $countFailed++;
    }
    if (!IsNotNull('User Hand', $dto->userPlayerHandDto)) {
        $countFailed++;
    } else {
        $actual = $dto->userPlayerHandDto;
        $exp = $dto->userPlayerHandDto;
        if (!Equals('User Player Id', $actual->playerId, $exp->playerId))
            ;
        if (!Equals('User Hand Card 1', $actual->pokerCard1Code, $exp->pokerCard1Code))
            ;
        if (!Equals('User Hand Card 2', $actual->pokerCard2Code, $exp->pokerCard2Code))
            ;
        if (!IsNull('Hand Type', $actual->pokerHandType))
            ;
    }
    if (!Equals('User Seat', $dto->userSeatNumber, $expected->userSeatNumber)) {
        $countFailed++;
    }
    if (!IsNull('Winner Id', $dto->winningPlayerId)) {
        $countFailed++;
    }
    if (!IsNull('All Hands', $dto->playerHandsDto)) {
        $countFailed++;
    }
    if ($countFailed === 0) {
        echo "*** PASSED ALL TESTS <br />";
    }
}

/**
 * Expected:
 * GameInstanceId - compare
 * *GameSessionId - compare
 * *CasinoTableId - null
 * GameStatus - compare
 * StatusDateTime - compare
 * DealerPlayerId - compare
 * PlayerStatusDtos - null
 * *TurnPlayerStatusDto - compare
 * *NextMoveDto - compare
 * CommunityCards - null
 * NewCommunityCards - null
 * *WaitingListSize - compare
 * UserPlayerHandDto - null
 * UserSeatNumber - null
 * WinningPlayerId - null
 * PlayerHandsDto - null 
 */
function ValidateGameStatusDtoAfterMove($dto, $expected) {
    global $dateTimeFormat;

    echo "Validating Game Status after a move (no round or game end) <br />";
    $countFailed = 0;

    if (!Equals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId)) {
        $countFailed++;
    }
    if (!Equals('Game Session', $dto->gameSessionId, $expected->gameSessionId)) {
        $countFailed++;
    }
    if (!IsNull('Casino Table Id', $dto->casinoTableId)) {
        $countFailed++;
    }
    if (!Equals('GameStatus', $dto->gameStatus, $expected->gameStatus)) {
        $countFailed++;
    }
    $dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
    $expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
    if (!Equals('StatusDateTime', $dtoDateTime, $expDateTime)) {
        $countFailed++;
    }
    if (!Equals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId)) {
        $countFailed++;
    }
    if (!IsNull('Player Statuses', $dto->playerStatusDtos)) {
        $countFailed++;
    }
    if (!Equals('Pot Size', $dto->currentPotSize, $expected->currentPotSize)) {
        $countFailed++;
    }
    if (!IsNotNull('Updated Player', $dto->turnPlayerStatusDto)) {
        $countFailed++;
    } else {
        $actual = $dto->turnPlayerStatusDto;
        $exp = $expected->turnPlayerStatusDto;
        if (!Equals("Updated Player Id", $actual->playerId, $exp->playerId)) {
            $countFailed++;
        }
        if (!IsNull("Updated Player Name", $actual->playerName)) {
            $countFailed++;
        }
        if (!Equals("Updated Player Seat", $actual->seatNumber, $exp->seatNumber)) {
            $countFailed++;
        }
        if (!Equals("Updated Player Status", $actual->status, $exp->status)) {
            $countFailed++;
        }
        if (!Equals("Updated Player Stake", $actual->currentStake, $exp->currentStake)) {
            $countFailed++;
        }
        if (!Equals("Updated Player Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
            $countFailed++;
        }
        if (!Equals("Updated Player Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
            $countFailed++;
        }
    }
    if (!IsNotNull('Next Move', $dto->nextMoveDto)) {
        $countFailed++;
    } else {
        $actual = $dto->nextMoveDto;
        $exp = $dto->nextMoveDto;
        if (!Equals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId)) {
            $countFailed++;
        }
        if (!Equals("Next Game Player Id", $actual->playerId, $exp->playerId)) {
            $countFailed++;
        }
        if (!Equals("Expiration Date", $actual->expirationDate, $exp->expirationDate)) {
            $countFailed++;
        }
        if (!Equals("Call Amount", $actual->callAmount, $exp->callAmount)) {
            $countFailed++;
        }
        if (!Equals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed)) {
            $countFailed++;
        }
        if (!Equals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount)) {
            $countFailed++;
        }
    }
    if (!IsNull('Community Cards', $dto->communityCards)) {
        $countFailed++;
    }
    if (!IsNull('New Cards', $dto->newCommunityCards)) {
        $countFailed++;
    }
    if (!IsNull('Waiting List', $dto->waitingListSize)) {
        $countFailed++;
    }
    if (!IsNull('User Hand', $dto->userPlayerHandDto)) {
        $countFailed++;
    }
    if (!IsNull('User Seat', $dto->userSeatNumber)) {
        $countFailed++;
    }
    if (!IsNull('Winner Id', $dto->winningPlayerId)) {
        $countFailed++;
    }
    if (!IsNull('All Hands', $dto->playerHandsDto)) {
        $countFailed++;
    }
    if ($countFailed === 0) {
        echo "*** PASSED ALL TESTS <br />";
    }
}

/**
 * Expected:
 * GameInstanceId - compare
 * *GameSessionId - compare
 * *CasinoTableId - null
 * GameStatus - compare
 * StatusDateTime - compare
 * DealerPlayerId - compare
 * PlayerStatusDto - null
 * *TurnPlayerStatusDto - compare
 * *NextMoveDto - compare
 * CommunityCards - null
 * *NewCommunityCards - compare
 * *WaitingListSize - compare
 * UserPlayerHandDto - null
 * UserSeatNumber - null
 * WinningPlayerId - null
 * PlayerHandsDto - null 
 */
function ValidateGameStatusDtoRoundEnd($dto, $expected, $isNewlyJoined = false) {
    global $dateTimeFormat;
    echo "Validating Game ROUND END <br />";
    $countFailed = 0;
    if (!Equals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId)) {
        $countFailed++;
    }
    if (!Equals('Game Session', $dto->gameSessionId, $expected->gameSessionId)) {
        $countFailed++;
    }

    if (!Equals('GameStatus', $dto->gameStatus, $expected->gameStatus)) {
        $countFailed++;
    }
    $dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
    $expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
    if (!Equals('StatusDateTime', $dtoDateTime, $expDateTime)) {
        $countFailed++;
    }
    if (!Equals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId)) {
        $countFailed++;
    }
    if (!Equals('Pot Size', $dto->currentPotSize, $expected->currentPotSize)) {
        $countFailed++;
    }
    if (!IsNotNull('Next Move', $dto->nextMoveDto)) {
        $countFailed++;
    } else {
        $actual = $dto->nextMoveDto;
        $exp = $dto->nextMoveDto;
        if (!Equals("Next Game Instance Id", $actual->gameInstanceId, $exp->gameInstanceId)) {
            $countFailed++;
        }
        if (!Equals("Next Game Player Id", $actual->playerId, $exp->playerId)) {
            $countFailed++;
        }
        if (!Equals("Expiration Date", $actual->expirationDate, $exp->expirationDate)) {
            $countFailed++;
        }
        if (!Equals("Call Amount", $actual->callAmount, $exp->callAmount)) {
            $countFailed++;
        }
        if (!Equals("Check Amount", $actual->isCheckAllowed, $exp->isCheckAllowed)) {
            $countFailed++;
        }
        if (!Equals("Raise Amount", $actual->raiseAmount, $exp->raiseAmount)) {
            $countFailed++;
        }
    }

// if newly joined return all
    if ($isNewlyJoined) {
        if (!IsNotNull('Casino Table Id', $dto->casinoTableId)) {
            $countFailed++;
        }
        $i = 0;
        if (!is_null($dto->playerStatusDtos)) {
            foreach ($dto->playerStatusDtos as $actual) {
                $exp = $expected->playerStatusDtos[$i];
                if (!Equals("Player $i Id", $actual->playerId, $exp->playerId)) {
                    $countFailed++;
                }/*
                  if (!Equals("Player $i Name", $actual->playerName, $exp->playerName)) {
                  $countFailed++;
                  } */
                if (!Equals("Player $i Seat", $actual->seatNumber, $exp->seatNumber)) {
                    $countFailed++;
                }
                if (!Equals("Player $i Status", $actual->status, $exp->status)) {
                    $countFailed++;
                }
                if (!Equals("Player $i Stake", $actual->currentStake, $exp->currentStake)) {
                    $countFailed++;
                }
                if (!Equals("Player $i Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
                    $countFailed++;
                }
                if (!Equals("Player $i Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
                    $countFailed++;
                }
                $i++;
            }
        }
        if (!IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
            $countFailed++;
        }
        if (!IsNotNull('Community Cards', $dto->communityCards)) {
            $countFailed++;
        }
        if (!IsNull('New Community Cards', $dto->newCommunityCards)) {
            $countFailed++;
        }
        if (!IsNotNull('Waiting List', $dto->waitingListSize)) {
            $countFailed++;
        }
    } else {
        if (!IsNull('Casino Table Id', $dto->casinoTableId)) {
            $countFailed++;
        }

        if (!IsNull('Player Statuses', $dto->playerStatusDtos)) {
            $countFailed++;
        }
        if (!IsNotNull('Updated Player', $dto->turnPlayerStatusDto)) {
            $countFailed++;
        } else {
            $actual = $dto->turnPlayerStatusDto;
            $exp = $expected->turnPlayerStatusDto;
            if (!Equals("Updated Player Id", $actual->playerId, $exp->playerId)) {
                $countFailed++;
            }
            if (!IsNull("Updated Player Name", $actual->playerName)) {
                $countFailed++;
            }
            if (!Equals("Updated Player Seat", $actual->seatNumber, $exp->seatNumber)) {
                $countFailed++;
            }
            if (!Equals("Updated Player Status", $actual->status, $exp->status)) {
                $countFailed++;
            }
            if (!Equals("Updated Player Stake", $actual->currentStake, $exp->currentStake)) {
                $countFailed++;
            }
            if (!Equals("Updated Player Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
                $countFailed++;
            }
            if (!Equals("Updated Player Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
                $countFailed++;
            }
        }
        if (!IsNull('Community Cards', $dto->communityCards)) {
            $countFailed++;
        }
        if (!Equals('New Community Cards', count($dto->newCommunityCards), count($expected->newCommunityCards))) {
            $countFailed++;
        }
        if (!Greater('New Community Cards', 0, count($dto->newCommunityCards))) {
            $countFailed++;
        } else {
            $i = 0;
            if (count($dto->newCommunityCards)) {
                foreach ($dto->newCommunityCards as $actual) {
                    $exp = $expected->newCommunityCards[$i];
                    if (!Equals('New Community Card', $actual, $exp)) {
                        $countFailed++;
                    }
                    $i++;
                }
            }
            if (!IsNull('User Hand', $dto->userPlayerHandDto)) {
                $countFailed++;
            }
            if (!IsNull('User Seat', $dto->userSeatNumber)) {
                $countFailed++;
            }
        }
        if (!IsNull('Waiting List', $dto->waitingListSize)) {
            $countFailed++;
        }
    }
    if (!IsNull('Winner Id', $dto->winningPlayerId)) {
        $countFailed++;
    }
    if (!IsNull('All Hands', $dto->playerHandsDto)) {
        $countFailed++;
    }
    if ($countFailed === 0) {
        echo "*** PASSED ALL TESTS <br />";
    }
}

/**
 * Expected:
 * GameInstanceId - compare
 * *GameSessionId - compare
 * *CasinoTableId - null
 * GameStatus - compare
 * StatusDateTime - compare
 * DealerPlayerId - compare
 * PlayerStatusDtos - null
 * *TurnPlayerStatusDto - compare
 * *NextMoveDto - null
 * CommunityCards - null
 * NewCommunityCards - null
 * *WaitingListSize - compare
 * UserPlayerHandDto - null
 * UserSeatNumber - null
 * *WinningPlayerId - compare
 * *PlayerHandsDto - compare
 */
function ValidateGameStatusDtoGameEnd($dto, $expected) {
    global $dateTimeFormat;
    echo "Validating Game END <br />";
    $countFailed = 0;
    if (!Equals('Game Instance Id', $dto->gameInstanceId, $expected->gameInstanceId)) {
        $countFailed++;
    }
    if (!Equals('Game Session', $dto->gameSessionId, $expected->gameSessionId)) {
        $countFailed++;
    }
    if (!IsNull('Casino Table Id', $dto->casinoTableId)) {
        $countFailed++;
    }

    if (!Equals('GameStatus', $dto->gameStatus, $expected->gameStatus)) {
        $countFailed++;
    }
    $dtoDateTime = DateTime::createFromFormat($dateTimeFormat, $dto->statusDateTime);
    $expDateTime = DateTime::createFromFormat($dateTimeFormat, $expected->statusDateTime);
    if (!Equals('StatusDateTime', $dtoDateTime, $expDateTime)) {
        $countFailed++;
    }
    if (!Equals('Dealer Id', $dto->dealerPlayerId, $expected->dealerPlayerId)) {
        $countFailed++;
    }
    // number of player status may be different because new user may have joined
    $activePlayers = array();
    foreach ($expected->playerStatusDtos as $exp) {
        if ($exp->status != PlayerStatusType::WAITING) {
            
            array_push($activePlayers, $exp); 
        }
    }
    if (!Equals('# Player Statuses', count($dto->playerStatusDtos), count($activePlayers))) {
        $countFailed++;
    } else {
        $i = 0;
        foreach ($dto->playerStatusDtos as $actual) {
            $exp = $activePlayers[$i];
            if (!Equals("Player $i Id", $actual->playerId, $exp->playerId)) {
                $countFailed++;
            }
            if (!Equals("Player $i Seat", $actual->seatNumber, $exp->seatNumber)) {
                $countFailed++;
            }
            if (!Equals("Player $i Status", $actual->status, $exp->status)) {
                $countFailed++;
            }
            if (!Equals("Player $i Stake", $actual->currentStake, $exp->currentStake)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
                $countFailed++;
            }
            if (!Equals("Player $i Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
                $countFailed++;
            }
            $i++;
        }
    }
    if (!Equals('Pot Size', $dto->currentPotSize, $expected->currentPotSize)) {
        $countFailed++;
    }
    if (!IsNull('Updated Player', $dto->turnPlayerStatusDto)) {
        $countFailed++;
    } /* else {
      $actual = $dto->turnPlayerStatusDto;
      $exp = $expected->turnPlayerStatusDto;
      if (!Equals("Updated Player Id", $actual->playerId, $exp->playerId)) {
      $countFailed++;
      }
      if (!IsNull("Updated Player Name", $actual->playerName)) {
      $countFailed++;
      }
      if (!Equals("Updated Player Seat", $actual->seatNumber, $exp->seatNumber)) {
      $countFailed++;
      }
      if (!Equals("Updated Player Status", $actual->status, $exp->status)) {
      $countFailed++;
      }
      if (!Equals("Updated Player Stake", $actual->currentStake, $exp->currentStake)) {
      $countFailed++;
      }
      if (!Equals("Updated Player Last Amt", $actual->lastPlayAmount, $exp->lastPlayAmount)) {
      $countFailed++;
      }
      if (!Equals("Updated Player Last Play", $actual->lastPlayInstanceNumber, $exp->lastPlayInstanceNumber)) {
      $countFailed++;
      }
      } */
    if (!IsNull('Next Move', $dto->nextMoveDto)) {
        $countFailed++;
    }
    if (!IsNull('Community Cards', $dto->communityCards)) {
        $countFailed++;
    }
    if (!IsNull('New Cards', $dto->newCommunityCards)) {
        $countFailed++;
    }
    if (!IsNull('Waiting List', $dto->waitingListSize)) {
        $countFailed++;
    }
    if (!IsNull('User Hand', $dto->userPlayerHandDto)) {
        $countFailed++;
    }
    if (!IsNull('User Seat', $dto->userSeatNumber)) {
        $countFailed++;
    }
    if (!IsNotNull('Winner Id', $dto->winningPlayerId)) {
        $countFailed++;
    }
    if (!IsNotNull('All Hands', $dto->playerHandsDto)) {
        $countFailed++;
    } else {
        $i = 0;
        foreach ($dto->playerHandsDto as $actual) {
            echo "Player $i Id:  $actual->playerId <br />";
            echo "Player $i Card 1: $actual->pokerCard1Code <br />";
            echo "Player $i Card 2: $actual->pokerCard2Code <br />";
            echo "Player $i Hand: $actual->pokerHandType <br />";
            /*
              $exp=$dto->playerHandsDto[$i];
              if (!Equals("Player $i Id", $actual->playerId, $exp->playerId));
              if (!Equals("Player $i Card 1", $actual->spokerCard1Code, $exp->pokerCard1Code));
              if (!Equals("Player $i Card 2", $actual->pokerCard2Code, $exp->pokerCard2Code));
              if (!Equals("Player $i Hand", $actual->pokerHandType, $exp->pokerHandType));
              $i++;
             * 
             */
        }
    }
    if ($countFailed === 0) {
        echo "*** PASSED ALL TESTS <br />";
    }
}

?>
