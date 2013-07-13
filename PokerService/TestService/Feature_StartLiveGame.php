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
//if (isset($_SESSION['param_tableSize'])) {
//$tableSize = $_SESSION['param_tableSize'];
//}
//else {$tableSize = null;}
////////////////////////////////////////////////////////////

$msg = json_encode(array(
    "eventType" => ActionType::StartGame,
    "gameSessionId" => $gameSessionId,
    "requestingPlayerId" => $playerId));

$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetSessionExchange($qCh);

$qEx->publish($msg, 's' . $gameSessionId);


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
