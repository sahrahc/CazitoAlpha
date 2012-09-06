<?php

include_once(dirname(__FILE__) . '/../DomainHelper/EntityHelper.php');
include_once(dirname(__FILE__) . '/../Metadata.php');

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

/* * ************************************************************************************* */

class PracticeSession {

    public $id;
    public $startDateTime;
    public $tableMinimum;
    public $numberSeats;
    // transient data */
    public $currentInstanceId;
    public $playerStatusDtos = array();
    public $blindBets;
    private $log;

    public function __construct($gSessionId, $curInstId, $statusDT) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $gSessionId;
        $this->currentInstanceId = $curInstId;
        $this->startDateTime = $statusDT;
    }

    /**
     * Creates the player state for the user and the virtual players.
     * @global type $defaultTableMin
     * @global type $buyInMultiplier
     * @global type $defaultAvatarUrl
     * @param type $pNumber
     * @param type $pId
     * @param type $pName
     * @param type $statusDT 
     */
    function savePracticePlayer($pNumber, $pId, $pName, $statusDT) {
        global $defaultTableMin;
        global $buyInMultiplier;
        global $defaultAvatarUrl;

        $blindBet = 0;
        $stake = $defaultTableMin * $buyInMultiplier;
        $playAmount = 'null';
        if ($pNumber == 1) {
            $blindBet = $defaultTableMin / 2;
            $stake = $stake - $blindBet;
            $playAmount = $blindBet;
        }
        if ($pNumber == 2) {
            $blindBet = $defaultTableMin;
            $stake = $stake - $blindBet;
            $playAmount = $blindBet;
        }
        $initialStatus = PlayerStatusType::WAITING;
        $isVirtual = 1;
        if ($pNumber == 0) {
            $isVirtual = 0;
            // update the seat number
            executeSQL("UPDATE Player set CurrentSeatNumber = 0 WHERE ID = $pId", __FUNCTION__ . ":
                Error updating player id $pId to seat number 0 for practice session");
        }
        executeSQL("INSERT INTO PlayerState (
                GameSessionId, GameInstanceId, PlayerId, IsVirtual,
                LastUpdateDateTime, SeatNumber, TurnNumber, Status, BlindBet,
                Stake, LastPlayAmount, PlayerPlayNumber, NumberTimeOuts)
                VALUES ($this->id, $this->currentInstanceId, $pId, $isVirtual,
                '$this->startDateTime', $pNumber, $pNumber, '$initialStatus',  $blindBet,
                $stake, 0, 0, 0)
                ", __FUNCTION__ . "
                : Error inserting first practice Player for practice session id $this->id");

        $playerInstance = new PlayerInstanceStatus();
        $playerInstance->playerId = $pId;
        $playerInstance->gameInstanceId = $this->currentInstanceId;
        $playerInstance->lastUpdateDateTime = $statusDT;
        $playerInstance->status = PlayerStatusType::WAITING;
        $playerInstance->stake = $stake;
        $playerInstance->lastPlayAmount = $playAmount;
        $playerInstance->playerPlayNumber = 0;
        $playerInstance->numberTimeOuts = 0;
        $playerInstance->playerInstanceSetup = new PlayerInstanceSetup();
        $playerInstance->playerInstanceSetup->playerId = $pId;
        $playerInstance->playerInstanceSetup->isVirtual = 1;
        $playerInstance->playerInstanceSetup->playerName = $pName;
        $playerInstance->playerInstanceSetup->playerImageUrl = $defaultAvatarUrl;
        $playerInstance->playerInstanceSetup->gameSessionId = $this->id;
        $playerInstance->playerInstanceSetup->gameInstanceId = $this->currentInstanceId;
        $playerInstance->playerInstanceSetup->seatNumber = $pNumber;
        $playerInstance->playerInstanceSetup->turnNumber = $pNumber;
        $playerInstance->playerInstanceSetup->blindBet = $blindBet;
        $playerState = new PlayerStatusDto($playerInstance);
        $this->playerStatusDtos = array_merge($this->playerStatusDtos, array($playerState));
    }

    /**
     * Generates and saves the virtual players for the practice session.
     * @param type $statusDT 
     */
    function addDummyPlayersAndBlindBets($statusDT) {
        $playerName = 'Practice 1 - ' . $this->id;
        $player1 = EntityHelper::createPracticePlayer($playerName, 1, $this->startDateTime);
        $this->savePracticePlayer(1, $player1->playerId, $playerName, $statusDT);

        $playerName = 'Practice 2 - ' . $this->id;
        $player2 = EntityHelper::createPracticePlayer($playerName, 2, $this->startDateTime);
        $this->savePracticePlayer(2, $player2->playerId, $playerName, $statusDT);

        $playerName = 'Practice 3 - ' . $this->id;
        $player3 = EntityHelper::createPracticePlayer($playerName, 3, $this->startDateTime);
        $this->savePracticePlayer(3, $player3->playerId, $playerName, $statusDT);

        // add blind bets
        $this->blindBets = array(new BetDto($this->playerStatusDtos[1]->playerId, $this->playerStatusDtos[1]->blindBet),
            new BetDto($this->playerStatusDtos[2]->playerId, $this->playerStatusDtos[2]->blindBet));
    }

    /**
     * Indirectly called on practice games.
     * @param type $pId
     * @param type $gInstanceId
     * @param type $statusDT
     * @param type $check
     * @param type $call
     * @param type $raise
     * @return PlayerActionDto 
     */
    function generateRandomAction($pId, $gInstanceId, $statusDT, $check, $call, $raise) {
        /* --------------------------------------------------------------------- */
        // generate a random action from among the allowed actions.
        $pokerActionOptions = array(PokerActionType::RAISED,
            PokerActionType::CALLED); //, PokerActionType::FOLD);
        if (!is_null($check)) {
            // adding the check option if available
            $pokerActionOptions = array_merge($pokerActionOptions, array(PokerActionType::CHECKED));
        }
        $action = $pokerActionOptions[rand(0, count($pokerActionOptions) - 1)];
        $value = null;
        if ($action == PokerActionType::RAISED) {
            $value = $raise;
        } else if ($action == PokerActionType::CALLED) {
            $value = $call;
        };
        $playerActionDto = new PlayerActionDto($gInstanceId, $pId, $action,
                        $statusDT, $value);

        return $playerActionDto;
    }

    /**
     * Stores the practice session configuration values used once the session is ready for play (must be called last).
     */
    function savePracticeSession() {
        $nextId = getNextSequence('GameSession', 'Id');
        executeSQL("INSERT INTO GameSession (Id, StartDateTime, TableMinimum, NumberSeats,
                IsPractice) VALUES ($nextId, $this->startDateTime, $this->tableMinimum,
                $numberSeats, 1), ", __FUNCTION__ . ": ERROR insert into practice session");
        $this->id = $nextId;
    }

    /**
     *
     * @param type $instanceSetupDto
     */
    function communicateGameStarted($instanceSetupDto, $statusDT) {
        global $dateTimeFormat;
        
        $eventType = EventType::GAME_STARTED;
        $instanceId = $instanceSetupDto->gameInstanceId;

        $playerId = $instanceSetupDto->userPlayerId;

        $localTime = date($dateTimeFormat, strtotime($statusDT));

        $message = new EventMessage($this->id,
                        $playerId, $eventType, $localTime,
                        $instanceSetupDto);
        queueMessage($playerId, json_encode($message));
    }

}

?>
