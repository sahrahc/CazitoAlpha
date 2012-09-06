<?php

/* Type: Object and partial response Dto.
 * Primary Table: PlayerState
 * Description: the cards held by a player.  */

class PlayerHandDto {

    public $playerId;
    public $pokerCard1Dto;
    public $pokerCard2Dto;
    // optional
    public $pokerHandType;
    public $isWinningHand;

    /**
     *
     * @param type $playerHands
     * @return PlayerHandDto 
     */
    public static function mapPlayerHands($playerHands) {
        $obj = null;
        for ($i = 0, $l = count($playerHands); $i < $l; $i++) {
            $obj[$i] = new PlayerHandDto($playerHands[$i]->playerId,
                    new PokerCardDto(1, $playerHands[$i]->pokerCard1->cardCode),
                    new PokerCardDto(2, $playerHands[$i]->pokerCard2->cardCode)
                    );
            $obj[$i]->pokerHandType = $playerHands[$i]->pokerHandType;
            $obj[$i]->isWinningHand = $playerHands[$i]->isWinningHand;
        }
        return $obj;        
    }

    function __construct($playerId, $pokerCard1Dto, $pokerCard2Dto) {
        $this->playerId = $playerId;
        $this->pokerCard1Dto = $pokerCard1Dto;
        $this->pokerCard2Dto = $pokerCard2Dto;
    }

}

?>
