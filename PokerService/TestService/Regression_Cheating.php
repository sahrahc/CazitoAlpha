<?php

/**** Game 1 cheating (4 players [1, 2, 3, 4]):
 * - player 0 loads sleeve before game
 * - player 0 heart marker
 * - player 3 diamond marker
 * - player 2 club marker
 * - player 1 ace pusher
 * - player 0 replaces first hand with first card from sleeves
 * - player 1 uses river shuffler (look and replace)
 * - player 3 starts social spotter
 **** Game 2 cheating (3 players + 1 join, player # 2 leaves [2, 4, 1]):
 * player 2 (id 3) leaves
 * - player 1 validate social spotter works (pId 4 is now #1)
 * new player joins
 * - player 0 poker peeker on player 3
 * - player 1 start oil marker TODO: make it new player
 * - player 2 table tuck two cards
 * - player 3 (new inactive player) keep face cards (inactive)
 * - player 2 use anti-oil (on player 1)
 * cards seen social spotter:
 **** Game 3 cheating (4 players [4, 1, 2, 5]):
 * - player 0 validate social spotter works
 * - player 2 verify face cards
 * - player 1 verify oil marker
 * - player 2 use second of table tucker cards
 * cards seen:
 * Game 1 Cards
 * ============
 * sleeve for player 0: Jh, Jc (23, 49)
 * player 0: 2h, 3d (14, 28)
 * player 1: 8h, 9d - ace pusher changes first card
 * player 2: Qc, Td (50, 35)
 * player 3: 8c, 6s (46, 5)
 * community cards + 1: 5d, Jd, Ks, 2c, 4h, Kd (30, 36, 12, 40, 16, 38)
 * cards seen (ace pusher, sleeve, river shuffler)
 * Game 2 Cards
 * ============
 * sleeve for player 0: Jh, Jc (23, 49)
 * player 0: 7h, 9s
 * player 1: Ac, 9d
 * player 2: 3c, Kh
 * community cards: Qs, 2d, 4d, 6h, Ts
 * cards seen
 * Game 3 Cards
 * ============
  * sleeve for player 0: Jh
  * player 0: Ks, 6d
  * player 1: 2s, Qc
  * player 2: 8d, Th
  * player 3: Ac, 8c
  * community cards: 5c, Jh, Ad, 4d, 6h
 * cards seen (face card, table tucker)
*/

// prevent time out, this script runs for a long time
ini_set('max_execution_time', 600);
echo "exec time " . ini_get('max_execution_time') . "<br/>";

echo '********************************************************** <br />';
echo '*' . __FILE__ . "<br />";
echo '* Cheating Test: 4 players join new table and play together.<br/>' .
 '* need to rig up shuffle deck. <br />' .
 '* Every cheating item is used: <br/>';
'* TODO: test expiration and lock end date times<br/>';

// Setup ////////////////////////////////////////
include_once('../PokerPlayerService.php');
include_once('../CoordinatorService.php');
require_once('TestComponents.php');
require_once('TestData.php');
require_once('TestCheating.php');
require_once('ValidateGameStatus.php');

// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

/* * ************* configuration values ******* */
global $buyInMultiplier;
$printCheatingAPI = true;
$printAPI = true;

/* * ************* test data ****************** */
$playerNames = array('Test1', 'Test2', 'Test3', 'Test4', 'newUser5', 'newUser6');
$tableSize = 1000;
$tableName = 'CheatTest';
$tableCode = 'C';
$numberPlayers = 4;
$activePlayers = 4;

$sleeves[0] = array('Jh', 'Jc');
$indexCards[0] = array('2h', '3d', // player 0
	'8h', '9d', // player 1
	'Qc', 'Td', // player 2
	'8c', '6s', // player 3
	'5d', 'Jd', 'Ks', '2c', '4h', // community cards
	'Kd', '2s', '3s', '4s', '5s', '7s', '8s', '9s', 'Ts', 'Js', 'Qs', 'As',
	'3h', '5h', '6h', '7h', '9h', 'Th', 'Jh', 'Qh', 'Kh', 'Ah',
	'2d', '4d', '6d', '7d', '8d', 'Qd', 'Ad',
	'3c', '4c', '5c', '6c', '7c', '9c', 'Tc', 'Jc', 'Kc', 'Ac');
