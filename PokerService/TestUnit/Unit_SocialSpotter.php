<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');

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

    $qConn = QueueManager::GetConnection();
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
$itemType = ItemType::SOCIAL_SPOTTER;
$activeItems = CheatingHelper::GetPlayersWithItemType($gameInstance->gameSessionId, $itemType);
echo "Active Player items before test: " . json_encode($activeItems) . "<br /><br />";
//showGameInstanceSetupValues($par, $gameStatusDto);
$visible = new PlayerVisibleCards($playerId);
$visible->ResetVisible();

echo 'TEST CASE 33.1 start marking cards for JP <br /><br />';
$dto = CheatingHelper::ApplySocialSpotter($playerId, $gameSessionId, $statusDT, ItemType::SOCIAL_SPOTTER);
echo "startCardMarking return object (should be null): " . json_encode($dto) . "<br />";
$activeItems = CheatingHelper::GetPlayersWithItemType($gameInstance->gameSessionId, $itemType);
echo "Active Player items after starting card marking: " . json_encode($activeItems) . "<br /><br />";

echo '******************************************************<br />';
echo 'TEST CASE 33.2: Mark cards for instance <br /><br />';
$dto = CheatingHelper::MarkGameCards($gameInstance);
echo "markGameCards return object (smarkhould be null): " . json_encode($dto) . "<br />";
$visibles = new PlayerVisibleCards($playerId, $gameSessionId);
$dto = $visibles->GetSavedCardCodes();
$gameCards = new GameInstanceCards($gameInstance->id);
$gameCards->GetSavedCards();
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
$gameCards = new GameInstanceCards($gameInstance->id);
$gameCards->GetSavedCards();
$visibles = new PlayerVisibleCards($playerId, $gameSessionId);
$visibleList = $visibles->GetSavedCardCodes();
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

echo 'TEST CASE 33.3: Reveal cards on next game instance <br /><br />';
$dto = CheatingHelper::RevealMarkedCards($gameInstance2);
$gameCards = new GameInstanceCards($gameInstance2->id);
$gameCards->GetSavedCards();
// the next is redundant but used to avoid the queue. remove later
$visibles = new PlayerVisibleCards($playerId, $gameSessionId);
$visibleList = $visibles->GetSavedCardCodes();
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

QueueManager::DisconnectQueue($qConn);
?>
