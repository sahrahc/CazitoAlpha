<?php

echo __FILE__ . "<br />";

//////////////////////////
$name = 'Test0';
//////////////////////////

/**
 * Add users to table and grow waiting list to three
 * Have second seated player leave
 * The first waiting user should be offered the seat - verify message
 * The user accepts the seat - verify message to all players
 */
include('../PokerPlayerService.php');
include('showObject.php');

/* * ********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT max(Id) from CasinoTable", 'ERROR');
$row = mysql_fetch_array($result);
$casinoTableId1 = $row[0] + 1;

mysql_query("Delete from Player WHERE Name in ('Anna', 'Bob', 'Charles, 'David', 'Eric', 'Fred', 'Gary', 'Helen')");
mysql_query("Delete from PlayerState ps WHERE PlayerId not in (select id from player)");

echo '******************************************************<br />';

echo 'Setup: new player Anna starts new table and is joined by Bob, Charles, David <br /> <br />';

$name = 'Anna';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => null, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player1Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
$gameSessionId = $gameStatusDto->gameSessionId;
echo "$name created with user id $player1Id on seat number $gameStatusDto->userSeatNumber...<br />";
echo "Casino table created with table id $casinoTableId... <br />";
echo "Game Session id is $gameSessionId... <br /><br />";

// Bob
$name = 'Bob';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player2Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player2Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Charles
$name = 'Charles';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player3Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player3Id on seat number $gameStatusDto->userSeatNumber...<br />";

// player #4
$name = 'David';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player4Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player4Id on seat number $gameStatusDto->userSeatNumber...<br />";

/* start gaming on session with full table but no waiting list */
$par = json_encode(array("gameSessionId" => $gameSessionId,
    "requestingPlayerId" => $player1Id,
    "isPractice" => 0, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = startGame($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
echo '<br />Game Started... <br /><br />';
$gameStatusDto = json_decode($gameStatusDtoEncoded);
$gameInstance1Id = $gameStatusDto->gameInstanceId;

/* same as above in a different casino - test starting gaming on a new session with waiting list */

/* test user joining active game */

echo '******************************************************<br />';
echo 'TEST 14.1: Add Eric and Fred and verify they are added but on the waiting list <br /><br />';

/* add user already added, verify added only once */

/* move player from casino table to casino table */

/* start game instances after move */
// Player # 5
$name = 'Eric';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player5Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player5Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Player # 6
$name = 'Fred';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player6Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player6Id on seat number $gameStatusDto->userSeatNumber...<br />";

echo '******************************************************<br />';
echo 'Test 14.3 - Bob leaves session <br /><br />';

$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player2Id));
echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no outputc
// verify Eric got offered a seat
// TODO: verify user who doesn't belong in table is leaving session
// TODO: verify leaving table when there are no users in waiting table
// TODO: offer seat to table who is not reserved and table is full
// TODO: take seat not offered but empty seats available
printCasinoTableSeating($casinoTableId);
echo "Casino players after Bob leaves: <br />";

$playerInstances = PlayerInstance::GetPlayerInstancesForGame($gameInstance1Id);
echo "<br />Player statuses (Bob's status set to left but players should not have been added): <br /> ";
for ($i = 0; $i < count($playerInstances); $i++) {
    echo " - Player " . $playerInstances[$i]->playerName . " is on seat " .
    $playerInstances[$i]->seatNumber . " and status is " . $playerInstances[$i]->status . "<br />";
}

echo "<br> Eric takes the seat...<br />";
$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player5Id,
    "seatNumber" => 1));
echo "Encoded parameter: $par <br /><br />";
takeSeat($par); // no output
echo "Casino players after Eric takes seat: <br />";
printCasinoTableSeating($casinoTableId);

$playerInstances = PlayerInstance::GetPlayerInstancesForGame($gameInstance1Id);
echo "<br />Player statuses (Bob's status set to left but players should not have been added): <br /> ";
for ($i = 0; $i < count($playerInstances); $i++) {
    echo " - Player " . $playerInstances[$i]->playerName . " is on seat " .
    $playerInstances[$i]->seatNumber . " and status is " . $playerInstances[$i]->status . "<br />";
}

