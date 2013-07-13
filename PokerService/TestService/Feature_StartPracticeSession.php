<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
// OUT: param_gameStatusDto
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Testing Feature: Start Practice Game <br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];

////////////////////////////////////////////////////////////

$par = json_encode(array("requestingPlayerId"=>$playerId));
$gameStatusDtoEncoded = startPracticeSession($par);
$gameStatusDto = json_decode($gameStatusDtoEncoded);

////////////////////////////////////////////////////////////
// parmeter out

$_SESSION['param_gameStatusDto'] = $gameStatusDto;
echo "param out: $gameStatusDtoEncoded<br/><br/>";
?>
