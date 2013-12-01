<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

/* * ************************************************************************************* */

/**
 * An instance of two or more players (real or otherwise) being together to play a game at a point in time.
 */
class GameSession {

    public $id;
    public $casinoTableId;
    public $gameInstance;
    private $log;

    public function __construct($cTableId, $gSessionId) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->casinoTableId = $cTableId;
        $this->id = $gSessionId;
    }

    /**
     * Create and save game instance. Initialize values including identifier but there is no real data.
     * The number of players participating in this next game is set when the turns are reset.
     * @param int $tableMin The minimize bet.
     * @param type $statusDT
     * @return GameInstanceStatus
     */
    function startGameInstance($tableMin, $statusDT) {
        
        $nextInstanceId = getNextSequence('GameInstance', 'Id');
        $gameInstanceStatus = new GameInstanceStatus($nextInstanceId);
        $gameInstanceStatus->lastUpdateDateTime = $statusDT;
        // next player and turn set later per business rules
        $gameInstanceStatus->potSize = 0;
        $gameInstanceStatus->lastBetSize = 0;
        $gameInstanceStatus->numberCommunityCardsShown = 0;
        $gameInstanceStatus->lastInstancePlayNumber = 0;
        
        $gameInstanceStatus->gameInstanceSetup = new GameInstanceSetup($gameInstanceStatus->id, $this->id);
        $gameInstanceStatus->gameInstanceSetup->isPractice = 0;
        $gameInstanceStatus->gameInstanceSetup->startDateTime = $statusDT;
        $gameInstanceStatus->gameInstanceSetup->tableMinimum = $tableMin;
        // number of players set later while reseting turns.
        // dealer and first player set later per business rules
        
        executeSQL("INSERT INTO GameInstance (Id, GameSessionId, IsPractice, StartDateTime,
                LastUpdateDateTime, PotSize, LastBetSize, NumberCommunityCardsShown,
                LastInstancePlayNumber) VALUES
                ($nextInstanceId, $this->id, 0, '$statusDT', '$statusDT', 0, 0, 0, 0)
                ", __FUNCTION__ . ": ERROR inserting into GameInstance with generated id
                $nextInstanceId");
        return $gameInstanceStatus;
    }

    function communicateGameStarted($instanceSetupDto, $playerDtos, $statusDT) {
        $eventType = EventType::GAME_STARTED;
        $instanceId = $instanceSetupDto->gameInstanceId;

        for ($i = 0; $i < count($playerDtos); $i++) {
            $playerId = $playerDtos[$i]->playerId;
            //if ($playerId != $instanceSetupDto->userPlayerId) {
                $instanceSetupDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $instanceId);

                $message = new EventMessage($this->id,
                                $playerId, $eventType, $statusDT,
                                $instanceSetupDto);
                //$message->eventData = $instanceSetupDto;
                queueMessage($playerId, json_encode($message));
            //}
        }
    }


}
?>
