<?php

/* copy/paste portions of regression tests */
include('../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../../../libraries/helper/DataHelper.php');

include_once("CleanUpGameSessionById.php");
include_once("CleanUpPlayerById.php");
include_once("CleanUpOrphanCasino.php");
$conTest = connectToStateDB();

cleanUpGameSessionById($conTest, 1);
cleanUpPlayerById($conTest, 1);
cleanUpPlayerById($conTest, 2);
cleanUpPlayerById($conTest, 3);
cleanUpPlayerById($conTest, 4);
cleanUpOrphanCasino($conTest);
$qConn = QueueManager::GetConnection();
$qCh = QueueManager::GetChannel($qConn);
$qEx = QueueManager::GetPlayerExchange($qCh);

$q1 = QueueManager::GetPlayerQueue(1, $qCh);
$q2 = QueueManager::GetPlayerQueue(2, $qCh);
$q3 = QueueManager::GetPlayerQueue(3, $qCh);
$q4 = QueueManager::GetPlayerQueue(4, $qCh);
echo "CleanUp: deleting queues 1, 2, 3, 4 <br />";
$q1->delete();
$q2->delete();
$q3->delete();
$q4->delete();

/*
echo "beginning<br/>";
echo "exec time " . ini_get('max_execution_time') . "<br/>";
ini_set('max_execution_time', 120);
sleep(90);
echo "exec time " . ini_get('max_execution_time') . "<br/>";
echo "end<br/>";
*/
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
