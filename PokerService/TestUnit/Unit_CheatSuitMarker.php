<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');

/* * ********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT g.*, i.GameSessionId as GameSessionId
    FROM GameCard g
    INNER JOIN Player p ON p.id = g.PlayerId
    INNER JOIN GameInstance i ON g.GameInstanceId = i.Id WHERE p.Name = 'JP'
        AND g.PlayerCardNumber = 1
    ORDER BY g.GameInstanceId desc LIMIT 1 ", 'ERROR');
$row = mysql_fetch_array($result);
$gameInstanceId = $row['GameInstanceId'];
$gameSessionId = $row['GameSessionId'];
$gameInstance = EntityHelper::GetGameInstance($gameInstanceId);
$playerId = $row['PlayerId'];
echo "Test Data: Instance is $gameInstanceId and playerId $playerId for JP <br /><br />";
$gameCards = new GameInstanceCards($gameInstance->id);
$playerHand = $gameCards->GetPlayerGameCard($playerId, 1);
global $dateTimeFormat;
$statusDT = date($dateTimeFormat);

$qConn = QueueManager::GetConnection();
$ch = QueueManager::GetChannel($qConn);
$ex = QueueManager::GetPlayerExchange($ch);
$q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

/* * ******************************************************************************* */

echo '******************************************************<br />';
echo 'TEST CASE 30.1 suit markers <br /><br />';
$gameCards->GetSavedCards();
echo "initial player cards: <br />";
echo json_encode($gameCards->playerHands) . "<br />";
$cheatingItemH = new CheatingItem($playerId, $gameInstance->gameSessionId, 'hearts');
$hearts = $cheatingItemH->ApplySuitMarker($gameInstance);
echo "hearts on game: <br />";
echo json_encode($hearts) . "<br />";
$cheatingItemC = new CheatingItem($playerId, $gameInstance->gameSessionId, 'clubs');
$clubs = $cheatingItemC->ApplySuitMarker($gameInstance);
echo "clubs on game: <br />";
echo json_encode($clubs) . "<br />";
$cheatingItemD = new CheatingItem($playerId, $gameInstance->gameSessionId, 'diamonds');
$diamonds = CheatingHelper::ApplySuitMarker($gameInstance);
echo "diamonds on game: <br />";
echo json_encode($diamonds) . "<br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Heart Marker <br /><br />';
$par = json_encode(array("itemType" => ItemType::HEART_MARKER,
	"userPlayerId" => $playerId,
	"gameSessionId" => $gameSessionId,
	"gameInstanceId" => $gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Club Marker <br /><br />';
$par = json_encode(array("itemType" => ItemType::CLUB_MARKER,
	"userPlayerId" => $playerId,
	"gameSessionId" => $gameSessionId,
	"gameInstanceId" => $gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Diamond Marker <br /><br />';
$par = json_encode(array("itemType" => ItemType::DIAMOND_MARKER,
	"userPlayerId" => $playerId,
	"gameSessionId" => $gameSessionId,
	"gameInstanceId" => $gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

QueueManager::DisconnectQueue($qConn);
?>
