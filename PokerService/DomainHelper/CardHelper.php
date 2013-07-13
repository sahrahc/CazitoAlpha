<?php

/* * ************************************************************************************** */

/**
 * Helper class with static methods for retrieving and processing game cards.  The card helper retrieves data and context from the data store while applying poker rules.
 */
class CardHelper {

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /**
     * Returns the number of community cards for a game instance starting with the first up to the number requested.
     * Used to retrieve the fold, turn or river cards to the client.
     * @param int $gInstId The game
     * @param int $numCards The number of cards to return
     * @return array[string] array of card names
     */
    public static function getCommunityCardDtos($gInstId, $numCards) {

        self::log()->Debug(__FUNCTION__ . " - Requested card count: $numCards");

        if (is_null($numCards) || $numCards == 0) {
            return null;
        }

        $resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = -1 AND PlayerCardNumber <= $numCards
                ORDER BY PlayerId, PlayerCardNumber", __FUNCTION__ . ":
                Error selecting $numCards cards from GameCard instance id $gInstId");

// populate the return object
        $counter = 0;
        while ($rowCard = mysql_fetch_array($resultCard)) {
            $cardCodes[$counter] = $rowCard['CardCode'];
            $counter++;
        }
        return $cardCodes;
    }

    /**
     * FIXME: 2+2 EVALUATOR SPECIFIC - first parameter cards should be changed to cardCodes
     * Wrapper for Eval Helper API to convert it into domain specific object.
     * Given a set of seven cards, return the hand information, which includes evaluator's specific data (e.g., 2+2 evaluator's info, category and rank within the category) and the English name of the hand.
     * This function and the next getHigherCard are used together.
     * @param array(int) $cards The list of seven cards.
     * @param PlayerHand $playerHand The player information, which gets updated and returned
     * @return PlayerHand The updated player hand.
     */
    public static function identifyPlayerHand($cards, &$playerHand) {
        self::log()->debug(__FUNCTION__ . " - cards given " . json_encode($cards));
        self::log()->debug(__FUNCTION__ . " - player hands given " . json_encode($playerHand));
        //$pH = $playerHand;

        $playerHand->handInfo = EvalHelper::getHandValue($cards);
        $playerHand->handCategory = EvalHelper::urshift($playerHand->handInfo, 12); // 12
        $playerHand->pokerHandType = EvalHelper::findHandName($playerHand->handCategory);
        $playerHand->rankWithinCategory = $playerHand->handInfo & 0x00000FFF;

        //return $pH;
    }

    /**
     * Takes the highest hand to date and compares to another given hand. Returns the highest card after the comparison.
     * @param HighestHand $hH An object with the player hand info without the player
     * @param PlayerHand $playerHand The player info, poker cards and hand info.
     * @return type
     */
    public static function getHigherCard($hH, $playerHand) {
        $hH2 = $hH;
        if ($hH->handCategory < $playerHand->handCategory) {
            $hH2->handCategory = $playerHand->handCategory;
            $hH2->handRank = $playerHand->rankWithinCategory;
            $hH2->winningPlayerId = $playerHand->playerId;
        } else if ($hH->handCategory == $playerHand->handCategory AND
                $hH->handRank < $playerHand->rankWithinCategory) {
            $hH2->handCategory = $playerHand->handCategory;
            $hH2->handRank = $playerHand->rankWithinCategory;
            $hH2->winningPlayerId = $playerHand->playerId;
        }
        return $hH2;
    }

    /**
     * Get the player hand information. Used on every non virtual player 
     * at the beginning of the game.
     * @param type $pId: The player whose hand is being sent
     * @param type $gInstId
     * @return PlayerHandDto
     */
    public static function getPlayerHandDto($pId, $gInstId) {
        $result = executeSQL("SELECT * from GameCard where GameInstanceId = $gInstId
                AND PlayerId = $pId ORDER BY PlayerCardNumber", __FUNCTION__ . "
                : ERROR selecting game card for game instance id $gInstId and player
                id $pId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }

        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $pokerCardCode[$i++] = $row["CardCode"];
        }

