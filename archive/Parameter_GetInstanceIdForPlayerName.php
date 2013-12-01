<?php

// Description /////////////////////////////////////////////
// Code snipet for retrieving game instance and player whose
// turn it is to play for a
// 
// IN: param_playerName
// OUT: param_gameInstanceId
//      param_turnPlayerId
//      param_playerId
//      param_gameSessionId
// 
// Setup ///////////////////////////////////////////////////
echo "------------------------------------------------------ <br />";
echo "Getting latest Game Instance Id and Next Player Id for a game at which given player name is playing  <br /><br />";

$playerName = $_POST['param_playerName'];
if (is_null($playerName)) {
    echo "Missing required parameter param_playerName <br /><br />";
    exit(1);    
}
if (is_null($conTest)) {
    $conTest = connectToStateDB();
}

////////////////////////////////////////////////////////////

$result = executeSQL("SELECT GameInstanceId AS LastInstanceId, 
        NextPlayerId
    FROM PlayerState ps
    INNER JOIN Player p ON p.id = ps.PlayerId
    INNER JOIN GameInstance i ON ps.GameInstanceId = i.Id 
    WHERE p.name = '$name' 
    ORDER BY ps.GameInstanceId desc LIMIT 1", 'ERROR');
$row = mysql_fetch_array($result);

$_SESSION['param_gameInstanceId'] = $row[0];
$_SESSION['param_turnPlayerId'] = $row[1];

?>
