<?php
include_once(dirname(__FILE__) . '/../Helper/DataHelper.php');

$conT = connectToStateDB();
mysql_query("Drop table PlayerActiveItem");
mysql_query("Drop table PlayerHiddenCard");
mysql_query("Drop table PlayerVisibleCard");
?>
