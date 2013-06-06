<?php

/* This object allows all cards for a game to be passed.
 */

class GameInstanceCards {

    public $communityCards;
    public $playerHands;
    
    function __construct($communityCards, $playerHands) {
        $this->communityCards = $communityCards;
        $this->playerHands = $playerHands;
    }

    /**
     * Separate saving the entity into the database to EntityHelper
     * Assign cards dealt on a game to players and store them in database. Returns the hand for the user who requested the operation, which is used when a game first starts.
     * This method is an optimization in that it combines two operations in one.
     * Restrictions: Must be called after game reset.
     * shuffling of cards (EvalHelper) either here or called from PokerCoordinator
     * cleaner that way
     * @param array($pokerCard) $pokerCards: list of cards, randomly shuffled, for all players + 5 community cards
     */
    public static function InitDealGameCards($gameInstance) {
        $pokerCards = EvalHelper::shuffleDeck();
        // NOTE: the hands don't get saved on the player state until all the hands are known.
        // 1. player cards; no need to worry about player status
        $playerStatuses = PlayerInstance::GetPlayerInstancesForGame($gameInstance->id);

        $cardCounter = 1;
        //$this->log->debug(__FUNCTION__ . " - number players in db: $numberRows");
        foreach($playerStatuses as $playerStatus) {
            // assign and store player cards
            $card1Index = $pokerCards[$cardCounter]->cardIndex;
            $card1Code = $pokerCards[$cardCounter]->cardCode;
            $card1DeckPosition = $pokerCards[$cardCounter]->deckPosition;
            $game1Card = GameCard::InitShuffledCard($card1Index, $card1DeckPosition, $card1Code);
            $game1Card->Insert($gameInstance->d, $playerStatus->id, 1);
            $cardCounter++;

            $card2Index = $pokerCards[$cardCounter]->cardIndex;
            $card2Code = $pokerCards[$cardCounter]->cardCode;
            $card2DeckPosition = $pokerCards[$cardCounter]->deckPosition;
            $game2Card = GameCard::InitShuffledCard($card2Index, $card2DeckPosition, $card2Code);
            $game2Card->Insert($gameInstance->id, $playerStatus->id, 2);
            $cardCounter++;
        }
        // 2. pick the next 10 to be community cards
        for ($i = 0; $i < 5; $i++) {
            // store community cards database
            $playerCardNumber = $i + 1;

            $cardIndex = $pokerCards[$cardCounter]->cardIndex;
            $deckPosition = $pokerCards[$cardCounter]->deckPosition;
            $cardCode = $pokerCards[$cardCounter]->cardCode;
            $communityCard = GameCard::InitShuffledCard($cardIndex, $deckPosition, $cardCode);
            $communityCard->Insert($gameInstance->id, -1, $playerCardNumber);
            $cardCounter++;
        }
        /* save the rest without player info */
        for ($i = $cardCounter; $i < count($pokerCards); $i++) {
            $cardIndex = $pokerCards[$i]->cardIndex;
            $cardCode = $pokerCards[$i]->cardCode;
            $deckPosition = $pokerCards[$i]->deckPosition;
            $remainingCard = GameCard::InitShuffledCard($cardIndex, $deckPosition, $cardCode);
            $remainingCard->Insert($gameInstance->d, 'null', 'null');
        }
    }

    /**
     * This logic belongs to Poker Coordinator EndRound. No return object,
     * communcation via queue
     * Evalate whether community cards need to be dealt on table based on turns.
     */
    public function DealCommunityCards($gameInstance, $roundNumber) {
        if ($roundNumber > 2) {
            return;
        }
        $numberCards = $roundNumber + 3;
        $previousNumberCards = $gameInstance->numberCommunityCardsShown;
        $allCards = CardHelper::getCommunityCardDtos($gameInstance->id, $numberCards);
        $length = $numberCards == 3 ? 3 : 1;
        $cardsToSend = array_slice($allCards, $previousNumberCards, $length);
        return $cardsToSend;
    }

}
?>
