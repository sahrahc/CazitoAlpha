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

$playerId = $_SESSION['param_playerId'];
if (is_null($playerId)) {
    echo "Missing required parameter param_playerId <br /><br />";
}
$gameSessionId = $_SESSION['param_gameSessionId'];
if (is_null($gameSessionId)) {
    echo "Missing required parameter param_gameSessionId <br /><br />";
}
$seatNumber = $_SESSION['param_seatNumber'];
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

$players = EntityHelper::GetPlayersForCasinoTable($casinoTableId);
echo "Parameter Out:<br />";
echo "Casino players after $playerId takes seat: <br />";
for ($i=0; $i<count($players); $i++) {
    echo " - Player " . $players[$i]->playerName . " is on seat " .
            $players[$i]->currentSeatNumber . " and reserved seat " . $players[$i]->reservedSeatNumber . "<br />";
}
echo "<br />";

?>
