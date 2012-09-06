<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

class GameInstanceSetup {

    public $id;               // set on GameSession.startGameInstance
    public $gameSessionId;    // set on GameSession.startGameInstance
    public $isPractice;       // set on GameSession.startGameInstance
    public $startDateTime;    // set on GameSession.startGameInstance
    public $tableMinimum;     // set on GameSession.startGameInstance
    public $numberPlayers;    // set on $this->resetPlayerStatesAndTurns
    public $dealerPlayerId;   // set on $this->resetPlayerStatesAndTurns
    public $dealerTurnNumber; // set on $this->resetPlayerStatesAndTurns
    public $firstPlayerId;    // set on $this->resetPlayerStatesAndTurns
    public $log;

    public function __construct($id, $gSessionId) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $id;
        $this->gameSessionId = $gSessionId;
    }

    /**
     * Goes through all the seated players and assigns turn numbers. To be called before a game instance starts. This method creates player states in the correct (turn numbers increasing with seat number) order.
     * NOTE: Does not use GameInstanceId.
     * @param CasinoTable $cTable The casino table is needed to synch players who join/left table.
     * @param timestamp $statusDT
     * @param in lastDealerSN The previous instance's dealer seat number
     */
    function resetPlayerStatesAndTurns($cTable, $statusDT, $lastDealerSN) {
        if (is_null($lastDealerSN)) {
            $lastDealerSN = -1;
        }
        $initialStatus = PlayerStatusType::WAITING;

        // clean up next pokermove because previous instance will have no player states
        executeSQL("UPDATE NextPokerMove m INNER JOIN GameInstance i ON m.GameInstanceId = i.Id
            SET m.IsDeleted = 1 WHERE i.GameSessionId = $this->gameSessionId", __FUNCTION__ . "
                : Error setting next poker moves to deleted for session $this->gameSessionId");
        $this->log->warn(__FUNCTION__ . " - Set " . mysql_affected_rows() . " next poker moves
                to deleted for game session $this->gameSessionId.");
        /* FIXME: must reset queues?
        executeSQL("UPDATE EventMessage SET IsDeleted = 1
                WHERE GameSessionId = $this->gameSessionId", __FUNCTION__ . "
                : Error setting next event msgs to deleted for session $this->gameSessionId");
        $this->log->warn(__FUNCTION__ . " - Set " . mysql_affected_rows() . " event messages
                to deleted for game session $this->gameSessionId.");
        */
        // synch up player states first but ignore stakes and turn numbers are set later
        if (!is_null($cTable)) {
        //-------------------------------------------------------------
        // 1. delete players who left
        // FIXME: verify logic after implementing leaving tables
          /*  executeSQL("DELETE PlayerState
                FROM PlayerState INNER JOIN Player p ON PlayerState.PlayerId = p.Id
                WHERE p.CurrentCasinoTableId <> $cTable->id", __FUNCTION__ . "
                : Error deleting PlayerStates who left
                game session $this->gameSessionId at table $cTable->id");
            $this->log->warn(__FUNCTION__ . " - Deleted " . mysql_affected_rows() . "
                player states for players who left casino table.");
                */
            // FIXME: true for now
            $isVirtual = 0;
            // 2. add players who joined AND HAVE A SEAT (not in waiting list),
            // most columns will be reset later
            executeSQL("INSERT INTO PlayerState (GameSessionId, GameInstanceId, PlayerId, 
                    IsVirtual, LastUpdateDateTime, SeatNumber, Status, LastPlayAmount, 
                    PlayerPlayNumber, BlindBet, Stake, NumberTimeOuts)
                    SELECT $this->gameSessionId, $this->id, p.Id, $isVirtual, '$statusDT',
                    p.CurrentSeatNumber, '$initialStatus', 0, 0, 0, p.BuyIn, 0
                FROM Player p INNER JOIN CasinoTable c ON p.CurrentCasinoTableId = c.Id
                    LEFT JOIN PlayerState s ON p.Id = s.PlayerId AND s.GameSessionId = 
                c.CurrentGameSessionId
                    WHERE p.CurrentCasinoTableId = $cTable->id AND p.currentSeatNumber
                IS NOT NULL AND s.PlayerId IS NULL", __FUNCTION__ . "
                : Error inserting PlayerState for new players in table $cTable->id");
            $this->log->warn(__FUNCTION__ . " - Inserted " . mysql_affected_rows() . " player
                states for players who recently joined the table.");
        }
        // 3. update players with current seat numbers. Done in the db because most efficient.
        executeSQL("UPDATE PlayerState ps JOIN Player p ON ps.PlayerId = p.Id SET
                ps.GameInstanceId = $this->id,
                ps.LastUpdateDateTime = '$statusDT',
                ps.SeatNumber = p.CurrentSeatNumber,
                ps.Status = '$initialStatus',
                ps.BlindBet = 0, 
                ps.LastPlayAmount = 0,
                ps.PlayerPlayNumber = 0,
                ps.NumberTimeOuts = 0,
                ps.Card1Code = null, ps.Card2Code = null,
                ps.HandType = null, ps.HandInfo = null, ps.HandCategory = null,
                ps.HandRankWithinCategory = null
                WHERE GameSessionId = $this->gameSessionId AND p.CurrentSeatNumber is not null
                ", __FUNCTION__ . ": Error updating player state seat numbers with player data
                for session id $this->gameSessionId");
        $this->log->warn(__FUNCTION__ . " - Updated " . mysql_affected_rows() . " player states
            for players who stayed at the table for the next game.");

        $playerStatuses = EntityHelper::getPlayerInstancesForGame($this->id);

        // assign turn numbers and update; note that players who just joined the table will have both an insert and an update
        $countPlayers = count($playerStatuses);
        for ($i = 0; $i < $countPlayers; $i++) {
            $turnNumber = ($i + ($countPlayers-1) - $lastDealerSN) % $countPlayers;
            $playerId = $playerStatuses[$i]->playerId;
            executeSQL("UPDATE PlayerState set TurnNumber = $turnNumber
                    WHERE PlayerId = $playerId
                    and GameSessionId = $this->gameSessionId", __FUNCTION__ . "
                    : Error updating turn number for PlayerState player id
                    $playerId and session $this->gameSessionId");
        }
    }

    /**
     * Assign cards dealt on a game to players and store them in database. Returns the hand for the user who requested the operation, which is used when a game first starts.
     * This method is an optimization in that it combines two operations in one.
     * Restrictions: Must be called after game reset.
     * @param array($pokerCard) $pokerCards: list of cards, randomly shuffled, for all players + 5 community cards
     * @param type $userPlayerId
     * @return PlayerHandDto
     */
    function saveGameCardsGetUserHandDto($pokerCards, $userPlayerId) {
        // NOTE: the hands don't get saved on the player state until all the hands are known.
        // 1. player cards; no need to worry about player status
        $leftStatus = PlayerStatusType::LEFT;
        $result = executeSQL("SELECT * FROM PlayerState WHERE GameInstanceId = $this->id
                AND Status != '$leftStatus' ORDER BY TurnNumber", __FUNCTION__ . "
                : Error selecting PlayerState instance id $this->id");

        $numberRows = mysql_num_rows($result);
        $cardCounter = 1;
        $this->log->debug(__FUNCTION__ . " - number players in db: $numberRows");
        $userHandDto = null;
        while ($row = mysql_fetch_array($result)) {
            $playerId = $row['PlayerId'];

            // assign and store player cards
            $card1Index = $pokerCards[$cardCounter]->cardIndex;
            $card1Code = $pokerCards[$cardCounter]->cardCode;
            $card1DeckIndex = $pokerCards[$cardCounter]->deckPosition;
            $cardCounter++;
            executeSQL("INSERT INTO GameCard (GameInstanceId, PlayerId, PlayerCardNumber,
                    DeckPosition, CardCode, CardIndex) VALUES ($this->id, $playerId, 1,
                    $card1DeckIndex, '$card1Code', $card1Index)", __FUNCTION__ . "
                    : Error inserting player game cards player id $playerId");

            $card2Index = $pokerCards[$cardCounter]->cardIndex;
            $card2Code = $pokerCards[$cardCounter]->cardCode;
            $card2DeckIndex = $pokerCards[$cardCounter]->deckPosition;
            $cardCounter++;
            executeSQL("INSERT INTO GameCard (GameInstanceId, PlayerId, PlayerCardNumber,
                    DeckPosition, CardCode, CardIndex) VALUES ($this->id, $playerId, 2,
                    $card2DeckIndex, '$card2Code', $card2Index)", __FUNCTION__ . "
                    : Error inserting player game cards player id $playerId");

            // 3. send back the requesting player cards only
            if ($userPlayerId == $playerId) {
                $userCard1Dto = new PokerCardDto(1, $card1Code);
                $userCard2Dto = new PokerCardDto(2, $card2Code);
                
                $userHandDto = new PlayerHandDto($playerId, $userCard1Dto, $userCard2Dto);
            }
        }
        // 2. pick the next 10 to be community cards
        for ($i = 0; $i < 5; $i++) {
            // store community cards database
            $cardNumber = $i + 1;
            
            $cardIndex = $pokerCards[$cardCounter]->cardIndex;
            $cardCode = $pokerCards[$cardCounter]->cardCode;
            $deckPosition = $pokerCards[$cardCounter]->deckPosition;
            executeSQL("INSERT INTO GameCard (GameInstanceId, PlayerId, PlayerCardNumber, 
                    DeckPosition, CardCode, CardIndex) VALUES ($this->id, -1, $cardNumber,
                    $deckPosition, '$cardCode', $cardIndex)", __FUNCTION__ . "
                    : Error inserting GameCard instance ID $this->id");
            $cardCounter++;
        }
        /* save the rest without player info */
        for ($i = $cardCounter; $i<count($pokerCards); $i++){
            $cardIndex = $pokerCards[$i]->cardIndex;
            $cardCode = $pokerCards[$i]->cardCode;
            $deckPosition = $pokerCards[$i]->deckPosition;
            executeSQL("INSERT INTO GameCard (GameInstanceId, PlayerId, PlayerCardNumber,
                    DeckPosition, CardCode, CardIndex) VALUES ($this->id, null, null,
                    $deckPosition, '$cardCode', $cardIndex)", __FUNCTION__ . "
                    : Error inserting unassigned GameCard instance ID $this->id");
        }
        return $userHandDto;
    }

    /**
     * Define the first move on a newly started game instance.
     * @global type $dateTimeFormat
     * @global type $playExpiration
     * @global type $practiceExpiration
     * @param type $firstPlayerId
     * @param type $tableMin
     */
    function saveFirstExpectedMove($firstPlayerId, $tableMin) {
        global $dateTimeFormat;
        global $playExpiration;
        global $practiceExpiration;
        $expirationDateTime = new DateTime();
        if ($this->isPractice == 1) {
            $expirationDateTime->add(new DateInterval($practiceExpiration)); // 2 seconds
        } else {
            $expirationDateTime->add(new DateInterval($playExpiration)); // 20 seconds
        }
        $expirationString = $expirationDateTime->format($dateTimeFormat);

        $raiseAmount = $tableMin * 2;
        executeSQL("INSERT INTO NextPokerMove (GameInstanceId, IsPractice, PlayerId,
                TurnNumber, ExpirationDate, IsEndGameNext, CallAmount, CheckAmount,
                RaiseAmount, IsDeleted)
                SELECT $this->id, $this->isPractice, $firstPlayerId,
                TurnNumber, '$expirationString', 0, $tableMin, null,
                $raiseAmount, 0
                FROM PlayerState WHERE GameInstanceId = $this->id
                    AND Playerid = $firstPlayerId", __FUNCTION__ . ":
                ERROR - Error inserting first move for instance $this->id");
    }

}

?>
