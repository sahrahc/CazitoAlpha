<?php

// prevent time out, this script runs for a long time
ini_set('max_execution_time', 600);
echo "exec time " . ini_get('max_execution_time') . "<br/>";

echo '********************************************************** <br />';
echo '*' . __FILE__ . "<br />";
echo '* Smoke Test: 2 rounds of practice play <br />';
echo '* TODO: Fix comparison - player name not returned on practice game restart (only live)<br />';
echo '* TODO: Fix comparison - player status skipped vs. time out<br />';
echo '* TODO: Not comparing UserPlayerId yet';
echo '********************************************************** <br />';

// Setup ////////////////////////////////////////
include_once('../PokerPlayerService.php');
include_once('../CoordinatorService.php');
require_once('ValidateGameStatus.php');

global $buyInMultiplier;
global $defaultTableMin;
$playerNames = array('Test0');
$playerIds = null;
$q = null;
$playerStatusDtos = null;
$expectedDto = null; // will keep updating as the game progresses 
$tableSize = $defaultTableMin;
$gameSessionId = null;
$gameInstanceId = null;
// expected game parameters
$buyIn = $tableSize * $buyInMultiplier;
$blind1Size = $tableSize / 2;
$blind2Size = $blind1Size * 2;

session_start();

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);

echo '****************************************************** <br />';
echo 'Round 1: Player logs in and starts practice session <br />';

$numberPlayers = 4;

$playerIds[0] = testPlayerEntry($playerNames[0]);
$_SESSION['param_playerId'] = $playerIds[0];
testStartPractice($buyIn);
$q[0] = QueueManager::GetPlayerQueue($playerIds[0], $qCh);
$playerStatusDtos[0] = clone $expectedDto->playerStatusDtos[0];
$playerStatusDtos[1] = clone $expectedDto->playerStatusDtos[1];
$playerStatusDtos[2] = clone $expectedDto->playerStatusDtos[2];
$playerStatusDtos[3] = clone $expectedDto->playerStatusDtos[3];
$expectedDto->playerStatusDtos = null;
$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;

echo '****************************************************** <br />';
echo 'PHASE 2: <br/><br/>';
echo 'Game 1 Round 1 of practice poker play <br />';

$lastBet = $tableSize;
echo ' Play 1 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, null, 1, true);
echo ' Play 2 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2, false);
echo ' Play 3 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, null, 3, true);
echo ' Play 4 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, null, 4, true);

echo '****************************************************** <br />';
echo 'Game 1 Round 2 <br />';
echo ' Play 5 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, NULL, 5, true);
echo ' Play 6 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 6, false);
echo ' Play 7 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 7, true);
echo ' Play 8 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, NULL, 8, true);

echo '****************************************************** <br />';
echo 'Game 1 Round 3 of poker player, 4th player time outs <br />';

echo ' Play 9 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, null, 9, true);

echo ' Play 10 ********************************************************/<br/>';
echo '******* TIME OUT ************************************************/<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testPracticeMove(0, $playerIds[1], PlayerStatusType::SKIPPED, NULL, 10, false);
echo ' Play 11 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 11, true);
echo ' Play 12 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, NULL, 12, true);

echo '****************************************************** <br />';
echo 'Game 1 Round 4 of poker player, final round of play two players <br />';

echo ' Play 13 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, NULL, 13, true);
echo ' Play 14 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 14, false);
echo ' Play 15 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 15, true);
echo ' Play 16 ********************************************************/<br/>';
testPracticeMove(2, null, null, null, 16, true);

echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo 'PHASE 2: Start second practice game <br />';
/* shift players to match turns */
array_push($playerIds, $playerIds[0]);
array_shift($playerIds);
array_push($playerStatusDtos, $playerStatusDtos[0]);
array_shift($playerStatusDtos);
array_push($q, $q[0]);
array_shift($q);

// initialize
$expectedDto->status = GameStatus::STARTED;
$expectedDto->playerStatusDtos[0] = clone $playerStatusDtos[0];
$expectedDto->playerStatusDtos[1] = clone $playerStatusDtos[1];
$expectedDto->playerStatusDtos[2] = clone $playerStatusDtos[2];
$expectedDto->playerStatusDtos[3] = clone $playerStatusDtos[3];
// 1 is dealer, 2 and 3 are the blinds, 1 is the first play, 0 fold
$expectedDto->dealerPlayerId = $playerIds[0];
UpdatePlayerStatus(1, PlayerStatusType::BLIND_BET, $blind1Size, 0);
UpdatePlayerStatus(2, PlayerStatusType::BLIND_BET, $blind2Size, 0);
UpdatePlayerStatus(3, PlayerStatusType::WAITING, 0, 0);
UpdatePlayerStatus(0, PlayerStatusType::WAITING, 0, 0);
$expectedDto->firstPlayerId = $playerIds[0];
$expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[0], $blind2Size, 0, $blind2Size * 2);
$expectedDto->casinoTableId = null;
$expectedDto->userSeatNumber = null;
$expectedDto->currentPotSize = $blind1Size + $blind2Size;

$_SESSION['param_playerId'] = $playerIds[3];
include('Feature_StartPracticeGame.php');
ConsumeTableQueue();

// game start initializes everyone's status
$playerStatusDtos[0] = clone $expectedDto->playerStatusDtos[0];
$playerStatusDtos[1] = clone $expectedDto->playerStatusDtos[1];
$playerStatusDtos[2] = clone $expectedDto->playerStatusDtos[2];
$playerStatusDtos[3] = clone $expectedDto->playerStatusDtos[3];

testPracticeGameStart(3);

echo '****************************************************** <br />';
$expectedDto->playerStatusDtos = null;
$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;
// playNumber skips if user folded

echo '****************************************************** <br />';
echo 'Game 2 Round 1 of poker player <br />';

$lastBet = $tableSize;
echo ' Play 1 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1, false);
echo ' Play 2 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 2, true);
echo ' Play 3 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 3, true);
echo ' Play 4 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4, true);

echo '****************************************************** <br />';
echo 'Game 2 Round 2 of poker player, player 0 leaves after round <br />';
echo ' Play 5 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 5, false);
echo ' Play 6 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CHECKED, NULL, 6, true);
echo ' Play 7 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 7, true);
echo ' Play 8 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 8, true);

echo '****************************************************** <br />';
echo 'Game 2 Round 3 of poker player, 2nd player times out <br />';

echo ' Play 9 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 9, false);
echo ' Play 10 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 10, true);
echo ' Play 11 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 11, true);
echo ' Play 12 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CHECKED, NULL, 12, true);

echo '****************************************************** <br />';
echo 'Game 2 Round 4 of poker player, final round of play two players <br />';

echo ' Play 13 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 13, false);
echo ' Play 14 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 14, true);
echo ' Play 15 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15, true);
echo ' Play 16 ********************************************************/<br/>';
testPracticeMove(2, null, PokerActionType::CHECKED, $lastBet, 16, true);

echo '****************************************************** <br />';
/////////////////////////////////////////////////
// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

connectToStateDB();

echo 'GameSessionId : ' . $gameSessionId . '<br />';
cleanUpGameSessionById($gameSessionId);
    CleanUpPlayerById($playerIds[0]);
// no need? clean up if something went wrong?
cleanUpOrphanCasino();
CleanUpAbandonedPlays();

session_destroy();

?>
