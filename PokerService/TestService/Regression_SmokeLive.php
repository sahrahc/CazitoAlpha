<?php

// prevent time out, this script runs for a long time
ini_set('max_execution_time', 600);
echo "exec time " . ini_get('max_execution_time') . "<br/>";

echo '********************************************************** <br />';
echo '*' . __FILE__ . "<br />";
echo '* Smoke Test: 4 players join new table and play together <br />';
echo '*            Three rounds of play to test that turns are <br />';
echo '*            reset properly when users leave and are added. <br />';
echo '*            All poker moves and time out tested.<br />';
echo '* TODO: COMPARISON FOR UPDATED PLAYER STAKE NOT WORKING <br />';
echo '* TODO: Not comparing UserPlayerId yet<br />';
echo '********************************************************** <br />';

// Setup ////////////////////////////////////////
include_once('../PokerPlayerService.php');
include_once('../CoordinatorService.php');
require_once('TestComponents.php');
require_once('TestData.php');
require_once('ValidateGameStatus.php');

// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

/*************** configuration values ********/
//global $defaultTableMin;
global $buyInMultiplier;
$printAPI = true;
/*************** test data *******************/
$playerNames = array('Test1', 'Test2', 'Test3', 'Test4', 'NewUser5');
$tableSize = 1000;
$tableName = 'SmokeTest';
$tableCode = 'X';
$numberPlayers = 4; // number of players in table, including waiting players and left
$activePlayers = 4; // number of players actively in game, excluding players leaving in middle of game
$startingPlayers = 4; // mumber of players who started the game.
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
$expectedDto = null; // will keep updating as the game progresses 
$casinoTableId = null;

/* * ************************ initialize *********************** */
// already set by PokerPlayerService.php
// session_start();

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);

echo '****************************************************** <br />';
echo '4 players login and join table <br />';

scriptLoginJoinEarly($numberPlayers);

echo '****************************************************** <br />';
echo 'GAME 1: 4 players';

/* initializes the expectedDto with game start values */
InitGameStart(0, 4);

/* a player starts a game */
queueStartLiveGame($gameSessionId, $playerIds[0]);
ConsumeTableQueue();

testGameStart(0, true);
testGameStart(1);
testGameStart(2);
testGameStart(3);

echo '****************************************************** <br />';

$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;
// playNumber skips if user folded

echo ' GAME 1 Play 1 ********************************************************/<br/>';
testMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' GAME 1 Play 2 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2);
echo ' GAME 1 Play 3 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 3);
echo ' GAME 1 Play 4 ********************************************************/<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4);

echo ' GAME 1 Play 5 ********************************************************/<br/>';
testMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 5);
echo ' GAME 1 Play 6 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::FOLDED, NULL, 6);
echo ' GAME 1 Play 7 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 7);
echo ' GAME 1 Play 8 ********************************************************/<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 8);


echo ' GAME 1 Play 9 ********************************************************/<br/>';
testMove(3, $playerIds[1], PokerActionType::CALLED, $lastBet, 9);

echo ' GAME 1 Play 11 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 11);

echo ' GAME 1 Play 12 ********************************************************/<br/>';
echo '******* TIME OUT ************************************************/<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testMove(2, $playerIds[3], PlayerStatusType::SKIPPED, NULL, 12);


echo ' GAME 1 Play 13 ********************************************************/<br/>';
testMove(3, $playerIds[1], PokerActionType::CHECKED, NULL, 13);
//testMove(0, PokerActionType::FOLDED, NULL, 14);
echo ' GAME 1 Play 15 ********************************************************/<br/>';
echo ' ************(Play 14 skipped because user folded****************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15);
echo ' GAME 1 Play 16 ********************************************************/<br/>';
testMove(2, null, PokerActionType::CALLED, $lastBet, 16);

echo '****************************************************** <br />';
echo 'GAME 2: Testing turns assigned correctly on second round <br />';
echo '         and with players leaving, and new users getting  <br />';
echo '         game status correctly without joining <br />';
echo '         - Player 2 leaves and player 1 starts the game <br />';
echo '         - Player 0 leaves in the middle of the game. <br />';
echo '         - New player to join in the middle of game and should <br />';
echo '         be included in third and last game <br />';

// remove info since left (timed out actually) in between games
scriptPlayerLeaves(2);
RemovePlayer(2);

UpdateTurnsNextGame();

// initialize test data
InitGameStart(0, 3);

queueStartLiveGame($gameSessionId, $playerIds[1]);
ConsumeTableQueue();

testGameStart(2, true);
testGameStart(0);
testGameStart(1);

echo '****************************************************** <br />';
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;
// playNumber skips if user folded

echo ' GAME 2 Play 1 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' GAME 2 Play 2 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 2);
echo ' GAME 2 Play 3 ********************************************************/<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 3);

echo ' New User joins ********************************************************/<br/>';
// takes first seat, empty because user left
testJoinTableMiddle($playerNames[4], 'RoundEnd');

echo ' GAME 2 Play 4 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::CHECKED, NULL, 4);
echo ' GAME 2 Play 5 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 5);
echo ' GAME 2 Play 6 ********************************************************/<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 6);

echo ' User leaves ********************************************************/<br/>';
// player who has turn leaves
//testPlayerLeaveTable(0, $playerIds[0], false);
scriptPlayerLeaves(0, 7);
RemovePlayer(0);
// playerId's shifted because player removed

echo ' GAME 2 Play 8 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 8);
echo ' GAME 2 Play 9 ********************************************************/<br/>';
testMove(1, $playerIds[0], PlayerStatusType::CHECKED, NULL, 9);

echo ' GAME 2 Play 11 ********************************************************/<br/>';
testMove(0, $playerIds[0], PokerActionType::CALLED, $lastBet, 11);
echo ' GAME 2 Play 12 ********************************************************/<br/>';
testMove(1, null, PokerActionType::CALLED, $lastBet, 12);

echo '****************************************************** <br />';
/////////////////////////////////////////////////
// cleanup

//MakePlayerActive(2, $playerIds[2], 2, $playerNames[4], $buyIn, 0);

echo '****************************************************** <br />';
echo 'GAME 3: Testing game play correctly starts with seat taken by <br />';
echo '        replacement player, three players total. <br />';
echo '        Two users leaving in the middle of the game <br />';
echo '        - Remaining single user wins <br />';
echo '        - Upon one user coming back, same seat given, game resets to waiting <br />';
echo '        - Upon new user joining, seat replacement <br />';

/*
connectToStateDB();
echo 'GameSessionId : ' . $gameSessionId . '<br />';
cleanUpGameSessionById($gameSessionId);
for ($i=0;$i<count($playerIds);$i++) {
    cleanUpPlayerById($playerIds[$i]);
}

// no need? clean up if something went wrong?
cleanUpOrphanCasino();
CleanUpAbandonedPlays();
*/
session_destroy();
?>
