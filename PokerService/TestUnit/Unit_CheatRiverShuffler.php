<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
include('showObject.php');

/* * ********************************************************************************
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
$playerId = $row['PlayerId'];

global $dateTimeFormat;
$statusDT = date($dateTimeFormat);

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

/* * ******************************************************************************* */

echo '******************************************************<br />';
echo '***** New Practice Session <br />';

$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameInstanceSetupDto = json_decode($dtoEncoded);
$gameSessionId = $gameInstanceSetupDto->gameSessionId;
$gameInstance = EntityHelper::getGameInstance($gameInstanceSetupDto->gameInstanceId);
$cCards = CardHelper::getCommunityCardDtos($gameInstance->id, 5);
echo "Community card before look: " . json_encode($cCards) . "<br /><br />";

echo 'TEST CASE 34.1 swap before looking not allowed<br /><br />';
$dto = CheatingHelper::cheatLookRiverCard($playerId, $gameInstance, $statusDT);
$cCards = CardHelper::getCommunityCardDtos($gameInstance->id, 5);
echo "Community card after looking (no change): " . json_encode($cCards) . "<br /><br />";

echo '******************************************************<br />';
echo 'TEST CASE 34.2: Look and swap river cards for instance <br /><br />';

$dto = CheatingHelper::cheatSwapRiverCard($playerId, $gameInstance);
$cCards = CardHelper::getCommunityCardDtos($gameInstance->id, 5);
echo "Community card after swapping: " . json_encode($cCards) . "<br /><br />";

QueueManager::disconnect($qConn);
?>
