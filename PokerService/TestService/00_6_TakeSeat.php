<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
$con = connectToStateDB();

$playerName = 'Test8';
$casinoTableId = 1;
$seatNumber = 3;
$result = executeSQL("SELECT Id from player where name = '$playerName'
    ", 'ERROR');
$row = mysql_fetch_array($result);
$playerId = $row[0];

$result = executeSQL("SELECT CurrentGameSessionId from casinotable where id = $casinoTableId
    ", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$playerId,
    "seatNumber"=>$seatNumber));
    echo "Encoded parameter: $par <br /><br />";
takeSeat($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->getCasinoPlayerDtos();
echo "Casino players after $playerName takes seat: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}

?>
