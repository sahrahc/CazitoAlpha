<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameSessionId
//     param_seatNumber
// OUT: none
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Testing Feature: Take Seat <br /><br />";

$playerId = $_POST['param_playerId'];
if (is_null($playerId)) {
    echo "Missing required parameter param_playerId <br /><br />";
}
$gameSessionId = $_POST['param_gameSessionId'];
if (is_null($gameSessionId)) {
    echo "Missing required parameter param_gameSessionId <br /><br />";
}
$seatNumber = $_POST['param_seatNumber'];
if (is_null($seatNumber)) {
    echo "Missing required parameter param_seatNumber <br /><br />";
}
if (is_null($playerId) || is_null($gameSessionId) || $seatNumber) {
    exit(1);
}

require_once(dirname(__FILE__) . '/../PokerPlayerService.php');
////////////////////////////////////////////////////////////

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "playerId"=>$playerId,
    "seatNumber"=>$seatNumber));
echo "Parameter In: $par <br /><br />";
takeSeat($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->getCasinoPlayerDtos();
echo "Parameter Out:<br />";
echo "Casino players after $playerId takes seat: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}
echo "<br />";

?>
