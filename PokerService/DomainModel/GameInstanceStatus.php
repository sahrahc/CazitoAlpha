<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

class HighestHand {

    public $handCategory;
    public $rankWithinCategory;
    public $winningPlayerId;

}

class GameInstanceStatus {

    public $id;
    public $lastUpdateDateTime;        // updated by PlayerMove
    public $nextPlayerId;              // updated by PlayerMove
    public $nextTurnNumber;            // updated by PlayerMove
    public $isNextPlayerVirtual;
    public $potSize;                   // updated by PlayerMove, SaveInstanceWBlinds
    public $lastBetSize;               // updated by PlayerMove, SaveInstanceWBlinds
    public $numberCommunityCardsShown; // updated by PlayerMove
    public $lastInstancePlayNumber;    // updated by PlayerMove
    public $winningPlayerId;
    public $gameInstanceSetup;
    private $log;
    public $playerHands;
    
    public function __construct($id) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $id;
    }

    /*     * ***************************************************************************** */
    /* private methods */

    /**
     * Only used once, to get all the cards in order to identify the winner and publish everyone's hands at the end of the game.
     * @return GameCards
     */
    private function getAllGameCards() {
        // get all the cards, order with community cards first.
        $result = executeSQL("SELECT g.*, ps.status AS Status FROM GameCard g 
                LEFT JOIN PlayerState ps ON g.GameInstanceId = ps.GameInstanceId
                AND g.PlayerId = ps.PlayerId WHERE g.GameInstanceId = $this->id
                ORDER BY g.PlayerId, CardNumber", __FUNCTION__ . "
                : Error selecting all GameCard for instance id $this->id");

        // initialize
        $playerIndex = 0; // index on array of players
        $ccIndex = 0;     // index on array of community cards
        $playerHands = null;
        $communityCards = null;

        // instantiate objects to be returned with the data
        while ($rowCard = mysql_fetch_array($result)) {
            if ($rowCard["PlayerId"] == -1) {
                // process community cards
                $communityCards[$ccIndex++] = new PokerCard($rowCard['CardNumber'],
                                $rowCard['CardIndex'], $rowCard['CardName']);
            } else if ($rowCard["Status"] != PlayerStatusType::FOLDED &&
                    $rowCard["Status"] != PlayerStatusType::LEFT) {
                // one entity for both cards.
                if ($rowCard['CardNumber'] == 1) {
                    $playerHands[$playerIndex] = new PlayerHand($rowCard['PlayerId'],
                                    new PokerCard($rowCard['CardNumber'],
                                            $rowCard['CardIndex'], $rowCard['CardName']),
                                    null);
                } else {
                    $playerHands[$playerIndex]->pokerCard2 = new PokerCard(
                                    $rowCard['CardNumber'], $rowCard['CardIndex'],
                                    $rowCard['CardName']);
                    ;
                    // increase index when second and last card is found
                    $playerIndex++;
                }
            }
        }

        return new GameCards($communityCards, $playerHands);
    }

    /**
     * Updates all calculated player hands portion of the PlayerStates, part of finding the winner.
     * @param array(PlayerHand) $playerHands
     */
    private function updateStatePlayerHands($playerHands, $statusDT) {
        for ($i = 0; $i < count($playerHands); $i++) {
            $handType = $playerHands[$i]->pokerHandType;
            $handInfo = $playerHands[$i]->handInfo;
            $handCategory = $playerHands[$i]->handCategory;
            $handRank = $playerHands[$i]->rankWithinCategory;
            $playerId = $playerHands[$i]->playerId;
            executeSQL("UPDATE PlayerState SET HandType = '$handType', HandInfo = $handInfo,
                    HandCategory = $handCategory, HandRankWithinCategory = $handRank,
                    LastUpdateDateTime = '$statusDT' WHERE PlayerId = $playerId", __FUNCTION__ . "
                        :ERROR updating PlayerState player id $playerId");
        }
    }

    /**
     * When a game is first started, the dealer and blinds are identified based on the dealer of the last instance.
     * Must be called after turns reset.
     * @param array(int, int) blindAmts The size of the small and large blinds.
     * @param int $lastDealerSN
     * @param PlayerInstanceStatus[] $playerStatuses The index is the turn number because reset makes them so.
     * @param timestamp statusDT
     * @return blindBets[Bet, Bet]
     */
    function saveInstanceWithDealerAndBlinds($blindAmts, $lastDealerSN, $playerStatuses, $statusDT) {
        $blind1 = $blindAmts[0];
        $blind2 = $blindAmts[1];

        $count = count($playerStatuses);
        $dealerTurn = 0 % $count;
        $blind1Turn = 1 % $count;
        $blind2Turn = 2 % $count;
        $nextPlayerTurn = 3 % $count;
        executeSQL("UPDATE PlayerState SET BlindBet = $blind1,
                Stake = Stake - $blind1,
                status = '" . PlayerStatusType::BLIND_BET . "',
                LastPlayAmount = $blind1,
                LastUpdateDateTime = '$statusDT'
                WHERE GameInstanceId = $this->id AND TurnNumber = $blind1Turn", __FUNCTION__ . ": Error updating PlayerState first blind bet instance
                $this->id ");

        executeSQL("UPDATE PlayerState SET BlindBet = $blind2,
                Stake = Stake - $blind2,
                status = '" . PlayerStatusType::BLIND_BET . "',
                LastPlayAmount = $blind2,
                LastUpdateDateTime = '$statusDT'
                WHERE GameInstanceId = $this->id AND TurnNumber = $blind2Turn", __FUNCTION__ . ": Error updating PlayerState second blind bet instance
                $this->id ");

        $blindBets = array(new Bet($playerStatuses[$blind1Turn]->playerId, $blind1),
            new Bet($playerStatuses[$blind2Turn]->playerId, $blind2));

        $this->nextPlayerId = $playerStatuses[$nextPlayerTurn]->playerId;
        $this->nextTurnNumber = $nextPlayerTurn;
        $this->gameInstanceSetup->dealerPlayerId = $playerStatuses[$dealerTurn]->playerId;
        $this->gameInstanceSetup->dealerTurnNumber = $dealerTurn;
        $this->gameInstanceSetup->firstPlayerId = $this->nextPlayerId;

        $this->lastBetSize = $blind2;
        $this->potSize = $blind1 + $blind2;
        // update dealer id and pot size
        executeSQL("UPDATE GameInstance SET DealerPlayerId = " .
                $this->gameInstanceSetup->dealerPlayerId . ",
                NextPlayerId = $this->nextPlayerId,
                FirstPlayerId = " .
                $this->gameInstanceSetup->firstPlayerId . ",
                PotSize = $blind1+$blind2,
                NumberPlayers = $count,
                LastBetSize = $blind2,
                lastUpdateDateTime = '$statusDT'
                WHERE Id = $this->id", __FUNCTION__ . ": Error updating GameInstance with
                dealer, next player and pot size instance $this->id ");

        return $blindBets;
    }

    /**
     * Updates GameInstance and PlayerState with the results of the action.
     * @param type $statusDT
     */
    function followUpPlayerTurn($nextPokerMove, $actionPlayerId, $playerPlayNumber, $statusDT) {
        $playerActionResultDto = new PlayerActionResultDto($nextPokerMove);

        // get game end
        if ($nextPokerMove->isEndGameNext) {
            $playerActionResultDto->gameResultDto = $this->findWinnerGetResult($statusDT);
        }
        // update the community cards
        $playerActionResultDto->cardsToSend = $this->dealCommunityCards($actionPlayerId, $playerPlayNumber);
        $this->numberCommunityCardsShown +=
                count($playerActionResultDto->cardsToSend);

        // update in database
        $instanceId = $this->id;
        $numCards = $this->numberCommunityCardsShown;
        $nextId = $this->nextPlayerId;
        $betSize = $this->lastBetSize;
        // no need to save the next player turn number
        executeSQL("UPDATE GameInstance SET LastUpdateDateTime = '$statusDT',
                NextPlayerId = $this->nextPlayerId,
                PotSize = $this->potSize,
                LastBetSize = $this->lastBetSize,
                NumberCommunityCardsShown = $this->numberCommunityCardsShown,
                LastInstancePlayNumber = $this->lastInstancePlayNumber
            WHERE Id = $this->id", __FUNCTION__ . "
                : Error updating on GameState for instance $this->id");

        return $playerActionResultDto;
    }

    /**
     * Evalate whether community cards need to be dealt on table based on turns.
     */
    private function dealCommunityCards($actionPlayerId, $playerPlayNumber) {
        // fold
        $previousNumberCards = $this->numberCommunityCardsShown;
        $numberCards = $previousNumberCards;
        $numberPlayers = $this->gameInstanceSetup->numberPlayers;
        $instancePlayNumber = $this->lastInstancePlayNumber;
        $cardsToSend = null;

        // round is complete if next player is the first player
        // deal community cards must be called after play is processed.
        switch (floor($instancePlayNumber / $numberPlayers)) {
            // if ($this->nextPlayerId == $this->gameInstanceSetup->firstPlayerId) {
            //switch ($playerPlayNumber) {
            case 0:
                $numberCards = 0;
                break;
            case 1:
                $numberCards = 3;
                break;
            case 2:
                $numberCards = 4;
                break;
            default:
                $numberCards = 5;
        }
        // get the new cards if more this time.
        if ($previousNumberCards != $numberCards) {
            $allCards = CardHelper::getCommunityCards($this->id, $numberCards);
            $length = $numberCards == 3 ? 3 : 1;
            $cardsToSend = array_slice($allCards, $previousNumberCards, $length);
        }
        // returning
        return $cardsToSend;
    }

    function loadGameInstanceHands() {
        $result = executeSQL("SELECT gc.*, HandType, HandInfo, HandCategory, 
                HandRankWithinCategory
                FROM GameCard gc INNER JOIN PlayerState ps
                ON gc.GameInstanceId = ps.GameInstanceId AND gc.PlayerId = ps.PlayerId
                WHERE gc.gameInstanceId = $this->id
                AND gc.PlayerId > -1 ORDER BY gc.PlayerId, gc.CardNumber", __FUNCTION__ . "
                : Error selecting from player state with instance id $this->id");
        $playerHands = null;
        $counter = 0;
        while ($row=mysql_fetch_array($result)) {
            $playerId = $row["PlayerId"];
            $pokerCard1 = new PokerCard($row["CardNumber"], $row["CardIndex"], $row["CardName"]);

            // get second card within the loop
            $row = mysql_fetch_array($result);
            $pokerCard2 = new PokerCard($row["CardNumber"], $row["CardIndex"], $row["CardName"]);

            $playerHands[$counter] =  new PlayerHand($playerId, $pokerCard1, $pokerCard2);
            $playerHands[$counter]->pokerHandType = $row["HandType"];
            $playerHands[$counter]->handInfo = $row["HandInfo"];
            $playerHands[$counter]->handCategory = $row["HandCategory"];
            $playerHands[$counter]->rankWithinCategory = $row["HandRankWithinCategory"];
            if ($this->winningPlayerId == $playerId){
                $playerHands[$counter]->isWinningHand = 1;
            }
            else { $playerHands[$counter]->isWinningHand = 0;}
            $counter++;
        }
        return $playerHands;
    }

    /**
     * Create an message and send it to the queue.
     * @param type $isTimeout Whether the action was by a player or triggered by timeout
     */
    function communicateMoveResult($playerActionResultDto, $isTimeout) {

        // a timeout by a practice game is not a time out.
        $actionType = EventType::PLAYER_MOVE;
        if ($this->gameInstanceSetup->isPractice == 0 && $isTimeout == 1) {
            $actionType = EventType::TIME_OUT;
        }

        // get the players;
        $playerInstances = null;
        if ($this->gameInstanceSetup->isPractice == 1) {
            $playerInstances = EntityHelper::getPlayerInstancesForGame($this->id);
            for ($i = 0; $i < count($playerInstances); $i++) {
                // FIXME - is this good form to add an arbitrary property?
                $playerInstances[$i]->isVirtual = $playerInstances[$i]->playerInstanceSetup->isVirtual;
            }
        } else {
            $casinoTable = EntityHelper::getCasinoTableForSession($this->gameInstanceSetup->gameSessionId);
            $playerInstances = $casinoTable->loadPlayers();
        }
        //$jsonEventM = addslashes(json_encode($updatedPlayerStatus));
        $jsonEvent = json_encode($playerActionResultDto);
        for ($i = 0; $i < count($playerInstances); $i++) {

            $this->log->debug(__FUNCTION__ . " - Event Message Created: " . $jsonEvent);
            /*   if ($playerInstances[$i]->playerId
              == $playerActionResultDto->playerStatusDto->playerId)
              continue; */
            if ($playerInstances[$i]->isVirtual == 1) {
                continue;
            }
            $message = new EventMessage($this->gameInstanceSetup->gameSessionId,
                    $playerInstances[$i]->playerId, $actionType, $this->lastUpdateDateTime,
                                null);
            //$jsonEvent);
                $message->eventData = $playerActionResultDto;
                //$message->enQueue();
                queueMessage($playerInstances[$i]->playerId, json_encode($message));
        }
    }

    /**
     * Find the winner and everyone's hands at the end of the game.
     * @return GameResultDto
     */
    function findWinnerGetResult($statusDT) {
        $gameCards = $this->getAllGameCards();
        $playerHands = $gameCards->playerHands;

        $hH = new HighestHand(); // holds the highest hand found when traversing the players
        $hH->handCategory = 0;
        $hH->handRank = 0;
        $hH->winningPlayerId = 0;

        for ($i = 0; $i < count($playerHands); $i++) {
            $cards = array(
                $gameCards->communityCards[0]->cardIndex,
                $gameCards->communityCards[1]->cardIndex,
                $gameCards->communityCards[2]->cardIndex,
                $gameCards->communityCards[3]->cardIndex,
                $gameCards->communityCards[4]->cardIndex,
                $gameCards->playerHands[$i]->pokerCard1->cardIndex,
                $gameCards->playerHands[$i]->pokerCard2->cardIndex
            );
            $playerHands[$i] = CardHelper::identifyPlayerHand($cards, $playerHands[$i]);

            $hH = CardHelper::getHigherCard($hH, $playerHands[$i]);
        }

        // update player hands.
        for ($i = 0; $i < count($playerHands); $i++) {
            // update hand for each player
            if ($playerHands[$i]->playerId == $hH->winningPlayerId) {
                $playerHands[$i]->isWinningHand = 1;
                $status = "Won";
            } else {
                $status = "Lost";
                $playerHands[$i]->isWinningHand = 0;
            }
        }
        $this->updateStatePlayerHands($playerHands, $statusDT);
        // save winning player Id
        executeSQL("UPDATE GameInstance SET WinningPlayerId = $hH->winningPlayerId,
                LastUpdateDateTime = '$statusDT' WHERE Id = $this->id", __FUNCTION__ . "
                : ERROR updating GameInstance id $this->id");

        // reward winner's stake
        executeSQL("UPDATE PlayerState SET Stake = Stake + $this->potSize,
                LastUpdateDateTime = '$statusDT' WHERE PlayerId =
                $hH->winningPlayerId AND GameInstanceId = $this->id", __FUNCTION__ . "
                : ERROR updating PlayerState instance id $this->id");

        $gameResult = new GameResultDto($playerHands, $hH->winningPlayerId, $this->id);
        /* --------------------------------------------------------------------- */
        return $gameResult;
    }

    /**
     * Gets the game result if ended
     */
    function getGameResult() {
        if (is_null($this->winningPlayerId)) {
            return null;
        }
        return new GameResultDto($this->playerHands, $this->winningPlayerId, $this->id);
    }

}

?>