/* restart game after one player leaves, after two player leaves, etc. */

echo '******************************************************<br />';
echo 'TEST CASE 13.1: sendPlayerAction with recycled game session <br />';

global $dateTimeFormat;
$date = date($dateTimeFormat);
$playerAction = new PlayerAction($gameInstanceId, $playerId, PokerActionType::CALLED, $date, 10000);

$par = json_encode($playerAction);
echo "parameter $par <br />";
$actionResultArray = sendPlayerAction($par);
showPokerMove($par, $actionResultArray);

/* --------------------------------------------------------------------------------------/
 * second player leaves
 */
echo '******************************************************<br />';
echo 'Test 12.4 - Anna leaves session <br /><br />';

$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player1Id));
echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output
// verify Fred got offered a seat

echo "Casino players: <br />";
printCasinoTableSeating($casinoTableId);

$playerInstances = PlayerInstance::GetPlayerInstancesForGame($gameInstance2Id);
echo "<br />Player statuses (Anna's status set to left but players should nt have been added): <br /> ";
for ($i = 0; $i < count($playerInstances); $i++) {
    echo " - Player " . $playerInstances[$i]->playerName . " is on seat " .
    $playerInstances[$i]->seatNumber . " and status is " . $playerInstances[$i]->status . "<br />";
}

echo "<br> Fred takes Anna's seat...<br />";
$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player6Id,
    "seatNumber" => 0));
echo "Encoded parameter: $par <br /><br />";
takeSeat($par); // no output


echo "Casino players after Fred takes seat: <br />";
printCasinoTableSeating($casinoTableId);

function printCasinoTableSeating($casinoTableId) {
    $players = EntityHelper::GetPlayersForCasinoTable($casinoTableId);
    for ($i = 0; $i < count($players); $i++) {
        echo " - Player " . $players[$i]->name . " is on seat " .
        $players[$i]->currentSeatNumber . " and reserved seat " . $players[$i]->reservedSeatNumber . "<br />";
    }
}

echo '******************************************************<br />';
// Player # 7
$name = 'Gary';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player7Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player7Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Player # 8
$name = 'Helen';
$par = json_encode(array("playerName" => $name));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;

$par = json_encode(array("casinoTableId" => $casinoTableId, "userPlayerId" => $userPlayerId, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player8Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player8Id on seat number $gameStatusDto->userSeatNumber...<br />";

/* --------------------------------------------------------------------------------------/
 * third player leaves
 */
echo '******************************************************<br />';
echo 'Test 14.4 - Eric leaves session <br /><br />';
// TODO: test player leaving every seat, only tested first and second.

$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player5Id));
echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output
// verify Fred got offered a seat

echo "Casino players: <br />";
printCasinoTableSeating($casinoTableId);

echo '******************************************************<br />';
echo 'Test 14.5 - Start game, verify seating for Eric <br /><br />';

// start game
$par = json_encode(array("gameSessionId" => $gameSessionId,
    "requestingPlayerId" => $player4Id,
    "isPractice" => 0, "tableSize" => null));
echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = startGame($par);
echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);
echo '<br />Game Started... <br /><br />';
$gameInstanceId = $gameStatusDto->gameInstanceId;

echo "Casino players: <br />";
printCasinoTableSeating($casinoTableId);

$playerInstances = PlayerInstance::GetPlayerInstancesForGame($gameStatusDtoEncoded->gameInstanceId);
$playerInstanceDtos = PlayerStatusDto::MapPlayerStatuses($playerInstances);
echo "<br />Player statuses (Eric is gone and nobody else got added): <br /> ";
for ($i = 0; $i < count($playerInstanceDtos); $i++) {
    echo " - Player " . $playerInstanceDtos[$i]->playerName . " is on seat " .
    $playerInstanceDtos[$i]->seatNumber . " and status is " . $playerInstanceDtos[$i]->status . "<br />";
}

echo "<br />David leaves, test game <br />";
$par = json_encode(array("gameSessionId" => $gameSessionId,
    "playerId" => $player4Id));
echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output
?>
