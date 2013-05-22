<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameSessionId
//     param_gameInstanceId
// OUT: 
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo "Cheat Feature Test:  Suit Marker <br /><br />";

/* warning if test doesn't work: player id for playerCardNumber = 1
 * on original code but seemed redundant. see unit test.
 */
$playerId = $_POST['param_playerId'];
if (is_null($playerId)) {
    echo "Missing required parameter param_playerId <br /><br />";
}
$gameSessionId = $_POST['param_gameSessionId'];
if (is_null($gameSessionId)) {
    echo "Missing required parameter param_gameSessionId <br /><br />";
}
$gameInstanceId = $_POST['param_gameInstanceId'];
if (is_null($gameInstanceId)) {
    echo "Missing required parameter param_gameInstanceId <br /><br />";
}
$suitType = $_POST['param_suitType'];
if (is_null($suitType)) {
    echo "Missing required parameter param_suitType <br /><br />";
}

if (is_null($playerId) || is_null($gameInstanceId || 
        is_null($gameSessionId)) || is_null($suitType)) {
    exit(1);
}
////////////////////////////////////////////////////////////

$par = json_encode(array("itemType"=>$suitType,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId));
echo "Parameter In: $par <br /><br />";
$returnDto = cheat($par);
echo "Parameter Out: " . $returnDto . "<br /><br />";

?>
