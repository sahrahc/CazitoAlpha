<?php

// Description /////////////////////////////////////////////
// IN: param_playerName
// OUT: param_playerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Logout <br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];

////////////////////////////////////////////////////////////

$par = json_encode(array("requestingPlayerId"=>$playerId));

//echo "Parameter In: $par <br /><br />";
$ok = logout($par);
if ($ok != '"OK"') {
    echo "*** FAILED: Log out should return OK rest response but returned $ok<br/>";
}

?>
