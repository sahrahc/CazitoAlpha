<?php

/* Type: Object and partial response Dto.
 * Primary Table: GameCard
 * Description: card within the game. The card number is for the player or community card
 * and is necessary because a card's position, not just the value, must be given.
 */

class GameCard {
    public $playerCardNumber;
    /* the position in the deck. Cheating will cause the cards in the deck to be used out of order */
    public $deckPosition;
    public $cardCode;
    public $cardIndex; // used after shuffling deck, only for 2+2
    
    public static function InitShuffledCard($cardIndex, $deckPosition, $cardCode) {
        $gameCard = new GameCard();
        $gameCard->cardIndex = $cardIndex;
        $gameCard->deckPosition = $deckPosition;
        $gameCard->cardCode = $cardCode;
        return $gameCard;
    }
    public static function InitPlayerCard($playerCardNumber, $deckPosition, $cardCode) {
        $gameCard = new GameCard();
        $gameCard->playerCardNumber = $playerCardNumber;
        $gameCard->deckPosition = $deckPosition;
        $gameCard->cardCode = $cardCode;
        return $gameCard;
    }
    /**
     * The player and game instance only needed to save in database
     * cardIndex to be removed when replacing 2+2 evaluator
     * @param type $gameInstanceId
     * @param type $playerId
     * @param type $cardIndex
     */
    public function Insert($gameInstanceId, $playerId, $playerCardNumber) {
        executeSQL("INSERT INTO GameCard (GameInstanceId, PlayerId, PlayerCardNumber, 
                    DeckPosition, CardCode, CardIndex) VALUES ($gameInstanceId, 
                $playerId, $playerCardNumber, $this->deckPosition, '$this->cardCode', 
                $this->cardIndex)", __CLASS__ . "-" .  __FUNCTION__ . "
                    : Error inserting GameCard instance ID $gameInstanceId");
            }
}

?>
