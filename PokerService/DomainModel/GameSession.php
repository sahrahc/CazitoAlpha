<?php

/* * ************************************************************************************* */

/**
 * An instance of two or more players (real or otherwise) being together to play a game at a point in time.
 */
class GameSession {

    public $id;
    public $requestingPlayerId;
    public $isCheatingAllowed;
    // transient only
    private $log;
    public $isPractice = false;
    
    public function __construct($gSessionId, $playerId) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = (int)$gSessionId;
        $this->requestingPlayerId = (int)$playerId;
    }

    /**
     * Create and save game instance. Initialize values including identifier but there is no real data.
     * The number of players participating in this next game is set when the turns are reset.
     * @param int $tableMin The minimize bet.
     * @param type $statusDT
     * @return GameInstance
     */
    public function InitNewLiveGameInstance() {
        $statusDT = Context::GetStatusDT();

        $nextInstanceId = getNextSequence('GameInstance', 'Id');
        $gameInstance = new GameInstance($nextInstanceId);
        $gameInstance->gameSessionId = $this->id;
        $gameInstance->status = GameStatus::STARTED;
        $gameInstance->startDateTime = $statusDT;
        $gameInstance->lastUpdateDateTime = $statusDT;
        // number of players set later while reseting turns.
        // dealer and first player set later per business rules
        $gameInstance->currentPotSize = 0;
        $gameInstance->lastBetSize = 0;
        $gameInstance->numberCommunityCardsShown = 0;
        $gameInstance->lastInstancePlayNumber = 0;

        $gameInstance->Insert();
        return $gameInstance;
    }

    /**
     * Communicates game started to the list of recipients. 
     * @param type $instanceSetupDto
     * @param Player[] $recipientPlayers Cannot be PlayerInstance because waiting players need to be communicated
     */
    function CommunicateGameStarted($gameStatusDto, $recipientPlayers) {
        $QEx = Context::GetExchangePlayer();

        $eventType = EventType::GameStarted;
        $instanceId = $gameStatusDto->gameInstanceId;

        for ($i = 0; $i < count($recipientPlayers); $i++) {
            $playerId = $recipientPlayers[$i]->id;
            $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $instanceId);

            $message = new QueueMessage($eventType, $gameStatusDto);
            //$message->eventData = $instanceSetupDto;
            QueueManager::SendToPlayer($QEx, $playerId, json_encode($message));
            //}
        }
    }

}
?>
