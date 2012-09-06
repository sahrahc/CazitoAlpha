<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
$con = connectToStateDB();

$playerName = 'Test4';
$casinoTableId = 1;

$result = executeSQL("SELECT Id from player where name = '$playerName'
    ", 'ERROR');
$row = mysql_fetch_array($result);
$playerId = $row[0];

$result = executeSQL("SELECT CurrentGameSessionId from casinotable where id = $casinoTableId
    ", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$playerId));
    echo "Encoded parameter: $par <br /><br />";
leaveSaloon($par); // no output

// verify Eric got offered a seat
// TODO: verify user who doesn't belong in table is leaving session
// TODO: verify leaving table when there are no users in waiting table
// TODO: offer seat to table who is not reserved and table is full
// TODO: take seat not offered but empty seats available
$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->getCasinoPlayerDtos();
echo "Casino players after $playerName leaves: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

?>