$tableGroove = array('Qh', 'Ks', 'As');
$indexCards[1] = array('7h', '9s', // player 0
	'Ac', '3c', // player 1
	'9d', 'Kh', // player 2
	'Qs', '2d', '4d', '6h', 'Ts', // community cards
	'2c', '4c', '5c', '6c', '7c', '8c', '9c', 'Tc', 'Jc', 'Qc', 'Kc', 
	'3d', '5d', '6d', '7d', '8d', 'Td', 'Jd', 'Qd', 'Kd', 'Ad',
	'2h', '3h', '4h', '5h', '8h', '9h', 'Th', 'Jh', 'Qh', 'Ah',
	'2s', '3s', '4s', '5s', '6s', '7s', '8s', 'Js',  'Ks', 'As');
$indexCards[2] = array('2s', '6d', // player 0
	'8d', 'Qc', // player 1
	'Ks', 'Th', // player 2
	'Ac', '8c', // player 3
	'5c', 'Jh', 'Ad', '4d', '6h', // community cards
    '2c', '3c', '4c', '6c', '7c', '9c', 'Tc', 'Jc', 'Kc', 
    '2d', '3d', '5d', '7d', '9d', 'Td', 'Jd', 'Qd', 'Kd', 
    '2h', '3h', '4h', '5h', '7h', '8h', '9h', 'Qh', 'Kh', 'Ah',
    '3s', '4s', '5s', '6s', '7s', '8s', '9s', 'Ts', 'Js', 'Qs', 'As');
/* * ************* calculated test data ******* */
/* global constants */
$buyIn = $tableSize * $buyInMultiplier;
$blind1Size = $tableSize / 2;
$blind2Size = $blind1Size * 2;
/* * ************* test globals *************** */
// global variables will keep updating throughout the test
$gameSessionId = null;
$gameInstanceId = null;
$playerIds = null;
$q = null;
$expectedDto = null; // will keep updating as the game progresses 
$casinoTableId = null;

/* * ************************ initialize *********************** */
// already set by PokerPlayerService.php
// session_start();

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);

echo '****************************************************** <br />';
echo '4 players login and join table (SAME AS SMOKE TEST) <br />';

scriptLoginJoinEarly($numberPlayers);

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - player 0  loads sleeve before game starts <br />';
$loadedCards = execLoadSleeve($playerIds[0], $sleeves[0]);
validateCardValues($playerIds[0], $loadedCards, $sleeves[0]);
echo '====================================================== <br />';

echo '****************************************************** <br />';
echo 'GAME 1: 4 players login and join';

/* initializes the expectedDto with game start values */
InitGameStart(0, 4);

queueStartLiveGame($gameSessionId, $playerIds[0], $indexCards[0]);
ConsumeTableQueue();

// initialize to expected values if clean game
InitPlayerHands($indexCards[0], true);

testGameStart(0, true);
testGameStart(1);
testGameStart(2);
testGameStart(3);

/* update expected player data after starting game */
$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;

echo '====================================================== <br />';
echo 'GAME 1 - validate cards dealt as specified <br/>';
// set values if cheating
validateGameCards($indexCards[0]);
echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test heart marker <br/>';
testCheatSuitMarker(0, ItemType::HEART_MARKER);
echo '====================================================== <br />';

echo ' GAME 1 Play 1 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' GAME 1 Play 2 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2);

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test diamond marker <br/>';
testCheatSuitMarker(3, ItemType::DIAMOND_MARKER);
echo '====================================================== <br />';

