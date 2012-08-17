<?php

include('..\PokerPlayerService.php');
include('showObject.php');

$conT = connectToStateDB();
mysql_query("Delete from casinotable");
mysql_query("delete from GameCard");
mysql_query("Delete from GameState");
mysql_query("Delete from Player");
mysql_query("Delete from PlayerState");

echo '******************************************************<br />';

echo 'TEST: unknown player JP <br />';
$par = json_encode(array("playerName"=>"JP", "isPractice"=>1, "casinoTableId"=>5));
    $casinoTableDtoEncoded = addUserToGamingSession($par);
showCasinoTableValues($par, $casinoTableDtoEncoded);

echo '****************************************************** <br />';
echo 'TEST: known player JP <br />';
// anonymous
$par = json_encode(array("playerName"=>"JP", "isPractice"=>0, "casinoTableId"=>null));
    $casinoTableDtoEncoded = addUserToGamingSession($par);
showCasinoTableValues($par, $casinoTableDtoEncoded);

echo '****************************************************** <br />';
echo 'TEST: known player JP <br />';
// anonymous
$par = json_encode(array("playerName"=>"Cecilia", "isPractice"=>0, "casinoTableId"=>2));
    $casinoTableDtoEncoded = addUserToGamingSession($par);
showCasinoTableValues($par, $casinoTableDtoEncoded);

echo '****************************************************** <br />';
echo 'TEST: known player JP <br />';
// anonymous
$par = json_encode(array("playerName"=>"JP", "isPractice"=>1, "casinoTableId"=>null));
showCasinoTableValues($par);

?>
