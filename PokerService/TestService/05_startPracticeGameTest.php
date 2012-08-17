<?php

include('../PokerPlayerService.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT ps.GameSessionId, GameInstanceId AS LastInstanceId, p.id
    FROM PlayerState ps
    INNER JOIN Player p ON p.id = ps.PlayerId
    INNER JOIN GameInstance i ON ps.GameInstanceId = i.Id WHERE p.Name = 'JP'
    ORDER BY ps.GameInstanceId desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];
$gameInstanceId = $row[1];
$playerId = $row[2];
/**********************************************************************************/

echo '******************************************************<br />';

echo 'TEST CASE 5.1: start a practice instance after instance ends <br />';
$par = json_encode(array("gameSessionId"=>$gameSessionId, "requestingPlayerId"=>$playerId, 
    "isPractice"=>1, "tableSize"=>null));
echo "Parameter: $par <br />";
$gameInstanceSetupDto = startGame($par);
showGameInstanceSetupValues($par, $gameInstanceSetupDto);

?>
