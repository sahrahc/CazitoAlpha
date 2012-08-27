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
echo 'TEST CASE 31.1 random ace <br /><br />';
echo "initial player hand: " . json_encode($playerHand) . "<br />";
$dto = CheatingHelper::pushRandomAce($gameInstanceId, $playerId, 1);
echo "player hand after pushing ace: " . json_encode($dto) . "<br />";

echo '******************************************************<br />';

echo 'TEST CASE 31.2: cheat operation Ace Pusher <br /><br />';
$par = json_encode(array("itemType"=>ItemType::ACE_PUSHER,
    "userPlayerId"=>$playerId,
    "gameSessionId"=>$gameSessionId,
    "gameInstanceId"=>$gameInstanceId,
    "cardNumber"=>1));
$returnDto = cheat($par);
echo "Parameter: " . $par . "<br />";
echo "Result: " . $returnDto . "<br /><br />";

?>
