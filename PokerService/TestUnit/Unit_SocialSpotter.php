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

    $qConn = QueueManager::GetQueueConnection();
    $ch = QueueManager::GetChannel($qConn);
    $ex = QueueManager::GetPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

/* * ******************************************************************************* */

echo '******************************************************<br />';

echo '***** New Practice Session <br />';
$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameStatusDto = json_decode($dtoEncoded);
$gameSessionId = $gameStatusDto->gameSessionId;
$gameInstance = EntityHelper::GetGameInstance($gameStatusDto->gameInstanceId);
$activeItems = CheatingHelper::getPlayersActivelyMarking($gameInstance->gameSessionId);
echo "Active Player items before test: " . json_encode($activeItems) . "<br /><br />";
//showGameInstanceSetupValues($par, $gameStatusDto);
CheatingHelper::ResetVisible($playerId);

echo 'TEST CASE 33.1 start marking cards for JP <br /><br />';
$dto = CheatingHelper::StartCardMarking($playerId, $gameSessionId, $statusDT);
echo "startCardMarking return object (should be null): " . json_encode($dto) . "<br />";
$activeItems = CheatingHelper::getPlayersActivelyMarking($gameInstance->gameSessionId);
echo "Active Player items after starting card marking: " . json_encode($activeItems) . "<br /><br />";

echo '******************************************************<br />';
echo 'TEST CASE 33.2: Mark cards for instance <br /><br />';
$dto = CheatingHelper::MarkGameCards($gameInstance);
echo "markGameCards return object (smarkhould be null): " . json_encode($dto) . "<br />";
$dto = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
$gameCards = CardHelper::getGameCardsForInstance($gameInstance->id);
echo "visible card are (should be null): " . json_encode($dto) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "<br />";

echo '******************************************************<br />';
echo '***** Game Started on Same Practice Session <br /><br />';
$par = json_encode(array("gameSessionId" => $gameSessionId, "requestingPlayerId" => $playerId,
    "isPractice" => 1, "tableSize" => null));
echo "Parameter: $par <br />";
$gameStatusDtoEncoded = startGame($par);
$gameStatusDto = json_decode($gameStatusDtoEncoded);
$gameInstance2 = EntityHelper::GetGameInstance($gameStatusDto->gameInstanceId);
//showGameInstanceSetupValues($par, $gameStatusDto);

echo 'TEST CASE 33.3: Reveal cards on same game instance <br /><br />';
$dto = CheatingHelper::RevealMarkedCards($gameInstance);
$gameCards = CardHelper::getGameCardsForInstance($gameInstance->id);
$visibleList = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

echo 'TEST CASE 33.3: Reveal cards on next game instance <br /><br />';
$dto = CheatingHelper::RevealMarkedCards($gameInstance2);
$gameCards = CardHelper::getGameCardsForInstance($gameInstance2->id);
// the next is redundant but used to avoid the queue. remove later
$visibleList = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

QueueManager::DisconnectQueue($qConn);
?>
