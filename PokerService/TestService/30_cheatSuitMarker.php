<?php

include(dirname(__FILE__) . '/../PokerPlayerService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT g.*, i.GameSessionId as GameSessionId
    FROM GameCard g
    INNER JOIN Player p ON p.id = g.PlayerId
    INNER JOIN GameInstance i ON g.GameInstanceId = i.Id WHERE p.Name = 'JP'
        AND g.CardNumber = 1
    ORDER BY g.GameInstanceId desc LIMIT 1 ", 'ERROR');
$row = mysql_fetch_array($result);
$gameInstanceId = $row['GameInstanceId'];
$gameSessionId = $row['GameSessionId'];
$gameInstance = EntityHelper::getGameInstance($gameInstanceId);
$playerId = $row['PlayerId'];
echo "Test Data: Instance is $gameInstanceId and playerId $playerId for JP <br /><br />";
$playerHand = CardHelper::getPlayerHand($playerId, $gameInstanceId, 1);
/**********************************************************************************/

echo '******************************************************<br />';
echo 'TEST CASE 30.1 suit markers <br /><br />';
$gameCards = $gameInstance->getAllGameCards();
echo "initial player cards: <br />";
echo json_encode($gameCards->playerHands) . "<br />";
$hearts = CheatingHelper::getSuitForAllGameCards($gameInstance, 'hearts');
echo "hearts on game: <br />";
echo json_encode($hearts) . "<br />";
$clubs = CheatingHelper::getSuitForAllGameCards($gameInstance, 'clubs');
echo "clubs on game: <br />";
echo json_encode($clubs) . "<br />";
$spades = CheatingHelper::getSuitForAllGameCards($gameInstance, 'spades');
echo "spades on game: <br />";
echo json_encode($spades) . "<br />";
$diamonds = CheatingHelper::getSuitForAllGameCards($gameInstance, 'diamonds');
echo "diamonds on game: <br />";
echo json_encode($diamonds) . "<br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Heart Marker <br /><br />';
$par = json_encode(array("itemType"=>ItemType::HEART_MARKER,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Club Marker <br /><br />';
$par = json_encode(array("itemType"=>ItemType::CLUB_MARKER,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

echo '******************************************************<br />';

echo 'TEST CASE 30.2: cheat operation Diamond Marker <br /><br />';
$par = json_encode(array("itemType"=>ItemType::DIAMOND_MARKER,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

?>
