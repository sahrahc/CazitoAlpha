<?php
include_once(dirname(__FILE__) . '/../../Libraries/Helper/DataHelper.php');

$conT = connectToStateDB();
mysql_query("Drop table CasinoTable");
mysql_query("Drop table Player");
mysql_query("Drop table GameInstance");
mysql_query("Drop table GameSession");
mysql_query("Drop table PlayerState");
mysql_query("Drop table GameCard");
mysql_query("Drop table PlayerAction");
mysql_query("Drop table NextPokerMove");
mysql_query("Drop table EventMessage");
?>