echo ' GAME 1 Play 3 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 3);
echo ' GAME 1 Play 4 ********************************************************<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4);

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test club marker <br/>';
testCheatSuitMarker(2, ItemType::CLUB_MARKER);
echo '====================================================== <br />';

echo ' GAME 1 Play 5 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 5);

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test ace pusher <br/>';
// arguments: playerNumber, cardNumber, cardIndex
testCheatAcePusherUpdateExpected(1, 1, 0);
echo '====================================================== <br />';

echo ' GAME 1 Play 6 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::FOLDED, NULL, 6);
echo ' GAME 1 Play 7 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 7);

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test use sleeve <br/>';
// arguments: playerNumber, pCardNum, hCardNum, sleeves, indexNum
testCheatUseSleeveUpdateExpected(0, 1, 0,  $sleeves[0], 0);
echo '====================================================== <br />';

echo ' GAME 1 Play 8 ********************************************************<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 8);

echo '====================================================== <br />';
echo ' GAME 1 CHEATING - test river look <br/>';
testCheatRiverLook(1);
echo '====================================================== <br />';

echo ' GAME 1 Play 9 ********************************************************<br/>';
testMove(3, $playerIds[1], PokerActionType::CALLED, $lastBet, 9);

echo ' GAME 1 Play 10 FOLDED ********************************************************<br/>';

echo '====================================================== <br />';
echo 'GAME 1 CHEATING - test river use <br/>';
// arguments: playerNumber, cardIndex
testCheatRiverUse(1, 0);
echo '====================================================== <br />';

echo ' GAME 1 Play 11 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 11);

// start card marking, check next round game start cards are revealed
echo '====================================================== <br />';
echo 'GAME 1 CHEATING - start social spotter <br/>';
testCheatStartSocialSpotter(3); // id 4
echo '====================================================== <br />';

echo ' GAME 1 Play 12 ********************************************************<br/>';
echo '******* TIME OUT ************************************************<br/>';
sleep(29);
ProcessExpiredPokerMoves();

testMove(2, $playerIds[3], PlayerStatusType::SKIPPED, NULL, 12);

echo ' GAME 1 Play 13 ********************************************************<br/>';
testMove(3, $playerIds[1], PokerActionType::CHECKED, NULL, 13);
//testMove(0, PokerActionType::FOLDED, NULL, 14);
echo ' GAME 1 Play 15 ********************************************************<br/>';
echo ' ************(Play 14 skipped because user folded****************<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15);
echo ' GAME 1 Play 16 ********************************************************<br/>';
testMove(2, null, PokerActionType::CALLED, $lastBet, 16);

echo '****************************************************** <br />';
echo 'GAME 2: Cheating with social spotter and poker peeker <br />';
echo '        Three players start, one leaves in middle and two join <br/><br/>';

echo '****************************************************** <br />';
echo 'GAME 2: Test User Leaving between games';
// remove info since leaving in between games (assuming timed out above from leaving)
scriptPlayerLeaves(2);
RemovePlayer(2);

UpdateTurnsNextGame();
//UpdateTurnsNextGame();

echo '****************************************************** <br />';
echo 'GAME 2: Start game';
// initialize test data
InitGameStart(0, 3); 

queueStartLiveGame($gameSessionId, $playerIds[2], $indexCards[1]);
ConsumeTableQueue();

// initialize to expected values if clean game
InitPlayerHands($indexCards[1]);

testGameStart(2, true);
testGameStart(0);
testGameStart(1);

$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;

echo '====================================================== <br />';
echo 'GAME 2 - validate cards dealt as specified <br/>';
// set values if cheating
validateGameCards($indexCards[1]);
echo '====================================================== <br />';
echo 'GAME 2 CHEATING - test social spotter worked <br/>';
// playerId 4 is now in position 1 because reset turns
testCheatSocialSpotterWorks(1, $previousGameCards, $indexCards[1]);
echo '====================================================== <br />';
echo ' GAME 2 Play 1 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' GAME 2 Play 2 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 2);
echo ' GAME 2 Play 3 ********************************************************<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 3);

