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
$playerId = $row['PlayerId'];

$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameInstanceSetupDto = json_decode($dtoEncoded);
$gameSessionId = $gameInstanceSetupDto->gameSessionId;
$gameInstanceId = $gameInstanceSetupDto->gameInstanceId;
$gameInstance = EntityHelper::getGameInstance($gameInstanceId);
$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand before pushing ace: " . json_encode($playerHand) . "<br />";
global $dateTimeFormat;
$statusDT = date($dateTimeFormat);

/**********************************************************************************/

echo '******************************************************<br />';
echo 'TEST CASE 31.1 random ace <br /><br />';
$dto = CheatingHelper::pushRandomAce($playerId, $gameInstance, 1, $statusDT);
$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand after pushing ace: " . json_encode($playerHand) . "<br />";

echo '******************************************************<br />';
$par = json_encode(array("userPlayerId" => $playerId));
$dtoEncoded = startPracticeSession($par);
$gameInstanceSetupDto = json_decode($dtoEncoded);
$gameSessionId = $gameInstanceSetupDto->gameSessionId;
$gameInstanceId = $gameInstanceSetupDto->gameInstanceId;
$gameInstance = EntityHelper::getGameInstance($gameInstanceId);
$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand before pushing ace: " . json_encode($playerHand) . "<br />";

echo 'TEST CASE 31.2: cheat operation Ace Pusher <br /><br />';
$par = json_encode(array("itemType"=>ItemType::ACE_PUSHER,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId,
    "playerCardNumber"=>2));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";
$playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand after pushing ace: " . json_encode($playerHand) . "<br />";

?>
