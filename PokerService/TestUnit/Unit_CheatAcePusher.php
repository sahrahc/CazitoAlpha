<?php

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
global $dateTimeFormat;
$statusDT = date($dateTimeFormat);

    $qConn = QueueManager::GetConnection();
    $ch = QueueManager::GetChannel($qConn);
    $ex = QueueManager::GetPlayerExchange($ch);
    $q = QueueManager::addOrResetPlayerQueue($playerId, $ch);

    $dto = CheatingHelper::PushRandomAce($playerId, $gameInstance, 1, $statusDT, ItemType::ACE_PUSHER);
    
    $playerHand = CardHelper::getPlayerHandDto($playerId, $gameInstanceId);
echo "Player hand after pushing ace: " . json_encode($playerHand) . "<br />";


?>
