<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT GameInstanceId AS LastInstanceId, NextPlayerId
    FROM PlayerState ps
    INNER JOIN Player p ON p.id = ps.PlayerId
    INNER JOIN GameInstance i ON ps.GameInstanceId = i.Id WHERE p.Name = 'Charles'
    ORDER BY ps.GameInstanceId desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameInstanceId = $row[0];
$playerId = $row[1];
/**********************************************************************************/

echo '******************************************************<br />';
echo 'TEST CASE 13.1: sendPlayerAction with recycled game session <br />';

global $dateTimeFormat;
$date = date($dateTimeFormat);
$playerActionDto = new PlayerActionDto($gameInstanceId, $playerId, PokerActionType::CALLED,
        $date, 10000);

$par = json_encode($playerActionDto);
echo "parameter $par <br />";
$actionResultArray = sendPlayerAction($par);
showPokerMove($par, $actionResultArray);

?>
