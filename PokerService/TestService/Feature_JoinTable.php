<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_casinoTableId
//     param_tableSize (optional)
// OUT: param_gameStatusDto
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Join Table <br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];
$casinoTableId = $_SESSION['param_casinoTableId'];

if (isset($_SESSION['param_tableSize'])) {
    $tableSize = $_SESSION['param_tableSize'];
}
if (isset($_SESSION['param_tableName'])) {
    $tableName = $_SESSION['param_tableName'];
}
if (isset($_SESSION['param_tableCode'])) {
    $tableCode = $_SESSION['param_tableCode'];
}
////////////////////////////////////////////////////////////
// test 

if (is_null($casinoTableId)) {
$par = json_encode(array(
    "tableName"=>$tableName, 
    "requestingPlayerId"=>$playerId, 
    "tableSize"=>$tableSize));

//echo "Parameter In: $par <br /><br />";
$gameStatusDtoEncoded = CreateTable($par);    
//echo "Parameter Out (REST): $gameStatusDtoEncoded <br /> <br />";
}
else {
$par = json_encode(array(
    "casinoTableId"=>$casinoTableId, 
    "requestingPlayerId"=>$playerId, 
    "tableCode"=>$tableCode));
$gameStatusDtoEncoded = JoinTable($par);
}
$gameStatusDto = json_decode($gameStatusDtoEncoded);

////////////////////////////////////////////////////////////
// parmeter out

$_SESSION['param_gameStatusDto'] = $gameStatusDto;
// how do you che
?>
