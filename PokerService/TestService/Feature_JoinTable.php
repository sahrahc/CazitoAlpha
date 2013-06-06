<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_casinoTableId
//     param_tableSize (optional)
// OUT: param_casinoTableId
//      param_gameSessionId
//      param_seatNumber
//      param_gameStatus
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Join Table <br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];
$casinoTableId = $_SESSION['param_casinoTableId'];

//// optional parameters
if (isset($_SESSION['param_tableSize'])) {
    $tableSize = $_SESSION['param_tableSize'];
}
else {$tableSize = null;}

////////////////////////////////////////////////////////////
// test 

$par = json_encode(array(
    "casinoTableId"=>$casinoTableId, 
    "userPlayerId"=>$playerId, 
    "tableSize"=>$tableSize));

echo "Parameter In: $par <br /><br />";
$gameStatusDtoEncoded = JoinTable($par);
echo "Parameter Out (REST): $gameStatusDtoEncoded <br /> <br />";

$gameStatusDto = json_decode($gameStatusDtoEncoded);

////////////////////////////////////////////////////////////
// parmeter out

$_SESSION['param_casinoTableId'] = $gameStatusDto->casinoTableId;
$_SESSION['param_gameSessionId'] = $gameStatusDto->gameSessionId;
$_SESSION['param_seatNumber'] = $gameStatusDto->userSeatNumber;
$_SESSION['param_gameStatus'] = $gameStatusDto->gameStatus;
?>
