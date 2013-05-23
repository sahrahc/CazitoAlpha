<?php

ini_set('max_execution_time', 600);
echo "exec time " . ini_get('max_execution_time') . "<br/>";

echo '****************************************************** <br />';
echo 'Smoke Test: 4 players join new table and play together <br />';
echo '            All poker moves and time out tested until <br />';
echo '            end of game with two remaining players.<br />';
echo __FILE__ . "<br />";

// Setup ////////////////////////////////////////
include('../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../../../libraries/helper/DataHelper.php');

$conTest = connectToStateDB();

$player1Name = 'Test1';
$player2Name = 'Test2';
$player3Name = 'Test3';
$player4Name = 'Test4';
$tableSize = 1000;
$casinoTableId;
$gameSessionId;
$gameInstanceId;
$player1Id;
$player2Id;
$player3Id;
$player4Id;

session_start();

$qConn = QueueManager::getPlayerConnection();
$qCh = QueueManager::getPlayerChannel($qConn);
$qEx = QueueManager::getPlayerExchange($qCh);

/////////////////////////////////////////////////
// functions used in this test 

function testLogin($playerName) {
    $_SESSION['param_playerName'] = $playerName;
    include('Feature_Login.php');
    $localPlayerId = $_SESSION['param_playerId'];
    // TODO: replace with ASSERT
    if (is_null($localPlayerId)) {
        echo "Warning: Login for $playerName did not return playerId <br/>";
    }
    return $localPlayerId;
}

function testJoinTable($playerId, $expectedSeatNumber, $expectedStatus) {
    global $casinoTableId;
    global $gameSessionId;
    global $tableSize;
    // $player Id and param_playerId and param_casinoTableId
    // already set but set again to avoid implied values
    // for readability and maintainability
    $_SESSION['param_playerId'] = $playerId;
    $_SESSION['param_casinoTableId'] = $casinoTableId;
    $_SESSION['param_tableSize'] = $tableSize;
    include('Feature_JoinTable.php');
    // casino table id value should not change but get the
    // value and test
    $localCasinoId = $_SESSION['param_casinoTableId'];
    $localGameSessionId = $_SESSION['param_gameSessionId'];
    $localSeatNumber = $_SESSION['param_seatNumber'];
    $localGameStatus = $_SESSION['param_gameStatus'];
    // TODO: replace with assert
    if (is_null($casinoTableId)){
        $casinoTableId = $localCasinoId;
    }
    
    //elseif ($casinoTableId != $localCasinoId) {
    //    throw new Exception('Casino table id changed when joining');
    //}
    if (is_null($gameSessionId)) {
        $gameSessionId = $gameSessionId;
    }
    //elseif ($gameSessionId != $localGameSessionId) {
    //    throw new Exception('Game session id for table changed when joinin');
    //}
    if ($localSeatNumber != $expectedSeatNumber) {
        echo "Wrong seat $localSeatNumber given to $playerId on joining table <br />";
    }
    if ($localGameStatus != $expectedStatus) {
        echo "Wrong game status $localGameStatus given to $playerId on joining table <br />";
    }
}

function verifyQMessage($playerId, $queue, $eventType) {
    $message = $queue->get(AMQP_AUTOACK);
    if (!$message) {
        echo "Warning: no expected message $eventType received by $playerId <br />";
    }
    else {
    $messageBody = $message->getBody();
    echo "Info: Message for player id $playerId: $messageBody <br /> <br />";
    $messageObject = json_decode($messageBody);
    if ($messageObject->eventType != $eventType) {
        echo "Warning: Player $playerId received message $messageObject->eventType instead of $eventType";
    }
    }
}

echo '****************************************************** <br />';
echo 'PHASE 1: 4 players login and join table <br />';

$player1Id = testLogin($player1Name);
testJoinTable($player1Id, 0, GameStatus::INACTIVE);
$q1 = QueueManager::getPlayerQueue($player1Id, $qCh);
verifyQMessage($player1Id, $q1, EventType::USER_JOINED);

