<?php

include('../PokerPlayerService.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT currentgamesessionid, p.id
    from player p inner join casinotable c on p.currentcasinotableid = c.id
    where currentcasinotableid = 1
    ORDER BY id desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];
$playerId = $row[1];

echo '****************************************************** <br />';
echo 'Front-End Testing - Restart a new instance for known casino <br />';
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$playerId,
    "isPractice"=>0, "tableSize"=>1000));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

?>
