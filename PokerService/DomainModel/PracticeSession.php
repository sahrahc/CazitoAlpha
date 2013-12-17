<?php

/* * ************************************************************************************* */

/**
 * TODO: set up as child of GameSession
 */
class PracticeSession extends GameSession {

    public $startDateTime;
    public $tableMinimum;
    public $numberSeats;

    public function __construct($gameSessionId, $playerId) {
        global $defaultTableMin;
        global $numberSeats;
		parent::__construct($gameSessionId, $playerId);

		$this->isPractice = true;
        $this->tableMinimum = $defaultTableMin;
		$this->numberSeats = $numberSeats;
    }

    /**
     * Calculates the amount of the first and second blind based on 
     * practice session rules.
     * @return array{int, int}
     */
    public function FindBlindBetAmounts() {
        
        $blind1 = $this->tableMinimum / 2;
        $blind2 = $blind1 * 2;
        return array($blind1, $blind2);
    }

    /**
     * Generates and saves the virtual players for the practice session, and
     * saves the practice instance in database.
     * @param type $statusDT 
     */
    function InitNewGameInstance() {
        global $defaultTableMin;

        $statusDT = Context::GetStatusDT();

        $gameInstance = new GameInstance(getNextSequence('GameInstance', 'Id'));
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

    public function InitPlayers($playerId, $gameInstanceId) {
        // create requesting player state first
        $player = Player::GetPlayer($playerId);
        $player->UpdatePlayerSeat(0);
        $this->_createPracticePlayerInstance(0, $player->id, $gameInstanceId);

        // create dummy practice players
        $playerName = 'Practice 1 - ' . $this->id;
		$player1 = new Player(null, $playerName, null, 1);
        $player1->CreatePracticePlayer(1);
        $this->_createPracticePlayerInstance(1, $player1->id, $gameInstanceId);

        $playerName = 'Practice 2 - ' . $this->id;
		$player2 = new Player(null, $playerName, null, 1);
        $player2->CreatePracticePlayer(2);
        $this->_createPracticePlayerInstance(2, $player2->id, $gameInstanceId);

        $playerName = 'Practice 3 - ' . $this->id;
        $player3 = new Player(null, $playerName, null, 1);
		$player3->CreatePracticePlayer(3);
        $this->_createPracticePlayerInstance(3, $player3->id, $gameInstanceId);
    }

    /**
     * Indirectly called on practice games.
     */
    function GenerateRandomAction($move) {
        /* --------------------------------------------------------------------- */
        // generate a random action from among the allowed actions.
        $pokerActionOptions = array(PokerActionType::RAISED,
            PokerActionType::CALLED); //, PokerActionType::FOLD);
        if (!is_null($move->isCheckAllowed)) {
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
    function CommunicateGameStarted($gameStatusDto, $recipientPlayers) {
        $ex = Context::GetExchangePlayer();

        $eventType = EventType::GameStarted;
        $instanceId = $gameStatusDto->gameInstanceId;

        $playerId = $this->requestingPlayerId;
        $gameStatusDto->userPlayerHandDto = CardHelper::getPlayerHandDto($playerId, $instanceId);

        $message = new QueueMessage($eventType, $gameStatusDto, $this->id);
        //queueMessage($playerId, json_encode($message));
        QueueManager::SendToPlayer($ex, $playerId, json_encode($message));
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
    function _createPracticePlayerInstance($pNumber, $pId, $gameInstanceId) {
        global $defaultTableMin;
        global $buyInMultiplier;
        $stake = $defaultTableMin * $buyInMultiplier;
/*
        $blindBet = 0;
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
        } */
        /* the following set by ResetActivePlayers:
         * gameInstanceId, turnNumber, status, last play amount
         * last play instance number, number timeouts
         */
        $playerInstance = new PlayerInstance();
        $playerInstance->playerId = $pId;
        $playerInstance->gameInstanceId = $gameInstanceId;
        $playerInstance->isVirtual = 1;
        $playerInstance->gameSessionId = $this->id;
        $playerInstance->lastUpdateDateTime = $this->startDateTime;
        $playerInstance->seatNumber = $pNumber;
        $playerInstance->currentStake = $stake;
        $playerInstance->Insert();
    }

	    public function CreatePracticeSession() {

        $nextSessionId = getNextSequence('GameSession', 'Id');
		$this->id = $nextSessionId;
        $this->startDateTime = Context::GetStatusDT();
        $startString = Context::GetStatusDTString();

		$vars = "Id, RequestingPlayerId, StartDateTime, TableMinimum, NumberSeats, IsPractice";
		$values = "$this->id, $this->requestingPlayerId, '$startString', $this->tableMinimum, "
				. "$this->numberSeats, $this->isPractice";
		$event = "INSERT INTO GameSession ($vars) VALUES ($values)";
		$eventCount = executeNonQuery($event, __CLASS__ . "-" . __FUNCTION__);
		$this->history->info("INSERTED $eventCount: $vars -INTO- $values");
    }

}

?>
