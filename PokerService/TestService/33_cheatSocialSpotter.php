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
/* * ******************************************************************************* */

echo '******************************************************<br />';

echo '***** New Practice Session <br />';
$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameInstanceSetupDto = json_decode($dtoEncoded);
$gameSessionId = $gameInstanceSetupDto->gameSessionId;
$gameInstance = EntityHelper::getGameInstance($gameInstanceSetupDto->gameInstanceId);
$activeItems = CheatingHelper::getPlayersActivelyMarking($gameInstance->gameInstanceSetup->gameSessionId);
echo "Active Player items before test: " . json_encode($activeItems) . "<br /><br />";
//showGameInstanceSetupValues($par, $gameInstanceSetupDto);
CheatingHelper::resetVisible($playerId);

echo 'TEST CASE 33.1 start marking cards for JP <br /><br />';
$dto = CheatingHelper::startCardMarking($playerId, $gameSessionId, $statusDT);
echo "startCardMarking return object (should be null): " . json_encode($dto) . "<br />";
$activeItems = CheatingHelper::getPlayersActivelyMarking($gameInstance->gameInstanceSetup->gameSessionId);
echo "Active Player items after starting card marking: " . json_encode($activeItems) . "<br /><br />";

echo '******************************************************<br />';
echo 'TEST CASE 33.2: Mark cards for instance <br /><br />';
$dto = CheatingHelper::markGameCards($gameInstance);
echo "markGameCards return object (should be null): " . json_encode($dto) . "<br />";
$dto = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
$gameCards = $gameInstance->getInstanceGameCards();
echo "visible card are (should be null): " . json_encode($dto) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "<br />";

echo '******************************************************<br />';
echo '***** Game Started on Same Practice Session <br /><br />';
$par = json_encode(array("gameSessionId" => $gameSessionId, "requestingPlayerId" => $playerId,
    "isPractice" => 1, "tableSize" => null));
echo "Parameter: $par <br />";
$instanceSetupDtoEncoded = startGame($par);
$gameInstanceSetupDto = json_decode($instanceSetupDtoEncoded);
$gameInstance2 = EntityHelper::getGameInstance($gameInstanceSetupDto->gameInstanceId);
//showGameInstanceSetupValues($par, $gameInstanceSetupDto);

echo 'TEST CASE 33.3: Reveal cards on same game instance <br /><br />';
$dto = CheatingHelper::revealMarkedCards($gameInstance);
$gameCards = $gameInstance->getInstanceGameCards();
$visibleList = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

echo 'TEST CASE 33.3: Reveal cards on next game instance <br /><br />';
$dto = CheatingHelper::revealMarkedCards($gameInstance2);
$gameCards = $gameInstance2->getInstanceGameCards();
$visibleList = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
echo "... and should match instance's community cards: " . json_encode($gameCards->communityCards) . "<br />";
echo "... plus the instance's player cards" . json_encode($gameCards->playerHands) . "br />";

echo '******************************************************<br />';
echo 'TEST CASE 33.4: Start marking cards via service (other operations to be tested via UI <br /><br />';
$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameInstanceSetupDto = json_decode($dtoEncoded);
$gameSessionId = $gameInstanceSetupDto->gameSessionId;
$gameInstance3 = EntityHelper::getGameInstance($gameInstanceSetupDto->gameInstanceId);

$par = json_encode(array("itemType" => ItemType::SOCIAL_SPOTTER,
    "userPlayerId" => $playerId,
    "gameSessionId" => $gameSessionId,
    "gameInstanceId" => $gameInstance3->id));
$returnDto = cheat($par);
$visibleList = CheatingHelper::getVisibleCardCodes($playerId, $gameSessionId);
echo "Visible cards " . json_encode($visibleList) . "<br />";
?>
