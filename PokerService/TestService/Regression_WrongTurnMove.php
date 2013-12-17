<?php

echo __FILE__ . "<br />";

//////////////////////////
$name = 'Test0';
//////////////////////////

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
/*
 * exceptions - regression smoke have two four users join and go through one 
 * round of play
 * all exceptions are hacking conditions
 * - wrong turn
 * - check on first round
 * - player not playing on table
 * - wrong call, raise amount
 * - attempt move after fold
 * - attempt move after left
 * - move on ended game
 * - move on unknown session
 * - user on waiting list attempts move
 */
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
$row = mysql_fetch_array($result, MYSQL_NUM);
$gameInstanceId = $row[0];
$playerId = $row[1];
/**********************************************************************************/

echo '******************************************************<br />';
echo 'TEST CASE 2.1: first move sendPlayerAction with practice session <br />';

global $dateTimeFormat;
$date = date($dateTimeFormat);
$playerAction = new PlayerAction($gameInstanceId, $playerId, PokerActionType::CHECKED,
        $date, 300);

$par = json_encode($playerAction);
echo "parameter $par <br />";
$actionResultArray = sendPlayerAction($par);
showPokerMove($par, $actionResultArray);

?>
