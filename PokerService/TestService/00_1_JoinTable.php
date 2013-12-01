<?php

include('../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
/*
mysql_query("Delete from Player WHERE Name in ('Test1', 'Test2', 'Test3', 'Test4', 'Test5', 'Test6')");
mysql_query("Delete from PlayerState ps WHERE PlayerId not in (select id from player)");
*/
$casinoTableId = 1;
$name = 'Test8';
$par = json_encode(array("casinoTableId"=>$casinoTableId, "playerName"=>$name, "tableSize"=>null));
    echo "Encoded parameter: $par <br /><br />";
$gameStatusDtoEncoded = addUserToCasinoTable($par);
    echo "Encoded return object: $gameStatusDtoEncoded <br /> <br />";
$gameStatusDto = json_decode($gameStatusDtoEncoded);


?>
