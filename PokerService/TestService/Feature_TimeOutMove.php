<?php

// Description /////////////////////////////////////////////
// IN: param_turnPlayerId
//     param_gameInstanceId
//     param_timeOutSeconds (optional, default 3)
// OUT: param_turnPlayerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Testing Feature: Time-Out Move for given Session <br /><br />";

$turnPlayerId = $_POST['param_turnPlayerId'];
if (is_null($turnPlayerId)) {
    echo "Missing required parameter param_turnPlayerId <br /><br />";
}
$gameInstanceId = $_POST['param_gameInstanceId'];
if (is_null($gameInstanceId)) {
    echo "Missing required parameter param_gameInstanceId <br /><br />";
}
if (is_null($turnPlayerId) || is_null($gameInstanceId)) {
    exit(1);
}

$timeOutSeconds = $_POST['param_timeOutSeconds'];
if (is_null($timeOutSeconds)) {
    $timeOutSeconds = 3;
}

////////////////////////////////////////////////////////////

echo "Game Instance Id " . $gameInstanceId . "<br />";
echo "Next Player Id " . $turnPlayerId . "<br />";

sleep(3);
ProcessExpiredPokerMoves();

$objAfter = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstanceId);
echo "Row after: " . json_encode($objAfter) . "<br />";

$_SESSION['param_turnPlayerId'] = $objAfter->playerId;

?>
