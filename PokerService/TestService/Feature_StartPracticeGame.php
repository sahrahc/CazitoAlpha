<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
// OUT: param_gameSessionId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Testing Feature: Start Practice Game <br /><br />";

$playerId = $_POST['param_playerId'];
if (is_null($playerId)) {
    echo "Missing required parameter param_playerId <br /><br />";
    exit(1);
}

////////////////////////////////////////////////////////////

$par = json_encode(array("userPlayerId"=>$playerId));
echo "Parameter In: $par <br /><br />";
$gameInstanceSetupDto = startPracticeSession($par);
$gameInstanceSetup = json_decode($gameInstanceSetupDto);
echo "Parameter Out: $gameInstanceSetupDto <br /> <br />";

$_SESSION['param_gameInstanceId'] = $gameInstanceSetup->gameInstanceId;

?>
