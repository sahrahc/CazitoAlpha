<?php

$conTest = connectToStateDB();


require_once('../PokerPlayerService.php');
require_once(dirname(__FILE__) . '/../Metadata.php');
require_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');


echo __FILE__ . "<br />";
$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);

$gameStatusDto = json_decode($dtoEncoded);
$gameSessionId = $gameStatusDto->gameSessionId;
$gameInstanceId = $gameStatusDto->gameInstanceId;

$gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);

echo "Player hand before pushing ace: " . json_encode($playerHand) . "<br />";
// next line used for cheat suit marker - try not using?
//$playerHand = CardHelper::getPlayerCard($playerId, $gameInstanceId, 1);
//$gameCards = $gameInstance->getInstanceGameCards();
//echo "initial player cards: <br />";
//echo json_encode($gameCards->playerHands) . "<br />";

global $dateTimeFormat;
$statusDT = date($dateTimeFormat);

    $qConn = QueueManager::GetConnection();
    $ch = QueueManager::GetChannel($qConn);
    $ex = QueueManager::GetPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

echo "Result: " . $q->get(AMQP_AUTOACK) . "<br /><br />";

// suit market test
$param_suitType = $_POST[ItemType::HEART_MARKER];
include('Feature_CheatSuitMarker.php');
$param_suitType = $_POST[ItemType::CLUB_MARKER];
include('Feature_CheatSuitMarker.php');
$param_suitType = $_POST[ItemType::DIAMOND_MARKER];
include('Feature_CheatSuitMarker.php');

// river shuffler test

$cCards = CardHelper::getCommunityCardDtos($gameInstanceId, 5);
echo "Community card after swap: " . json_encode($cCards) . "<br /><br />";

// social spotter test - assume new user
$itemType = ItemType::SOCIAL_SPOTTER;
$activeItems = PlayerActiveItem::GetPlayersWithItemType($gameInstance->gameSessionId, $itemType);
echo "Active Player items before test: " . json_encode($activeItems) . "<br /><br />";
/*
 * 1) cheat operation (
 * 2) startGame/endGame (reveal markedCard)
 * 3) startGame/endGame (more cards
 * 4) login (reset)
 * 5) startGame (reset)
 * 6) endGame (reveal new cards only)
 */
// after start game
$gameInstance2 = EntityHelper::GetGameInstance($gameStatusDto->gameInstanceId);
$gameCards = CardHelper::getGameCardsForInstance($gameInstance2->id);
echo "visible card are (should be null): " . json_encode($dto) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "<br />";
// after end game
$visibleList = PlayerVisibleCard::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";
$visibleList = PlayerVisibleCard::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";

?>
