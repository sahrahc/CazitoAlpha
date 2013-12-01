<?php

include('../PokerPlayerService.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT max(Id) from CasinoTable", 'ERROR');
$row = mysql_fetch_array($result);
$casinoTableId1 = $row[0] + 1;
$casinoTableId2 = $casinoTableId1 + 1;

mysql_query("Delete from Player WHERE Name in ('MM', 'NN', 'OO')");
mysql_query("Delete from PlayerState ps WHERE PlayerId not in (select id from player)");
/*
mysql_query("Delete from PracticeSession");
mysql_query("Delete from GameInstance");
mysql_query("delete from GameCard");
mysql_query("Delete from PlayerState");
*/

echo '******************************************************<br />';

echo 'TEST CASE 6.1: unknown player MM with new table<br />';
$par = json_encode(array("casinoTableId"=>null, "playerName"=>"MM", "tableSize"=>null));
$gameStatusDto = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDto);

echo '****************************************************** <br />';
echo 'TEST: 6.2 known player MM with known casino table 1 should not create duplicates. <br />';
// anonymous

$par = json_encode(array("casinoTableId"=>$casinoTableId1, "playerName"=>"MM", "tableSize"=>null));
$gameStatusDto = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDto);


echo '****************************************************** <br />';
echo 'TEST 6.3: second unknown player NN with known casino 1 <br />';
// anonymous
$par = json_encode(array("casinoTableId"=>$casinoTableId1, "playerName"=>"NN", "tableSize"=>null));
$gameStatusDto = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDto);


echo '****************************************************** <br />';
echo 'TEST 6.4: third unknown player OO with unknown casino 2 <br />';
// anonymous

$par = json_encode(array("casinoTableId"=>$casinoTableId2, "playerName"=>"OO", "tableSize"=>null));
$gameStatusDto = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDto);

echo '****************************************************** <br />';
echo 'TEST 6.5: move third player OO with to casino 1 <br />';
// anonymous

$par = json_encode(array("casinoTableId"=>$casinoTableId1, "playerName"=>"OO", "tableSize"=>null));
$gameStatusDto = addUserToCasinoTable($par);
showGameStatusDto($par, $gameStatusDto);

echo '****************************************************** <br />';
echo 'TEST CASE 6.6: start a new game instance for casino 1 <br />';

$gameStatusDecoded = json_decode($gameStatusDto);
$par = json_encode(array("gameSessionId"=>$gameStatusDecoded->gameSessionId,
    "requestingPlayerId"=>$gameStatusDecoded->userPlayerId,
    "isPractice"=>0, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

echo '****************************************************** <br />';
echo 'TEST CASE 6.7: restart a new game instance for casino 1 <br />';
$par = json_encode(array("gameSessionId"=>$gameStatusDecoded->gameSessionId,
    "requestingPlayerId"=>$gameStatusDecoded->userPlayerId,
    "isPractice"=>0, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

?>