        return new PlayerHandDto($pId, $pokerCardCode[0], $pokerCardCode[1]);
    }

    /**
     * Get one of a player's two cards
     * @param type $pId
     * @param type $gInstId
     * @param type $cardNum
     * @return GameCard 
     */
    public static function getPlayerCard($pId, $gInstId, $cardNum) {
        $resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = $pId AND PlayerCardNumber = $cardNum", __FUNCTION__ . ":
                Error selecting GameCards for instance id $gInstId player id $pId
                and card number $cardNum");

// populate the return object
        $rowCard = mysql_fetch_array($resultCard);

        $pokerCard = GameCard::InitPlayerCard($rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
        return $pokerCard;
    }

    /**
     * Update the value of one of a player hand's two cards.
     * @param type $pId
     * @param type $gInstId
     * @param type $cNumber
     * @param type $cIndex
     * @param type $cCode 
     */
    public static function updatePlayerCard($pId, $gInstId, $cNumber, $cIndex, $cCode) {
        $resultCard = executeSQL("UPDATE GameCard SET CardIndex = $cIndex, CardCode = '$cCode'
                WHERE GameInstanceId = $gInstId AND PlayerId = $pId and
                PlayerCardNumber = $cNumber", __FUNCTION__ . ": 
                Error updating game card for instance $gInstId,
                player $pId and card number $cNumber");
    }

    /**
     * For a given instance, return the list of all cards by code used in a game instance.
     * @param type $gInstId
     * @param type $unassigned: option to return all cards in the deck, not just assigned ones (default).
     * @return string array of card codes
     */
    public static function getCardCodesForInstance($gInstId, $unassigned) {
        $error_msg = __FUNCTION__ . ": Error selecting GameCard for instance $gInstId";
        if ($unassigned) {
            $result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                    AND PlayerId IS NULL
                ORDER BY DeckPosition", $error_msg);
        } else {
            $result = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId IS NOT NULL
                ORDER BY PlayerId, PlayerCardNumber", $error_msg);
        }
        $pokerCardCodes = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
//$card = new PokerCard($row["PlayerCardNumber"], $row["DeckPosition"], $row["CardCode"]);
            $pokerCardCodes[$counter++] = $row["CardCode"];
        }
        return $pokerCardCodes;
    }

    /**
     * Only used once, to get all the cards in order to identify the winner and publish everyone's hands at the end of the game.
     * @return GameInstanceCards 
     */
    public static function getGameCardsForInstance($gInstId, $excludeFoldCondition = false) {
        // get all the cards, order with community cards first.
        $result = executeSQL("SELECT g.*, ps.Status AS Status FROM GameCard g 
                LEFT JOIN PlayerState ps ON g.GameInstanceId = ps.GameInstanceId
                AND g.PlayerId = ps.PlayerId WHERE g.GameInstanceId = $gInstId
                AND g.PlayerId is not null
                ORDER BY g.PlayerId, PlayerCardNumber", __FUNCTION__ . "
                : Error selecting all GameCard for instance id $gInstId");

        // initialize
        $playerIndex = 0; // index on array of players
        $ccIndex = 0;     // index on array of community cards
        $playerHands = null;
        $communityCards = null;
        $prevPlayerId = null;
        // this won't work if $result is not sorted by playerid
        while ($rowCard = mysql_fetch_array($result)) {
            $isNotFolded = $rowCard["Status"] != PlayerStatusType::FOLDED;
            if ($excludeFoldCondition) {$isNotFolded = true;}
            if ($rowCard["PlayerId"] == -1) {
                // process community cards
                $communityCards[$ccIndex] = GameCard::InitPlayerCard($rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
                $communityCards[$ccIndex++]->cardIndex = $rowCard["CardIndex"];
            } else if ($rowCard["Status"] != PlayerStatusType::LEFT &&
                    $isNotFolded) {
                // one entity for both cards.
                if (is_null($prevPlayerId) || $prevPlayerId != $rowCard["PlayerId"]) {
                    $playerIndex = is_null($prevPlayerId) ? 0 : $playerIndex + 1;
                    // Not validating playercardnumber, in poker there is only two
                    // and the insert needs to make sure the values are only 1 and 2 and
                    // both are present. Anything else is data becoming corrupted.
                    $gameCard1 = GameCard::InitPlayerCard($rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
                    $gameCard1->cardIndex = $rowCard["CardIndex"];
                    $playerHands[$playerIndex] = new PlayerHand($rowCard['PlayerId'], $gameCard1, null);
                } else {
                    $gameCard2 = GameCard::InitPlayerCard(
                            $rowCard['PlayerCardNumber'], $rowCard['DeckPosition'], $rowCard['CardCode']);
                    $gameCard2->cardIndex = $rowCard["CardIndex"];
                    $playerHands[$playerIndex]->pokerCard2 = $gameCard2;
                    // increase index when second and last card is found
                }
                $prevPlayerId = $rowCard["PlayerId"];
            }
        }

        return new GameInstanceCards($communityCards, $playerHands);
    }

}

?>