$player2Id = testLogin($player2Name);
testJoinTable($player2Id, 1, GameStatus::INACTIVE);
$q2 = QueueManager::getPlayerQueue($player2Id, $qCh);
// Verify player1 received message
verifyQMessage($player1Id, $q1, EventType::USER_JOINED);
verifyQMessage($player2Id, $q2, EventType::USER_JOINED);

$player3Id = testLogin($player3Name);
testJoinTable($player3Id, 2, GameStatus::INACTIVE);
$q3 = QueueManager::getPlayerQueue($player3Id, $qCh);
// verify  player1, player2 received UserJoined communication
verifyQMessage($player1Id, $q1, EventType::USER_JOINED);
verifyQMessage($player2Id, $q2, EventType::USER_JOINED);
verifyQMessage($player3Id, $q3, EventType::USER_JOINED);

$player4Id = testLogin($player4Name);
testJoinTable($player4Id, 3, GameStatus::INACTIVE);
$q4 = QueueManager::getPlayerQueue($player4Id, $qCh);
// TODO: verify  player1, player2, player3 received UserJoined communication
verifyQMessage($player1Id, $q1, EventType::USER_JOINED);
verifyQMessage($player2Id, $q2, EventType::USER_JOINED);
verifyQMessage($player3Id, $q3, EventType::USER_JOINED);
verifyQMessage($player4Id, $q4, EventType::USER_JOINED);

echo '****************************************************** <br />';
echo 'PHASE 2: game start and blind bet verification <br />';

$_SESSION['param_playerId'] = $player1Id;
// no need to set param_gameSessionId
$_SESSION['param_tableSize'] = $tableSize;

include('Feature_StartLiveGame.php');

$gameInstanceId = $_SESSION['param_gameInstanceId'];
// verify
$dealerPlayerId = $_SESSION['param_dealerPlayerId'];
if ($dealerPlayerId != $player1Id) {
    echo "Warning: Incorrect dealer assigned $dealerPlayerId instead of $player1Id<br/>";
}
$firstPlayerId = $_SESSION['param_firstPlayerId'];
if ($firstPlayerId != $player4Id) {
    echo "Warning: Incorrect first player id $firstPlayerId instead of $player4Id<br/>";
}
$blindBet1PlayerId = $_SESSION['param_blindBet1PlayerId'];
if ($blindBet1PlayerId != $player2Id) {
    echo "Warning: Incorrect first blind bet player $blindBet1PlayerId instead of $player2Id <br/>";
}
$blindBet1BetSize = $_SESSION['param_blindBet1Size'];
if ($blindBet1BetSize != $tableSize/2) {
    echo "Warning: Incorrect first blind bet size of $blindBet1BetSize instead of " . $tableSize/2 . "<br/>";
}
$blindBet2PlayerId = $_SESSION['param_blindBet2PlayerId'];
if ($blindBet2PlayerId != $player3Id) {
    echo "Warning: Incorrect second blind bet playerId $blindBet2PlayerId instead of $player3Id <br />";
}
$blindBet2BetSize = $_SESSION['param_blindBet2Size'];
if ($blindBet2BetSize != $tableSize) {
    echo "Warning: Incorrect second blind bet size $blindBet2BetSize instead of ". $tableSize . "<br/>";
}
$playerStatusDtos = json_decode($_SESSION['param_playerStatusDtos']);

verifyQMessage($player1Id, $q1, EventType::GAME_STARTED);
verifyQMessage($player3Id, $q3, EventType::GAME_STARTED);
verifyQMessage($player4Id, $q4, EventType::GAME_STARTED);

echo '****************************************************** <br />';
echo 'PHASE 2: round 1 of poker player starting with first player <br />';

$_SESSION['param_turnPlayerId'] = $player1Id;
$_SESSION['param_pokerActionType'] = PokerActionType::RAISED;
$_SESSION['param_pokerActionValue'] = 500;
include('Feature_SendPlayerAction.php');

verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player2Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CALLED;
$_SESSION['param_pokerActionValue'] = 500;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player3Id;
$_SESSION['param_pokerActionType'] = PokerActionType::RAISED;
$_SESSION['param_pokerActionValue'] = 1000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player4Id;
$_SESSION['param_pokerActionType'] = PokerActionType::RAISED;
$_SESSION['param_pokerActionValue'] = 2000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);

echo '****************************************************** <br />';
echo 'PHASE 3: round 2 of poker player, player 2 folds <br />';

$_SESSION['param_turnPlayerId'] = $player1Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CHECKED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player2Id;
$_SESSION['param_pokerActionType'] = PokerActionType::FOLDED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player3Id;
$_SESSION['param_pokerActionType'] = PokerActionType::RAISED;
$_SESSION['param_pokerActionValue'] = 2000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

$_SESSION['param_turnPlayerId'] = $player4Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CALLED;
$_SESSION['param_pokerActionValue'] = 1000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);

echo '****************************************************** <br />';
echo 'PHASE 4: round 3 of poker player, player 4 time outs <br />';

$_SESSION['param_turnPlayerId'] = $player1Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CALLED;
$_SESSION['param_pokerActionValue'] = 1000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);
/*
$_SESSION['param_turnPlayerId'] = $player2Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CHECKED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);
*/
$_SESSION['param_turnPlayerId'] = $player3Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CHECKED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);

//sleep(29);
//checkExpiration();

verifyQMessage($player1Id, $q1, EventType::TIME_OUT);
verifyQMessage($player2Id, $q2, EventType::TIME_OUT);
verifyQMessage($player3Id, $q3, EventType::TIME_OUT);
verifyQMessage($player4Id, $q4, EventType::TIME_OUT);

echo '****************************************************** <br />';
echo 'PHASE 5: round 3 of poker player, final round of play two players <br />';

$_SESSION['param_turnPlayerId'] = $player1Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CHECKED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

//verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
//verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);
/*
$_SESSION['param_turnPlayerId'] = $player2Id;
$_SESSION['param_pokerActionType'] = PokerActionType::FOLDED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);
*/
$_SESSION['param_turnPlayerId'] = $player3Id;
$_SESSION['param_pokerActionType'] = PokerActionType::RAISED;
$_SESSION['param_pokerActionValue'] = 2000;
include('Feature_SendPlayerAction.php');

verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
//verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
//verifyQMessage($player4Id, $q4, EventType::PLAYER_MOVE);
/*
$_SESSION['param_turnPlayerId'] = $player4Id;
$_SESSION['param_pokerActionType'] = PokerActionType::CALLED;
$_SESSION['param_pokerActionValue'] = NULL;
include('Feature_SendPlayerAction.php');
 
verifyQMessage($player1Id, $q1, EventType::PLAYER_MOVE);
verifyQMessage($player2Id, $q2, EventType::PLAYER_MOVE);
verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);
 */
echo 'PHASE 6: end of game <br />';

verifyQMessage($player3Id, $q3, EventType::PLAYER_MOVE);

echo '****************************************************** <br />';


/**********************************************************************************/
/* time out
 * get new move and compare it is different than previous move */
/* check message:  player status changed */
// FIXME: sleep while 30 seconds or there is a message;

/* skip 2 more times
 * send play for user
 * verify community cards changed and nothing else
 */


/////////////////////////////////////////////////
// cleanup

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");

cleanUpGameSessionById($conTest, $gameSessionId);
cleanUpPlayerById($conTest, $player1Id);
cleanUpPlayerById($conTest, $player2Id);
cleanUpPlayerById($conTest, $player3Id);
cleanUpPlayerById($conTest, $player4Id);
cleanUpOrphanCasino($conTest);

echo "CleanUp: deleting queues $player1Id, $player2Id, $player3Id, $player4Id <br />";
$q1->delete();
$q2->delete();
$q3->delete();
$q4->delete();
session_destroy();


?>
