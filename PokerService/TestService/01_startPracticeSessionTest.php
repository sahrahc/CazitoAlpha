<?php

require_once(dirname(__FILE__) . '/../PokerPlayerService.php');
include('showObject.php');

$conT = connectToStateDB();
/*
mysql_query("Delete from PracticeSession");
mysql_query("Delete from GameInstance");
mysql_query("delete from GameCard");
mysql_query("Delete from Player");
mysql_query("Delete from PlayerState");
*/
echo '******************************************************<br />';

echo 'TEST CASE 1.1: unknown player JP <br />';
$par = json_encode(array("playerName"=>"JP"));
$userIdEncoded = login($par);
$user = json_decode($userIdEncoded);
$userPlayerId = $user->userPlayerId;
echo "     Login encoded return: $userIdEncoded <br />";
echo "     User player id: $user->userPlayerId <br /><br />";
echo "     User player name: $user->playerName <br /><br />";

$par = json_encode(array("userPlayerId"=>$userPlayerId));
$gameInstanceSetupDto = startPracticeSession($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

echo '****************************************************** <br />';
echo 'TEST: 1.2 known player JP <br />';
// anonymous
/*$par = json_encode(array("playerName"=>"JP"));
$gameInstanceSetupDto = startPracticeSession($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);
*/
echo '****************************************************** <br />';
echo 'TEST 1.3: second unknown player Cecilia <br />';
// anonymous
/*$par = json_encode(array("playerName"=>"Cecilia"));
$gameInstanceSetupDto = startPracticeSession($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);
*/
echo '****************************************************** <br />';
echo 'TEST 1.4: second known player Cecilia <br />';
// anonymous
/*$par = json_encode(array("playerName"=>"JP"));
$gameInstanceSetupDto = startPracticeSession($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);
*/
?>
