<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameSessionId
//     param_gameInstanceId
// OUT: none
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Cheat Feature Test: Social Spotter <br /><br />";

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
if (is_null($playerId) || is_null($gameSessionId) || is_null($gameInstanceId)) {
    exit(1);
}

////////////////////////////////////////////////////////////
//test

$par = json_encode(array("itemType" => ItemType::SOCIAL_SPOTTER,
    "userPlayerId" => $playerId,
    "gameSessionId" => $gameSessionId,
    "gameInstanceId" => $gameInstanceId));
echo "Parameter: " . $par . "<br />";
$returnDto = cheat($par);

?>
