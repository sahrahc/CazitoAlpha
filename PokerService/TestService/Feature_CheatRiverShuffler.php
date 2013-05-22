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
echo "Cheat Feature Test: River Shuffler <br /><br />";

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

$cCards = CardHelper::getCommunityCardDtos($gameInstanceId, 5);
echo "Community card before swap: " . json_encode($cCards) . "<br /><br />";

$par = json_encode(array("itemType" => ItemType::RIVER_SHUFFLER,
    "userPlayerId" => $playerId,
    "gameSessionId" => $gameSessionId,
    "gameInstanceId" => $gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
//echo "Result: " . $returnDto . "<br /><br />";

$cCards = CardHelper::getCommunityCardDtos($gameInstanceId, 5);
echo "Community card after looking: " . json_encode($cCards) . "<br /><br />";

$par = json_encode(array("itemType" => ItemType::RIVER_SHUFFLER_USE,
    "userPlayerId" => $playerId,
    "gameSessionId" => $gameSessionId,
    "gameInstanceId" => $gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
//echo "Result: " . $returnDto . "<br /><br />";

$cCards = CardHelper::getCommunityCardDtos($gameInstanceId, 5);
echo "Community card after swap: " . json_encode($cCards) . "<br /><br />";

?>
