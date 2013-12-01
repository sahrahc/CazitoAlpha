<?php

/* Type: Object and partial response Dto.
 * Primary Table: PlayerState
 * Description: the cards held by a player.  */

class PlayerHandDto {

    public $playerId;
    public $pokerCard1Code;
    public $pokerCard2Code;
    // optional
    public $pokerHandType;

    /**
     *
     * @param type $playerHands
     * @return PlayerHandDto 
     */
    public static function mapPlayerHands($playerHands) {
        $obj = null;
        for ($i = 0, $l = count($playerHands); $i < $l; $i++) {
            $obj[$i] = new PlayerHandDto($playerHands[$i]->playerId,
                    $playerHands[$i]->pokerCard1->cardCode,
                    $playerHands[$i]->pokerCard2->cardCode
                    );
            $obj[$i]->pokerHandType = $playerHands[$i]->pokerHandType;
        }
        return $obj;        
    }

    function __construct($playerId, $pokerCard1Code, $pokerCard2Code) {
        $this->playerId = $playerId;
        $this->pokerCard1Code = $pokerCard1Code;
        $this->pokerCard2Code = $pokerCard2Code;
    }

}

?>
