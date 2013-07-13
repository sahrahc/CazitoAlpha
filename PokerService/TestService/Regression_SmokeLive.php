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
echo '* TODO: Not comparing UserPlayerId yet';
echo '********************************************************** <br />';

// Setup ////////////////////////////////////////
include_once('../PokerPlayerService.php');
include_once('../CoordinatorService.php');
require_once('ValidateGameStatus.php');

// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

//global $defaultTableMin;
global $buyInMultiplier;
//global $playExpiration;
// Constants
$playerNames = array('Test1', 'Test2', 'Test3', 'Test4', 'NewUser5');
$playerIds = null;
$q = null;
$playerStatusDtos = null;
$expectedDto = null; // will keep updating as the game progresses 
$tableSize = 1000;
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
echo 'PHASE 1: 4 players login and join table <br />';

$_SESSION['param_casinoTableId'] = null;
$_SESSION['param_tableName'] = 'SmokeTest';
$_SESSION['param_tableSize'] = $tableSize;
$_SESSION['param_tableCode'] = 'X';
// parameter is seat number
// after each user joining test, verify message to already joined players
$i = 0;

for ($i=0; $i<4; $i++) {
    $firstTest = false;
    if ($i==0) { $firstTest = true;}
    $playerIds[$i] = testPlayerEntry($playerNames[$i]);
    testJoinTable($i, $playerIds[$i], $playerNames[$i], $buyIn, $firstTest);
    $q[$i] = QueueManager::GetPlayerQueue($playerIds[$i], $qCh);
    // verify previously joined users received message
    for ($j=0; $j<$i;$j++) {
        verifyQMessage($playerIds[$j], $q[$j], EventType::SeatTaken);
    }
}

$numberPlayers = 4;

echo '****************************************************** <br />';
echo 'PHASE 2: game start and blind bet verification <br />';
echo 'First player starts game instance';
$_SESSION['param_playerId'] = $playerIds[0];

// 0 is dealer, 1 and 2 are the blinds, 3 is the first play
$expectedDto->dealerPlayerId = $playerIds[0];
UpdatePlayerStatus(1, PlayerStatusType::BLIND_BET, $blind1Size, 0);
UpdatePlayerStatus(2, PlayerStatusType::BLIND_BET, $blind2Size, 0);
UpdatePlayerStatus(3, PlayerStatusType::WAITING, 0, 0);
UpdatePlayerStatus(0, PlayerStatusType::WAITING, 0, 0);
$expectedDto->firstPlayerId = $playerIds[3];
$expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[3], $blind2Size, 0, $blind2Size * 2);
$expectedDto->casinoTableId = null;
$expectedDto->userSeatNumber = null;
$expectedDto->currentPotSize = $blind1Size + $blind2Size;

include('Feature_StartLiveGame.php');
ConsumeTableQueue();

/* player status order changes with turn number
 $playerIds = ;
$playerStatusDtos = array($expectedDto->playerStatusDtos[1],
        $expectedDto->playerStatusDtos[2],
    $expectedDto->playerStatusDtos[3],
    $expectedDto->playerStatusDtos[0]);
$expectedDto->playerStatusDtos = $playerStatusDtos;
*/
testGameStart(0, true);
testGameStart(1);
testGameStart(2);
testGameStart(3);

echo '****************************************************** <br />';
$playerStatusDtos[0] = clone $expectedDto->playerStatusDtos[0];
$playerStatusDtos[1] = clone $expectedDto->playerStatusDtos[1];
$playerStatusDtos[2] = clone $expectedDto->playerStatusDtos[2];
$playerStatusDtos[3] = clone $expectedDto->playerStatusDtos[3];
$expectedDto->playerStatusDtos = null;
$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;
// playNumber skips if user folded

echo '****************************************************** <br />';
echo 'PHASE 2: <br/><br/>';
echo 'Game 1 Round 1 of poker player starting with first player <br />';

$lastBet = $tableSize;
echo ' Play 1 ********************************************************/<br/>';
testMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' Play 2 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2);
echo ' Play 3 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 3);
echo ' Play 4 ********************************************************/<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4);

echo '****************************************************** <br />';
echo 'Game 1 Round 2 of poker player, player 2 folds <br />';
echo ' Play 5 ********************************************************/<br/>';
testMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 5);
echo ' Play 6 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::FOLDED, NULL, 6);
echo ' Play 7 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 7);
echo ' Play 8 ********************************************************/<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 8);

echo '****************************************************** <br />';
echo 'PHASE 4 Turn: round 3 of poker player, 4th player time outs <br />';

echo ' Play 9 ********************************************************/<br/>';
testMove(3, $playerIds[1], PokerActionType::CALLED, $lastBet, 9);
//testMove(0, PokerActionType::FOLDED, NULL, 10);
echo ' Play 11 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 11);

echo ' Play 12 ********************************************************/<br/>';
echo '******* TIME OUT ************************************************/<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testMove(2, $playerIds[3], PlayerStatusType::SKIPPED, NULL, 12);

