<?php

include('../PokerPlayerService.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT ps.GameSessionId, NextPlayerId
    FROM PlayerState ps
    INNER JOIN Player p ON p.id = ps.PlayerId
    INNER JOIN GameInstance i ON ps.GameInstanceId = i.Id WHERE p.Name = 'MM'
    ORDER BY ps.GameInstanceId desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];
$playerId = $row[1];

echo '****************************************************** <br />';
echo 'TEST CASE 7.1: restart a new game instance for casino 1 <br />';
$par = json_encode(array("gameSessionId"=>$gameSessionId,
    "requestingPlayerId"=>$playerId,
    "isPractice"=>0, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

?>
