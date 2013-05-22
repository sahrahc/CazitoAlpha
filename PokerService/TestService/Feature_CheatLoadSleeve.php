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
echo "Cheat Feature Test: Load Sleeve <br /><br />";

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

$cardNames = array('hearts_4', 'spades_5');
$par = json_encode(array("itemType"=>ItemType::LOAD_CARD_ON_SLEEVE,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>null,
    "gameInstanceId"=>null,
    "cardNameList"=>$cardNames));
echo "Parameter In: " . $par . "<br /><br />";
$returnDto = cheat($par);
//echo "Result: " . $returnDto . "<br /><br />";

?>