echo ' 2 New User joins ********************************************************<br/>';
// takes first seat, empty because user left. seat number same as player number if no gaps
testJoinTableMiddle($playerNames[4], 'RoundEnd');
/* not testing seat offer
// placed on waiting list, need to have position reset when seat assigned
testJoinTableMiddle($playerNames[5], 'RoundEnd');
*/
echo ' GAME 2 Play 4 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CHECKED, NULL, 4);
echo ' GAME 2 Play 5 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 5);
echo ' GAME 2 Play 6 ********************************************************<br/>';
testMove(2, $playerIds[0], PokerActionType::CALLED, $lastBet, 6);

echo '====================================================== <br />';
echo 'GAME 2 CHEATING - test poker peeker <br/>';
// args: playerNumber, otherPlayerNumber, otherPlayerCardNumber
testPokerPeeker(0, 1, 2);
echo '====================================================== <br />';

/*
echo ' GAME 2 User leaves ********************************************************<br/>';
scriptPlayerLeaves(0); */
/* verify seat offer */
//		verifyQMessage($playerIds[4], $q[4], EventType::SeatOffer);

echo ' GAME 2 Play 7 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 7);
echo ' GAME 2 Play 8 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 8);

echo '====================================================== <br />';
echo 'GAME 2 CHEATING - request oil marker <br/>';
testCheatStartOilMarker(0); // Id #2
testCheatStartOilMarker(2); // Id #1
echo '====================================================== <br />';
sleep(20);
ProcessExpiredPokerMoves();

echo ' GAME 2 Play 9 ********************************************************<br/>';
testMove(2, $playerIds[0], PlayerStatusType::SKIPPED, NULL, 9);

echo '====================================================== <br />';
echo 'GAME 2 CHEATING - tuck card under table groove <br/>';
testCheatTableTuckerLoad(2, $tableGroove); // Id #1
echo '====================================================== <br />';
echo 'GAME 2 CHEATING - request face card <br/>';
testCheatFaceCard(3); // id 5
echo '====================================================== <br />';

echo ' GAME 2 Play 10 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 10);
echo ' GAME 2 Play 11 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 11);

echo '====================================================== <br />';
echo 'GAME 2 CHEATING - anti oil marker <br/>';
// arguments: playerNumber, otherPlayerNumber, $otherPlayerResponseFlag
testCheatAntiOilMarker(1, 2, false); // from id 4 to id 1
echo '====================================================== <br />';

echo ' GAME 2 Play 12 ********************************************************<br/>';
testMove(2, null, PokerActionType::CALLED, $lastBet, 12);

echo '****************************************************** <br />';
echo 'GAME 3: Cheating with face cards, oil marker and anti <br />';
echo '        oil marker, and tuck out <br />';
echo '        Back to four players <br />';

// remove info since leaving in between games
// arguments: playerNumber, playerId, seatNumber, $playerName, buyIn, position (
MakePlayerActive(3, $playerIds[3], 2, $playerNames[4], $buyIn, 1);
// new turn order: 5, 4, 1, 2
UpdateTurnsNextGame();
//UpdateTurnsNextGame();

// initialized
InitGameStart(0, 4);

queueStartLiveGame($gameSessionId, $playerIds[0], $indexCards[2]);
ConsumeTableQueue();

// initialize to expected values if clean game
InitPlayerHands($indexCards[2]);

testGameStart(1, true);
testGameStart(2);
testGameStart(3);

echo '====================================================== <br />';
echo 'GAME 3 CHEATING - test keep face card worked <br/>';
// arguments: playerNumber, cardIndex
UpdatePlayerHandsFaceCard(0, 2); // id = 5
testCheatFaceCardWorks(0, 2);

testGameStart(0);

