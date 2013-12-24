<?php

include_once(dirname(__FILE__) . '/../../Libraries/Helper/DataHelper.php');
include_once(dirname(__FILE__) . '/../PokerService/DomainHelper/AllInclude.php');
include_once(dirname(__FILE__) . '/../PokerService/DomainModel/AllInclude.php');
include_once(dirname(__FILE__) . '/../PokerService/DomainEnhanced/AllInclude.php');
include_once(dirname(__FILE__) . '/../PokerService/Dto/AllInclude.php');
include_once(dirname(__FILE__) . '/../PokerService/Metadata.php');
include_once(dirname(__FILE__) . '/../PokerService/Config.php');

connectToStateDB();

$playerName = $_POST["playerName"];
$suitType = $_POST["suitType"];
$actionType = $_POST["actionType"];
$gameSessionId = $_POST["gameSessionId"];
$qConn = QueueManager::GetConnection();
$ch = QueueManager::GetChannel($qConn);
$ex = QueueManager::GetPlayerExchange($ch);
$q = QueueManager::AddPlayerQueue($playerId, $ch);

if ($suitType != null && $actionType != null) {
    $player = Player::getPlayerByName($playerName);
    switch ($suitType) {
        case 'hearts':
            $cardCodes = array('2h', '3h', '4h', '5h', '6h', '7h', '8h', '9h', 'Th', 'Jh', 'Qh', 'Kh', 'Ah');
            break;
        case 'clubs':
            $cardCodes = array('2c', '3c', '4c', '5c', '6c', '7c', '8c', '9c', 'Tc', 'Jc', 'Qc', 'Kc', 'Ac');
            break;
        case 'diamonds':
            $cardCodes = array('2d', '3d', '4d', '5d', '6d', '7d', '8d', '9d', 'Td', 'Jd', 'Qd', 'Kd', 'Ad');
            break;
        case 'spades':
            $cardCodes = array('2s', '3s', '4s', '5s', '6s', '7s', '8s', '9s', 'Ts', 'Js', 'Qs', 'Ks', 'As');
            break;
    }
    if ($actionType == 'add') {
		$gameInstance = GameInstance::GetSessionLastInstance($gameSessionId);
        CheatingHelper::AddVisibleCards ($gameInstance);
        echo "Successfully marked $suitType cards for $playerName for 10 minutes";
    } elseif ($actionType == 'remove') {
		$visibles = new PlayerVisibleCard($player->id, $gameSessionId);
        $visibles->removeVisibleCardCodes($cardCodes);
        echo "Successfully removed $suitType cards as marked cards for $playerName";
    }
    QueueManager::disconnect($qConn);
}
?>
