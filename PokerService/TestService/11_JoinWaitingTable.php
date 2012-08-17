<?php

include_once('../PokerPlayerService.php');
include_once('../Components/EventMessageProducer.php');
include('showObject.php');
include('showMessage.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT max(Id) from CasinoTable", 'ERROR');
$row = mysql_fetch_array($result);
$casinoTableId1 = $row[0] + 1;
$casinoTableId2 = $casinoTableId1 + 1;

mysql_query("Delete from Player WHERE Name in ('AA', 'BB')");
mysql_query("Delete from PlayerState ps WHERE PlayerId not in (select id from player)");
/*
mysql_query("Delete from PracticeSession");
mysql_query("Delete from GameInstance");
mysql_query("delete from GameCard");
mysql_query("Delete from PlayerState");
*/

echo '******************************************************<br />';

echo 'TEST CASE 11.1: unknown player AA with new table joined by BB <br /> <br />';
$par = json_encode(array("casinoTableId"=>null, "playerName"=>"AA", "tableSize"=>null));
$gameStatusDtoEncoded = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDtoEncoded);
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player1Id = $gameStatusDto->userPlayerId;
$casinoTableId = $gameStatusDto->casinoTableId;
$gameSessionId = $gameStatusDto->gameSessionId;

/*
echo "Messages generated for AA; </br>";
$messageEncoded = getQueueMessage($player1Id);
echo "<br /><br />";
echo $messageEncoded . "<br />";
echo "<br /><br />";
echo '-------------------------------------------------------- <br />';
echo "Second User BB data: <br /> <br />";
*/
$par = json_encode(array("casinoTableId"=>$gameStatusDto->casinoTableId, "playerName"=>"BB", "tableSize"=>null));
$gameStatusDtoEncoded = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDtoEncoded);
$gameStatusDto = json_decode($gameStatusDtoEncoded);

$player2Id = $gameStatusDto->userPlayerId;

/*
echo "Messages generated for AA; </br>";
$messageEncoded = getQueueMessage($player1Id);
echo "<br /><br />";
echo $messageEncoded . "<br />";
echo "<br /><br />";
echo '-------------------------------------------------------- <br />';
echo "Messages generated for BB; </br>";
$messageEncoded = getQueueMessage($player2Id);
echo "<br /><br />";
echo $messageEncoded . "<br />";
echo "<br /><br />";
*/

echo '******************************************************<br />';
echo 'TEST CASE 11.2: start game by AA <br /><br />';

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$player1Id,
    "isPractice"=>0, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

echo '-------------------------------------------------------- <br />';
echo 'restart game by BB <br /> <br />';

$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$player1Id,
    "isPractice"=>0, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

?>
