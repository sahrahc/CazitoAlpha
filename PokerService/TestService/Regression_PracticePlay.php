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
require_once('TestComponents.php');
require_once('TestData.php');
require_once('ValidateGameStatus.php');

/*************** configuration values ********/
global $buyInMultiplier;
global $defaultTableMin;
$printAPI = true;
/*************** test data *******************/
$playerNames = array('Test0');
$tableSize = $defaultTableMin; // bet size
$numberPlayers = 4;
$activePlayers = 4;
$indexCards[0] = array(
    '2c', '3c', '4c', '5c', '6c', '7c', '8c', '9c', 'Tc', 'Jc', 'Qc', 'Kc', 'Ac',
    '2d', '3d', '4d', '5d', '6d', '7d', '8d', '9d', 'Td', 'Jd', 'Qd', 'Kd', 'Ad',
    '2h', '3h', '4h', '5h', '6h', '7h', '8h', '9h', 'Th', 'Jh', 'Qh', 'Kh', 'Ah',
    '2s', '3s', '4s', '5s', '6s', '7s', '8s', '9s', 'Ts', 'Js', 'Qs', 'Ks', 'As');

/*************** calculated test data ********/
$buyIn = $tableSize * $buyInMultiplier;
$blind1Size = $tableSize / 2;
$blind2Size = $blind1Size * 2;
/*************** test globals ****************/
// will keep updating as throughout the test
$gameSessionId = null;
$gameInstanceId = null;
$playerIds = null;
$q = null;
//$playerStatusDtos = null;
$expectedDto = null;  

session_start();

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);

echo '****************************************************** <br />';
echo 'GAME 1: Player logs in and starts practice session <br />';

$playerIds[0] = testPlayerEntry($playerNames[0]);

scriptStartPractice($playerIds[0], $buyIn, $indexCards[0]);
// initialize test data
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
// initialize test data with actual values
$q[0] = QueueManager::GetPlayerQueue($playerIds[0], $qCh);

echo ' GAME 1 Play 1 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, null, 1, true);
echo ' GAME 1 Play 2 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2, false);
echo ' GAME 1 Play 3 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, null, 3, true);
echo ' GAME 1 Play 4 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, null, 4, true);

echo ' GAME 1 Play 5 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, NULL, 5, true);
echo ' GAME 1 Play 6 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 6, false);
echo ' GAME 1 Play 7 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 7, true);
echo ' GAME 1 Play 8 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, NULL, 8, true);

echo ' GAME 1 Play 9 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, null, 9, true);

echo ' GAME 1 Play 10 ********************************************************/<br/>';
echo '******* TIME OUT ************************************************/<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testPracticeMove(0, $playerIds[1], PlayerStatusType::SKIPPED, NULL, 10, false);
echo ' GAME 1 Play 11 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 11, true);
echo ' GAME 1 Play 12 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, NULL, 12, true);

echo ' GAME 1 Play 13 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], null, NULL, 13, true);
echo ' GAME 1 Play 14 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 14, false);
echo ' GAME 1 Play 15 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], null, NULL, 15, true);
echo ' GAME 1 Play 16 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], null, null, 16, true);

echo '****************************************************** <br />';
echo 'GAME 2: Start second practice game <br />';
/* shift players to match turns */
UpdateTurnsNextGame();

// initialize test data
InitGameStart(0, 4);

queueStartPracticeGame($gameSessionId, $playerIds[0]);
ConsumeTableQueue();

testPracticeGameStart(3);

echo '****************************************************** <br />';
$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;

echo ' GAME 2 Play 1 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1, false);
echo ' GAME 2 Play 2 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 2, true);
echo ' GAME 2 Play 3 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 3, true);
echo ' GAME 2 Play 4 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4, true);


echo ' GAME 2 Play 5 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 5, false);
echo ' GAME 2 Play 6 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CHECKED, NULL, 6, true);
echo ' GAME 2 Play 7 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 7, true);
echo ' GAME 2 Play 8 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 8, true);


echo ' GAME 2 Play 9 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 9, false);
echo ' GAME 2 Play 10 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 10, true);
echo ' GAME 2 Play 11 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 11, true);
echo ' GAME 2 Play 12 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CHECKED, NULL, 12, true);


echo ' GAME 2 Play 13 ********************************************************/<br/>';
testPracticeMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 13, false);
echo ' GAME 2 Play 14 ********************************************************/<br/>';
testPracticeMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 14, true);
echo ' GAME 2 Play 15 ********************************************************/<br/>';
testPracticeMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15, true);
echo ' GAME 2 Play 16 ********************************************************/<br/>';
testPracticeMove(2, $playerIds[3], PokerActionType::CHECKED, $lastBet, 16, true);

echo '****************************************************** <br />';
/////////////////////////////////////////////////
// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

connectToStateDB();
/*
echo 'GameSessionId : ' . $gameSessionId . '<br />';
cleanUpGameSessionById($gameSessionId);
CleanUpPlayerById($playerIds[0]);
// no need? clean up if something went wrong?
cleanUpOrphanCasino();
CleanUpAbandonedPlays();
*/
session_destroy();

?>
