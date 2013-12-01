<?php

/**
 * Add users to table and grow waiting list to three
 * Have second seated player leave
 * The first waiting user should be offered the seat - verify message
 * The user accepts the seat - verify message to all players
 */
include('../PokerPlayerService.php');
include(dirname(__FILE__) . '/../EventMessageService.php');
include('showObject.php');

/**********************************************************************************
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
$par = json_encode(array("casinoTableId"=>null, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
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
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player2Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player2Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Charles
$name = 'Charles';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player3Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player3Id on seat number $gameStatusDto->userSeatNumber...<br />";

// player #4
$name = 'David';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player4Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player4Id on seat number $gameStatusDto->userSeatNumber...<br />";

echo '******************************************************<br />';
echo 'TEST 14.1: Add Eric and Fred and verify they are added but on the waiting list <br /><br />';

// Player # 5
$name = 'Eric';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player5Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player5Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Player # 6
$name = 'Fred';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player6Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player6Id on seat number $gameStatusDto->userSeatNumber...<br />";

echo '******************************************************<br />';
echo 'Test 14.3 - Bob leaves session <br /><br />';

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player2Id));
    echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players after Bob leaves: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

echo "<br> Eric takes the seat...<br />";
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player5Id,
    "seatNumber"=>1));
    echo "Encoded parameter: $par <br /><br />";
takeSeat($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players after Eric takes seat: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

/*--------------------------------------------------------------------------------------/
 * second player leaves
 */
echo '******************************************************<br />';
echo 'Test 12.4 - Anna leaves session <br /><br />';

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player1Id));
    echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output

// verify Fred got offered a seat

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

echo "<br> Fred takes Anna's seat...<br />";
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player6Id,
    "seatNumber"=>0));
    echo "Encoded parameter: $par <br /><br />";
takeSeat($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players after Fred takes seat: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

echo '******************************************************<br />';
// Player # 7
$name = 'Gary';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player7Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player7Id on seat number $gameStatusDto->userSeatNumber...<br />";

// Player # 8
$name = 'Helen';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player8Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
echo "$name created with user id $player8Id on seat number $gameStatusDto->userSeatNumber...<br />";

/*--------------------------------------------------------------------------------------/
 * third player leaves
 */
echo '******************************************************<br />';
echo 'Test 14.4 - Eric leaves session <br /><br />';
// TODO: test player leaving every seat, only tested first and second.

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player5Id));
    echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output

// verify Fred got offered a seat

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

echo '******************************************************<br />';
echo 'Test 14.5 - Start game, verify seating for Eric <br /><br />';

// start game
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$player4Id,
    "isPractice"=>0, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameInstanceSetupDtoEncoded = startGame($par);
    echo "Encoded return object: $gameInstanceSetupDtoEncoded <br /> <br />";
$gameInstanceSetupDto = json_decode($gameInstanceSetupDtoEncoded);
echo '<br />Game Started... <br /><br />';
$gameInstanceSetup = json_decode($gameInstanceSetupDtoEncoded);
$gameInstanceId = $gameInstanceSetup->gameInstanceId;

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->loadPlayers();
echo "Casino players: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

$playerInstanceDtos = EntityHelper::getPlayerStatusDtosForInstance($gameInstanceId);
echo "<br />Player statuses (Eric is gone and nobody else got added): <br /> ";
for ($i=0; $i<count($playerInstanceDtos); $i++) {
    echo " - Player " . $playerInstanceDtos[$i]->playerName . " is on seat " .
            $playerInstanceDtos[$i]->seatNumber . " and status is " . $playerInstanceDtos[$i]->status . "<br />";
}

echo "<br />David leaves, test game <br />";
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$player4Id));
    echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output


?>
