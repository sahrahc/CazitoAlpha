<?php

echo __FILE__ . "<br />";

//////////////////////////
$name = 'Test0';
//////////////////////////

include(dirname(__FILE__) . '/../TimerService.php');
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
    INNER JOIN GameInstance i ON ps.GameInstanceId = i.Id WHERE p.Name = 'JP'
    ORDER BY ps.GameInstanceId desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameInstanceId = $row[0];
$playerId = $row[1];

echo '******************************************************<br />';
echo 'TEST CASE 3.1: first move skip Turn Practice Player 1 <br />';

/* get previous move */
/**********************************************************************************/
$objBefore = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstanceId);
echo "Row before: " . json_encode($objBefore) . "<br />";

ProcessExpiredPokerMoves();
// FIXME: sleep while 30 seconds or there is a message;
/**********************************************************************************/
/* get new move and compare it is different than previous move */

$objAfter = ExpectedPokerMove::GetExpectedMoveForInstance($gameInstanceId);
echo "Row after: " . json_encode($objAfter) . "<br />";

/* check message:  player status changed */

/**********************************************************************************/
/* skip 2 more times
 * send play for user
 * verify community cards changed and nothing else
 */
?>
