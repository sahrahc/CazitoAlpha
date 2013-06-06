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
$gameInstanceId = $_SESSION['param_gameInstanceId'];
$pokerActionType = $_SESSION['param_pokerActionType'];
if ($pokerActionType == PokerActionType::CALLED ||
        $pokerActionType == PokerActionType::RAISED) {
    $pokerActionValue = $_SESSION['param_pokerActionValue'];
}

////////////////////////////////////////////////////////////

global $dateTimeFormat;
$date = date($dateTimeFormat);
$par = json_encode(new PlayerAction(
        $gameInstanceId, 
        $turnPlayerId, 
        $pokerActionType,
        $date, 
        $pokerActionValue));

echo "Parameter In: $par <br /><br />";
$actionResultDtoEncoded = sendPlayerAction($par);
//$actionResultDto = json_decode($actionResultDtoEncoded);

$qConn = QueueManager::GetQueueConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetPlayerExchange($qCh);
$q = QueueManager::GetPlayerQueue($playerId, $qCh);

while ($message = $q->get(AMQP_AUTOACK)) {
    $messageBody = $message->getBody();
    echo "Parameter Out (Queue): $messageBody <br /> <br />";
    $messageObject = json_decode($messageBody);
    if ($messageObject->eventType == EventType::PLAYER_MOVE) {
        $actionResultDto = $messageObject->eventData;
    }
}

echo "Parameter Out (REST): $actionResultDtoEncoded <br /> <br />";

?>
