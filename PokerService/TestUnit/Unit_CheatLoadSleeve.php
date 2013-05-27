<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT g.*, i.gameSessionId AS GameSessionId
    FROM GameCard g
    INNER JOIN Player p ON p.id = g.PlayerId
    INNER JOIN GameInstance i ON g.GameInstanceId = i.Id WHERE p.Name = 'JP'
        AND g.PlayerCardNumber = 1
    ORDER BY g.GameInstanceId desc LIMIT 1 ", 'ERROR');
$row = mysql_fetch_array($result);
$gameInstanceId = $row['GameInstanceId'];
$gameSessionId = $row['GameSessionId'];
$gameInstance = EntityHelper::getGameInstance($gameInstanceId);
$playerId = $row['PlayerId'];
echo "Test Data: Instance is $gameInstanceId and playerId $playerId for JP <br /><br />";
//$playerHand = CardHelper::getPlayerCard($playerId, $gameInstanceId, 1);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

/**********************************************************************************/

echo '******************************************************<br />';
echo 'TEST CASE 32.1 add hidden cards <br /><br />';
$cardNames = array('spades_A', 'clubs_10', 'hearts_2', 'hearts_Q');
echo "Card to be hidden: " . json_encode($cardNames) . "<br />";

$dto = CheatingHelper::addHiddenCards($playerId, $cardNames);//($gameInstanceId, $playerId, 1);
echo "Cards on sleeve returned from add operation: " . json_encode($dto) . "<br />";

echo '******************************************************<br />';
echo 'TEST CASE 32.2 add two more hidden cards <br /><br />';
$cardNames = array('clubs_9', 'diamonds_3');
echo "More cards to be hidden: " . json_encode($cardNames) . "<br />";

$dto = CheatingHelper::addHiddenCards($playerId, $cardNames);//($gameInstanceId, $playerId, 1);
echo "Cards on sleeve returned from add operation: " . json_encode($dto) . "<br />";

echo '******************************************************<br />';
echo 'TEST CASE 32.3 get cards operation <br /><br />';

$dto = CheatingHelper::getHiddenCards($playerId);
echo "Cards on sleeve from get operation: " . json_encode($dto) . "<br />";

echo '******************************************************<br />';
echo 'TEST CASE 32.4 reset sleeve operation <br /><br />';

$dto = CheatingHelper::resetSleeve($playerId);
echo "Cards on sleeve from reset operation: " . json_encode($dto) . "<br />";

QueueManager::disconnect($qConn);
?>
