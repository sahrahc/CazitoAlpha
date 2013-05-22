<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');
require_once(dirname(__FILE__) . '/../Components/QueueManager.php');
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
    public $playerHands;
    public $ex;
    private $log;

    public function __construct($id) {
        $this->log = Logger::getLogger(__CLASS__);
        $this->id = $id;
    }

    /*     * ***************************************************************************** */
    /* instance cards and hands */

    /**
     * Only used once, to get all the cards in order to identify the winner and publish everyone's hands at the end of the game.
     * @return GameCards 
    */
    public function getInstanceGameCards() {
        // get all the cards, order with community cards first.
        $result = executeSQL("SELECT g.*, ps.status AS Status FROM GameCard g 
                LEFT JOIN PlayerState ps ON g.GameInstanceId = ps.GameInstanceId
                AND g.PlayerId = ps.PlayerId WHERE g.GameInstanceId = $this->id
                AND g.playerId is not null
                ORDER BY g.PlayerId, PlayerCardNumber", __FUNCTION__ . "
                : Error selecting all GameCard for instance id $this->id");

        // initialize
        $playerIndex = 0; // index on array of players
        $ccIndex = 0;     // index on array of community cards
        $playerHands = null;
        $communityCards = null;
        $prevPlayerId = null;
        // this won't work if $result is not sorted by playerid
        while ($rowCard = mysql_fetch_array($result)) {
            if ($rowCard["PlayerId"] == -1) {
                // process community cards
                $communityCards[$ccIndex] = new PokerCard($rowCard['PlayerCardNumber'],
                                $rowCard['DeckPosition'], $rowCard['CardCode']);
                $communityCards[$ccIndex++]->cardIndex = $rowCard['CardIndex'];
            } else if ($rowCard["Status"] != PlayerStatusType::FOLDED &&
                  $rowCard["Status"] != PlayerStatusType::LEFT) {
                // one entity for both cards.
                if (is_null($prevPlayerId) || $prevPlayerId != $rowCard["PlayerId"]) {
                    $playerIndex = is_null($prevPlayerId) ? 0 : $playerIndex + 1;
                    // Not validating playercardnumber, in poker there is only two
                    // and the insert needs to make sure the values are only 1 and 2 and
                    // both are present. Anything else is data becoming corrupted.
                    $playerHands[$playerIndex] = new PlayerHand($rowCard['PlayerId'],
                                    new PokerCard($rowCard['PlayerCardNumber'],
                                            $rowCard['DeckPosition'], $rowCard['CardCode']), null);
                    $playerHands[$playerIndex]->pokerCard1->cardIndex = $rowCard['CardIndex'];
                } else {
                    $playerHands[$playerIndex]->pokerCard2 = new PokerCard(
                                    $rowCard['PlayerCardNumber'], $rowCard['DeckPosition'],
                                    $rowCard['CardCode']);
                    $playerHands[$playerIndex]->pokerCard2->cardIndex = $rowCard['CardIndex'];
                    // increase index when second and last card is found
                }
                $prevPlayerId = $rowCard["PlayerId"];
            }
        }

        return new GameCards($communityCards, $playerHands);
    }

    /**
     * Returns player hand Dtos.
     * @return PlayerHandDto
     */
    function getInstancePlayerHandDtos() {
        $result = executeSQL("SELECT gc.*, HandType, HandInfo, HandCategory,
                HandRankWithinCategory
                FROM GameCard gc INNER JOIN PlayerState ps
                ON gc.GameInstanceId = ps.GameInstanceId AND gc.PlayerId = ps.PlayerId
                WHERE gc.gameInstanceId = $this->id AND gc.PlayerId > -1
                ORDER BY gc.PlayerId, gc.PlayerCardNumber", __FUNCTION__ . "
                : Error selecting from player state with instance id $this->id");
        $playerHandDtos = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerId = $row["PlayerId"];
            $pokerCard1Dto = new PokerCardDto($row["PlayerCardNumber"], $row["CardCode"]);
            // get second card within the loop
            $row = mysql_fetch_array($result);
            $pokerCard2Dto = new PokerCardDto($row["PlayerCardNumber"], $row["CardCode"]);

            $playerHandDtos[$counter] = new PlayerHandDto($playerId, $pokerCard1Dto, $pokerCard2Dto);
            $playerHandDtos[$counter]->pokerHandType = $row["HandType"];
            $playerHandDtos[$counter]->handInfo = $row["HandInfo"];
            $playerHandDtos[$counter]->handCategory = $row["HandCategory"];
            $playerHandDtos[$counter]->rankWithinCategory = $row["HandRankWithinCategory"];
            if ($this->winningPlayerId == $playerId) {
                $playerHandDtos[$counter]->isWinningHand = 1;
            } else {
                $playerHandDtos[$counter]->isWinningHand = 0;
            }
            $counter++;
        }
        return $playerHandDtos;
    }

    /**
     * Updates all calculated player hands portion of the PlayerStates, part of finding the winner.
     * @param type $playerHands
     * @param type $statusDT 
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
     * Evalate whether community cards need to be dealt on table based on turns.
     */
    private function dealGetCommunityCardDtos($actionPlayerId, $pPlayNum) {
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
            $allCards = CardHelper::getCommunityCardDtos($this->id, $numberCards);
            $length = $numberCards == 3 ? 3 : 1;
            $cardsToSend = array_slice($allCards, $previousNumberCards, $length);
        }
        // returning
        return $cardsToSend;
    }

    /*     * ***************************************************************************** */
    /* update instance status */

    /**
     * When a game is first started, the dealer and blinds are identified based on the dealer of the last instance.
     * Must be called after turns reset.
     * @param array(int, int) blindAmts The size of the small and large blinds.
     * @param int $lastDealerSN
     * @param PlayerInstanceStatus[] $pStatuses The index is the turn number because reset makes them so.
     * @param timestamp statusDT
     * @return blindBets[BetDto, Bet]
     */
    function saveInstanceWithDealerAndBlinds($blindAmts, $lastDealerSN, $pStatuses, $statusDT) {
        $blind1 = $blindAmts[0];
        $blind2 = $blindAmts[1];

        $count = count($pStatuses);
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

        $blindBets = array(new BetDto($pStatuses[$blind1Turn]->playerId, $blind1),
            new BetDto($pStatuses[$blind2Turn]->playerId, $blind2));

        $this->nextPlayerId = $pStatuses[$nextPlayerTurn]->playerId;
        $this->nextTurnNumber = $nextPlayerTurn;
        $this->gameInstanceSetup->dealerPlayerId = $pStatuses[$dealerTurn]->playerId;
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
    function followUpPlayerTurn($nextPMove, $actionPlayerId, $pPlayNum, $statusDT) {
        $playerActionResultDto = new PlayerActionResultDto($nextPMove);

        // get game end
        if ($nextPMove->isEndGameNext) {
            $playerActionResultDto->gameResultDto = $this->findWinnerGetResult($statusDT);
        }
        // update the community cards
        $playerActionResultDto->cardsToSend = $this->dealGetCommunityCardDtos($actionPlayerId, $pPlayNum);
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
     * Find the winner and everyone's hands at the end of the game.
     * @param type $statusDT
     * @return GameResultDto 
     */
    function findWinnerGetResult($statusDT) {
        $gameCards = $this->getInstanceGameCards();
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

        $playerHandsDto = PlayerHandDto::mapPlayerHands($playerHands);
        $gameResult = new GameResultDto($playerHandsDto, $hH->winningPlayerId, $this->id);
        /* --------------------------------------------------------------------- */
        /* mark cards that are to be seen if item is enabled */
        CheatingHelper::markGameCards($this);
        /* --------------------------------------------------------------------- */
        return $gameResult;
    }

    /**
     * Gets the game result if ended
     */
    /**
     *
     * @return GameResultDto 
     */
    function getGameResult() {
        if (is_null($this->winningPlayerId)) {
            return null;
        }
        return new GameResultDto($this->playerHands, $this->winningPlayerId, $this->id);
    }

    /**
     * Create an message and send it to everyone's queue including the player who made the move.
     * @param type $isTimeOut Whether the action was by a player or triggered by timeout
     */
    function communicateMoveResult($playerActionResultDto, $isTimeOut) {

        // a timeout by a practice game is not a time out.
        $actionType = EventType::PLAYER_MOVE;
        if ($this->gameInstanceSetup->isPractice == 0 && $isTimeOut == 1) {
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
            $playerInstances = $casinoTable->getCasinoPlayerDtos();
        }
        for ($i = 0; $i < count($playerInstances); $i++) {

            if ($playerInstances[$i]->isVirtual == 1 ||
                    $playerInstances[$i] == PlayerStatusType::LEFT) {
                continue;
            }
            $message = new EventMessage($this->gameInstanceSetup->gameSessionId,
                            $playerInstances[$i]->playerId, $actionType, $this->lastUpdateDateTime,
                            $playerActionResultDto);
            //$message->eventData = $playerActionResultDto;
            QueueManager::queueMessage($this->ex, $playerInstances[$i]->playerId, json_encode($message));
        }
    }

}

?>
