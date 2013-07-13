<?php

// Description /////////////////////////////////////////////
// IN: param_turnPlayerId
//     param_gameInstanceId
//     param_pokerActionType
//     param_pokerActionValue (if type is called or raised)
// OUT: param_playerId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
echo "Testing Feature: Send Player Action <br /><br />";

// mandatory variables, if not found PHP will raise an error
$turnPlayerId = $_SESSION['param_turnPlayerId'];
$gameSessionId = $_SESSION['param_gameSessionId'];
$gameInstanceId = $_SESSION['param_gameInstanceId'];
$pokerActionType = $_SESSION['param_pokerActionType'];
$pokerActionValue = null;
if ($pokerActionType == PokerActionType::CALLED ||
        $pokerActionType == PokerActionType::RAISED) {
    $pokerActionValue = $_SESSION['param_pokerActionValue'];
}

////////////////////////////////////////////////////////////

global $dateTimeFormat;
$currentDT = date($dateTimeFormat);
$msg = json_encode(array(
    "eventType" => ActionType::MakePokerMove,
    "gameSessionId" => $gameSessionId,
    "requestingPlayerId" => $turnPlayerId,
    "gameInstanceId" => $gameInstanceId,
    "eventData" => array(
        "pokerActionType" => $pokerActionType,
        "actionTime" => $currentDT,
        "actionValue" => $pokerActionValue
    )));
//echo "Parameter In: $par <br /><br />";

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetSessionExchange($qCh);
$qEx->publish($msg, 's' . $gameSessionId);
?>
