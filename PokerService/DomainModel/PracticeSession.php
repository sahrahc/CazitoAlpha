<?php

include_once(dirname(__FILE__) . '/../DomainHelper/EntityHelper.php');
include_once(dirname(__FILE__) . '/../Metadata.php');

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

/* * ************************************************************************************* */

/**
 * TODO: set up as child of GameSession
 */
class PracticeSession {

    public $id;
    public $requestingPlayerId; // the player for whom the practice session is created
    public $startDateTime;
    public $tableMinimum;
    public $numberSeats;
    // transient data */
    public $isPractice = true;
    private $log;

    public function __construct($gameSessionId, $playerId = null) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $gameSessionId;
        if ($playerId) {
            $this->requestingPlayerId = $playerId;
        }
    }

    /**
     * Generates and saves the virtual players for the practice session, and
     * saves the practice instance in database.
     * @param type $statusDT 
     */
    function InitNewPracticeInstance() {
        //global $buyInMultiplier;

        $statusDT = Context::GetStatusDT();

        $gameInstance = new GameInstance();
        $gameInstance->id = getNextSequence('GameInstance', 'Id');
        $gameInstance->gameSessionId = $this->id;
        $gameInstance->status = GameStatus::STARTED;
        $gameInstance->startDateTime = $statusDT;
        $gameInstance->lastUpdateDateTime = $statusDT;
        // not setting dealer id and turn
        $gameInstance->currentPotSize = $defaultTableMin * 1.5;
        ;
        $gameInstance->lastBetSize = $defaultTableMin;
        $gameInstance->numberCommunityCardsShown = 0;
        $gameInstance->lastInstancePlayNumber = 0;
        $gameInstance->Insert();
        return $gameInstance;
    }

    public function InitPlayers($playerId) {
        $statusDT = Context::GetStatusDT();
        // create requesting player state first
        $player = EntityHelper::getPlayer($playerId);
        $player->UpdatePlayerSeat(0);
        $this->_createPracticePlayerInstance(0, $player->id, $player->name);

        // create dummy practice players
        $playerName = 'Practice 1 - ' . $this->id;
        $player1 = EntityHelper::createPracticePlayer($playerName, 1, $this->startDateTime);
        $this->_createPracticePlayerInstance(1, $player1->id, $playerName, $statusDT);

        $playerName = 'Practice 2 - ' . $this->id;
        $player2 = EntityHelper::createPracticePlayer($playerName, 2, $this->startDateTime);
        $this->_createPracticePlayerInstance(2, $player2->id, $playerName, $statusDT);

        $playerName = 'Practice 3 - ' . $this->id;
        $player3 = EntityHelper::createPracticePlayer($playerName, 3, $this->startDateTime);
        $this->_createPracticePlayerInstance(3, $player3->id, $playerName, $statusDT);
        /* short cut for practice instance
          // init dealer and players
          $gameInstance->dealerPlayerId = $this->playerStatusDtos[0]->playerId;
          $gameInstance->firstPlayerId = $this->playerStatusDtos[3]->playerId;

          executeSQL("update GameInstance SET DealerPlayerId = $gameInstance->dealerPlayerId,
          FirstPlayerId=$gameInstance->fi rstPlayerId,
          NextPlayerId=$gameInstance->firstPlayerId, NumberPlayers = $this->numberSeats
          WHERE id = $gameInstance->id", __FUNCTION__ . ":
          Error updating practice game instance $gameInstance->id");

          // add blind bets
          return array(new BetDto($this->playerStatusDtos[1]->playerId, $this->playerStatusDtos[1]->blindBet),
          new BetDto($this->playerStatusDtos[2]->playerId, $this->playerStatusDtos[2]->blindBet));

         */
    }

    /**
     * Indirectly called on practice games.
     * @param type $pId
     * @param type $gInstanceId
     * @param type $statusDT
     * @param type $check
     * @param type $call
     * @param type $raise
     * @return PlayerAction
     */
    function GenerateRandomAction($move) {
        /* --------------------------------------------------------------------- */
        // generate a random action from among the allowed actions.
        $pokerActionOptions = array(PokerActionType::RAISED,
            PokerActionType::CALLED); //, PokerActionType::FOLD);
        if (!is_null($move->checkAmount)) {
            // adding the check option if available
            $pokerActionOptions = array_merge($pokerActionOptions, array(PokerActionType::CHECKED));
        }
        $action = $pokerActionOptions[rand(0, count($pokerActionOptions) - 1)];
        $value = null;
        if ($action == PokerActionType::RAISED) {
            $value = $move->raiseAmount;
        } else if ($action == PokerActionType::CALLED) {
            $value = $move->callAmount;
        };
        $time = Context::GetStatusDT();
        $playerAction = new PlayerAction($move->gameInstanceId, $move->playerId, $action, $time, $value);
        return $playerAction;
    }

    /**
     *
     * @param type $instanceSetupDto
     */
    function CommunicateGameStarted($gameStatusDto) {
        $QEx = Context::GetQEx;
        $statusDT = Context::GetStatusDT();

        $eventType = EventType::GAME_STARTED;
        $instanceId = $gameStatusDto->gameInstanceId;

        $playerId = $this->requestingPlayerId;
        $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $instanceId);

        $message = new EventMessage($this->id, $playerId, $eventType, $statusDT, $gameStatusDto);
        //queueMessage($playerId, json_encode($message));
        QueueManager::QueueMessage($QEx, $playerId, json_encode($message));
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
    function _createPracticePlayerInstance($pNumber, $pId, $pName) {
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
        $isVirtual = 1;
        if ($pNumber == 0) {
            $isVirtual = 0;
        }

        /* the following set by ResetActivePlayers:
         * gameInstanceId, turnNumber, status, last play amount
         * last play instance number, number timeouts
         */
        $playerInstance = new PlayerInstance();
        $playerInstance->playerId = $pId;
        $playerInstance->isVirtual = 1;
        $playerInstance->gameSessionId = $this->id;
        $playerInstance->lastUpdateDateTime = $this->startDateTime;
        $playerInstance->seatNumber = $pNumber;
        $playerInstance->currentStake = $stake;
        $playerInstance->Insert();
    }

}

?>