echo '****************************************************** <br />';
echo 'PHASE 5: round 3 of poker player, final round of play two players <br />';

echo ' Play 13 ********************************************************/<br/>';
testMove(3, $playerIds[1], PokerActionType::CHECKED, NULL, 13);
//testMove(0, PokerActionType::FOLDED, NULL, 14);
echo ' Play 15 ********************************************************/<br/>';
echo ' ************(Play 14 skipped because user folded****************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15);
echo ' Play 16 ********************************************************/<br/>';
testMove(2, null, PokerActionType::CALLED, $lastBet, 16);

echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo '****************************************************** <br />';
echo 'PHASE 6: Testing turns assigned correctly on second round <br />';
echo '         and with players leaving, and new users getting  <br />';
echo '         game status correctly without joining <br />';
echo '         - Player 2 leaves and player 1 starts the game <br />';
echo '         - Player 0 leaves in the middle of the game. <br />';
echo '         - New player to join in the middle of game and should <br />';
echo '         be included in third and last game <br />';

// remove info since leaving in between games
$leavingPlayerId = $playerIds[2];
testPlayerLeaveTable(2, $leavingPlayerId, true);
testPlayerLogout($leavingPlayerId);
cleanUpPlayerById($leavingPlayerId);

$numberPlayers--;

// only 3 players, but still need to shift turns since first player didn't leave
/* player status order changes with turn number, update player arrays to match turn numbers */
/* put first player last */
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
// 1 is dealer, 2 and 3 are the blinds, 1 is the first play, 0 fold
$expectedDto->dealerPlayerId = $playerIds[0];
UpdatePlayerStatus(1, PlayerStatusType::BLIND_BET, $blind1Size, 0);
UpdatePlayerStatus(2, PlayerStatusType::BLIND_BET, $blind2Size, 0);
UpdatePlayerStatus(0, PlayerStatusType::WAITING, 0, 0);
$expectedDto->firstPlayerId = $playerIds[0];
$expectedDto->nextMoveDto = InitMove($gameInstanceId, $playerIds[0], $blind2Size, 0, $blind2Size * 2);
//$expectedDto->casinoTableId = null;
$expectedDto->userSeatNumber = null;
$expectedDto->currentPotSize = $blind1Size + $blind2Size;

$_SESSION['param_playerId'] = $playerIds[1];
include('Feature_StartLiveGame.php');
ConsumeTableQueue();

// game start initializes everyone's status
$playerStatusDtos[0] = clone $expectedDto->playerStatusDtos[0];
$playerStatusDtos[1] = clone $expectedDto->playerStatusDtos[1];
$playerStatusDtos[2] = clone $expectedDto->playerStatusDtos[2];

testGameStart(1, true);
testGameStart(0);
testGameStart(2);

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
testMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' Play 2 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 2);
echo ' Play 3 ********************************************************/<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 3);

echo ' New User joins ********************************************************/<br/>';
// takes first seat, empty because user left
$nextPlayerNumber = count($playerIds);
$playerIds[$nextPlayerNumber] = testPlayerEntry($playerNames[$nextPlayerNumber]);
testJoinTableMiddle($nextPlayerNumber, $playerIds[$nextPlayerNumber], $playerNames[$nextPlayerNumber], 2, $buyIn, 'RoundEnd');

echo '****************************************************** <br />';
echo 'Game 2 Round 2 of poker player, player 0 leaves after round <br />';
echo ' Play 4 ********************************************************/<br/>';
testMove(0, $playerIds[1], PokerActionType::CHECKED, NULL, 4);
echo ' Play 5 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 5);
echo ' Play 6 ********************************************************/<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 6);

echo ' User leaves ********************************************************/<br/>';
testPlayerLeaveTable(0, $playerIds[0], false);

echo '****************************************************** <br />';
echo 'Game 2 Round 3 of poker player, 2nd player times out <br />';

echo ' Play 8 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 8);
echo ' Play 9 ********************************************************/<br/>';
echo '******* TIME OUT ************************************************/<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testMove(2, $playerIds[1], PlayerStatusType::SKIPPED, NULL, 9);

echo '****************************************************** <br />';
echo 'Game 2 Round 4 of poker player, final round of play two players <br />';

//testMove(0, PokerActionType::FOLDED, NULL, 10);
echo ' Play 11 ********************************************************/<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 11);
echo ' Play 12 ********************************************************/<br/>';
testMove(2, null, PokerActionType::CALLED, $lastBet, 12);

echo '****************************************************** <br />';
/////////////////////////////////////////////////
// cleanup

connectToStateDB();

echo 'GameSessionId : ' . $gameSessionId . '<br />';
cleanUpGameSessionById($gameSessionId);
for ($i=0;$i<count($playerIds);$i++) {
    cleanUpPlayerById($playerIds[$i]);
}
// no need? clean up if something went wrong?
cleanUpOrphanCasino();
CleanUpAbandonedPlays();

session_destroy();
?>
