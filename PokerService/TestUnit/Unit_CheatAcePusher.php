<?php

require_once('../PokerPlayerService.php');
require_once(dirname(__FILE__) . '/../Metadata.php');
require_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

echo __FILE__ . "<br />";
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

    $qConn = QueueManager::getPlayerConnection();
    $ch = QueueManager::getPlayerChannel($qConn);
    $ex = QueueManager::getPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

    $dto = CheatingHelper::pushRandomAce($playerId, $gameInstance, 1, $statusDT);
    
    $playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand after pushing ace: " . json_encode($playerHand) . "<br />";


?>
