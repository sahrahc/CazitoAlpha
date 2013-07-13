<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameInstanceId
//     param_casinoTableId (print output only, but still required)
// OUT: param_playerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Feature Test: Leave Table <br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];
$gameSessionId = $_SESSION['param_gameSessionId'];
$casinoTableId = $_SESSION['param_casinoTableId'];

////////////////////////////////////////////////////////////

$msg = json_encode(array(
    "eventType" => ActionType::LeaveTable,
    "gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$playerId));

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetSessionExchange($qCh);
$qEx->publish($msg, 's' . $gameSessionId);
?>
