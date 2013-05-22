<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameInstanceId
//     param_casinoTableId (print output only, but still required)
// OUT: param_playerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Leave Table <br /><br />";

$playerId = $_POST['param_playerId'];
if (is_null($playerId)) {
    echo "Missing required parameter param_playerId <br /><br />";
}
$gameSessionId = $_POST['param_gameSessionId'];
if (is_null($gameSessionId)) {
    echo "Missing required parameter param_gameSessionId <br /><br />";
}
$casinoTableId = $_POST['param_casinoTableId'];
if (is_null($casinoTableId)) {
    echo "Missing required parameter param_casinoTableId <br /><br />";
}
if (is_null($playerId) || is_null($gameSessionId) || $casinoTableId) {
    exit(1);
}

////////////////////////////////////////////////////////////

$par = json_encode(array(
    "gameSessionId"=>$gameSessionId,
    "playerId"=>$playerId));
echo "Parameter In: $par <br /><br />";
leaveSaloon($par); // no output

$casinoTable = EntityHelper::getCasinoTable($casinoTableId);
$playerDtos = $casinoTable->getCasinoPlayerDtos();
echo "Parameter Out:<br />";
echo "Casino players after $playerId leaves: <br />";
for ($i=0; $i<count($playerDtos); $i++) {
    echo " - Player " . $playerDtos[$i]->playerName . " is on seat " .
            $playerDtos[$i]->currentSeatNumber . " and reserved seat " . $playerDtos[$i]->reservedSeatNumber . "<br />";
}
echo "<br />";

?>