$expectedDto->userPlayerHandDto = null;
$expectedDto->gameStatus = GameStatus::IN_PROGRESS;
$expectedDto->waitingListSize = null;

echo '========UpdatePlayerH============================================== <br />';
echo 'GAME 3 CHEATING- validate cards dealt as specified <br/>';
validateGameCards($indexCards[2]);
echo '====================================================== <br />';
echo 'GAME 3 CHEATING - test anti oil marker worked <br/>';
// player numbers unchanged because new player
// arguments: playernumber, otherplayernumber
testCheatAntiOilMarkerWorks(1, 2); // from 4 on 1
echo '====================================================== <br />';
echo 'GAME 3 CHEATING - test social spotter worked <br/>';
// playerId 4 is stil1 1
testCheatSocialSpotterWorks(1, $previousGameCards, $indexCards[2]);
echo '====================================================== <br />';
echo 'GAME 3 CHEATING - test snake oil marker worked <br/>';
// playerId 2 is now in position 3
testCheatOilMarkerWorks(3, $indexCards[2]);
echo '====================================================== <br />';

echo ' GAME 3 Play 1 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 1);
echo ' GAME 3 Play 2 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 2);

echo '====================================================== <br />';
echo 'GAME 3 CHEATING - use card tucked under table groove <br/>';
// player id 1 is now in position 2
testCheatTableTuckerUse(2, 2, 0); // by id 1
echo '====================================================== <br />';

echo ' GAME 3 Play 3 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::RAISED, $lastBet*=2, 3);

echo ' GAME 3 Play 4 ********************************************************<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 4);
echo ' GAME 3 Play 5 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::CHECKED, NULL, 5);
echo ' GAME 3 Play 6 ********************************************************<br/>';
testMove(0, $playerIds[1], PokerActionType::CALLED, $lastBet, 6);
echo ' GAME 3 Play 7 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 7);

echo '====================================================== <br />';
echo 'GAME 3 CHEATING - anti oil marker <br/>';
// arguments: playerNumber, otherPlayerNumber
testCheatAntiOilMarker(0, 3, true); // on id 2 from id 5
echo '======================================================o <br />';

echo ' GAME 3 Play 8 ********************************************************<br/>';
testMove(2, $playerIds[3], PokerActionType::RAISED, $lastBet*=2, 8);

echo ' GAME 3 Play 9 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::CALLED, $lastBet, 9);
echo ' GAME 3 Play 10 *******************************************************<br/>';
testMove(0, $playerIds[1], PlayerStatusType::CHECKED, NULL, 10);
echo ' GAME 3 Play 11 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CHECKED, NULL, 11);
echo ' GAME 3 Play 12 ********************************************************<br/>';
testMove(2, $playerIds[3], PokerActionType::CALLED, $lastBet, 12);

echo ' GAME 3 Play 13 ********************************************************<br/>';
testMove(3, $playerIds[0], PokerActionType::RAISED, $lastBet*=2, 13);
echo ' GAME 3 Play 14 *******************************************************<br/>';
testMove(0, $playerIds[1], PlayerStatusType::CALLED, $lastBet, 14);
echo ' GAME 3 Play 15 ********************************************************<br/>';
testMove(1, $playerIds[2], PokerActionType::CALLED, $lastBet, 15);
echo ' GAME 3 Play 16 ********************************************************<br/>';
testMove(2, NULL, PokerActionType::CHECKED, NULL, 16);

echo '****************************************************** <br />';
/////////////////////////////////////////////////
// cleanup

connectToStateDB();

echo 'GameSessionId : ' . $gameSessionId . '<br />';
/*cleanUpGameSessionById($gameSessionId);
for ($i = 0; $i < count($playerIds); $i++) {
	cleanUpPlayerById($playerIds[$i]);
}
// no need? clean up if something went wrong?
cleanUpOrphanCasino();
CleanUpAbandonedPlays();
*/
session_destroy();

// TODO: verify expirations
?>
