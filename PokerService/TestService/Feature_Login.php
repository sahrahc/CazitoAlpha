<?php

// Description /////////////////////////////////////////////
// IN: param_playerName
// OUT: param_playerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Login <br /><br />";

// mandatory variables, if not found PHP will raise an error
$name = $_SESSION['param_playerName'];

////////////////////////////////////////////////////////////

$par = json_encode(array("playerName"=>$name));

echo "Parameter In: $par <br /><br />";
$userDtoEncoded = login($par);
$userDto = json_decode($userDtoEncoded);
echo "Parameter Out: $userDtoEncoded <br /><br />";

$_SESSION['param_playerId'] = $userDto->userPlayerId;

?>
