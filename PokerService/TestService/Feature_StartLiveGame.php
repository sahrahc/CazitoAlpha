<?php

// Description /////////////////////////////////////////////
// IN: param_playerId
//     param_gameSessionid
// OUT: param_gameInstanceId
// 
// Setup ///////////////////////////////////////////////////
echo "////////////////////////////////////////////////////// <br />";
echo __FILE__ . "<br />";
$feature = '(Re)Start Live Game for known casino';
echo "Testing Feature: $feature<br /><br />";

// mandatory variables, if not found PHP will raise an error
$playerId = $_SESSION['param_playerId'];
$gameSessionId = $_SESSION['param_gameSessionId'];

//// optional parameters
if (isset($_SESSION['param_tableSize'])) {
    $tableSize = $_SESSION['param_tableSize'];
}
else {$tableSize = null;}

////////////////////////////////////////////////////////////

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$playerId,
    "isPractice"=>0, 
    "tableSize"=>$tableSize));

echo "Parameter In: $par <br /><br />";
$gameStatusDto = startGame($par);

$qConn = QueueManager::GetQueueConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetPlayerExchange($qCh);
$q = QueueManager::GetPlayerQueue($playerId, $qCh);

while ($message = $q->get(AMQP_AUTOACK)) {
    $messageBody = $message->getBody();
    echo "Parameter Out (Queue): $messageBody <br /> <br />";
    $messageObject = json_decode($messageBody);
    if ($messageObject->eventType == EventType::GAME_STARTED) {
        $gameStatusDto = $messageObject->eventData;
    }
}

echo "Parameter Out (REST, TEST ONLY): $gameStatusDto <br /> <br />";

// verify game session and user player id match in parameters
if ($gameStatusDto->gameSessionId != $gameSessionId) {
    throw new Exception("$feature: mismatch game session id in vs. out parameter");
}
if ($gameStatusDto->userPlayerId != $playerId) {
    throw new Exception("$feature: mismatch player id in vs. out parameter");
}

////////////////////////////////////////////////////////////
// parameter out
$_SESSION['param_gameInstanceId'] = $gameStatusDto->gameInstanceId;
$_SESSION['param_dealerPlayerId'] = $gameStatusDto->dealerPlayerId;
/* fix, blindBetDtos from playerStatusDtos
$_SESSION['param_blindBet1PlayerId'] = $gameStatusDto->blindBetDtos[0]->playerId;
$_SESSION['param_blindBet1Size'] = $gameStatusDto->blindBetDtos[0]->betSize;
$_SESSION['param_blindBet2PlayerId'] = $gameStatusDto->blindBetDtos[1]->playerId;
$_SESSION['param_blindBet2Size'] = $gameStatusDto->blindBetDtos[1]->betSize;
*/
$_SESSION['param_playerStatusDtos'] = json_encode($gameStatusDto->playerStatusDtos);
 

// object: {"gameSessionId":"31",
// "gameInstanceId":4,
// "dealerPlayerId":"5",
// "firstPlayerId":"2",
// "userPlayerId":"2",
// "blindBets":[{"playerId":"4","betSize":500},
//      {"playerId":"3","betSize":1000}],
// "playerStatusDtos":[
//   {"playerId":"2","playerName":"Test0",
//    "playerImageUrl":"Avatar_user0.jpeg",
//    "seatNumber":"0",
//    "status":"Waiting",
//    "blindBet":"0",
//    "stake":"30000",
//    "playAmount":"0",
//    "lastPlayInstanceNumber":"0"},
//   {"playerId":"3","playerName":"Test1","playerImageUrl":"Avatar_user0.jpeg","seatNumber":"1","status":"BlindBet","blindBet":"1000","stake":"29000","playAmount":"1000","lastPlayInstanceNumber":"0"},
//   {"playerId":"4","playerName":"Test2","playerImageUrl":"Avatar_user0.jpeg","seatNumber":"2","status":"BlindBet","blindBet":"500","stake":"29500","playAmount":"500","lastPlayInstanceNumber":"0"},
//   {"playerId":"5","playerName":"Test3","playerImageUrl":"Avatar_user0.jpeg","seatNumber":"3","status":"Waiting","blindBet":"0","stake":"30000","playAmount":"0","lastPlayInstanceNumber":"0"}],"userPlayerHandDto":{"playerId":"2","pokerCard1Dto":{"playerCardNumber":1,"cardName":"diamonds_8"},"pokerCard2Dto":{"playerCardNumber":2,"cardName":"clubs_2"},"pokerHandType":null,"isWinningHand":null}}

?>
