<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

include_once(dirname(__FILE__) . '/../Components/EvalHelper.php');

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
     * @return array[PokerCardDto]
     */
    public static function getCommunityCardDtos($gInstId, $numCards) {

        self::log()->Debug(__FUNCTION__ . " - Requested card count: $numCards");

        if (is_null($numCards) || $numCards == 0) {
            return null;
        }

        $resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = -1 AND PlayerCardNumber <= $numCards", __FUNCTION__ . ":
                Error selecting $numCards cards from GameCard instance id $gInstId");

// populate the return object
        $counter = 0;
        while ($rowCard = mysql_fetch_array($resultCard)) {
            self::log()->Debug(__FUNCTION__ . " - Card Number " . $rowCard['PlayerCardNumber']);

            $pokerCardDtos[$counter] = new PokerCardDto($rowCard['PlayerCardNumber'],
                            $rowCard['CardCode']);
            $counter++;
        }
        return $pokerCardDtos;
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
    public static function identifyPlayerHand($cards, $playerHand) {
        self::log()->debug(__FUNCTION__ . " - cards given " . json_encode($cards));
        self::log()->debug(__FUNCTION__ . " - player hands given " . json_encode($playerHand));
        $pH = $playerHand;

        $pH->handInfo = EvalHelper::getHandValue($cards);
        $pH->handCategory = EvalHelper::urshift($playerHand->handInfo, 12); // 12
        $pH->pokerHandType = EvalHelper::findHandName($playerHand->handCategory);
        $pH->rankWithinCategory = $pH->handInfo & 0x00000FFF;

        return $pH;
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
     * Get the player hand information. Used on every non virtual player at the beginning of the game.
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

        $row = mysql_fetch_array($result);
        $pokerCard1Dto = new PokerCardDto($row["PlayerCardNumber"], $row["CardCode"]);
        
        $row = mysql_fetch_array($result);
        $pokerCard2Dto = new PokerCardDto($row["PlayerCardNumber"], $row["CardCode"]);
        
        return new PlayerHandDto($pId, $pokerCard1Dto, $pokerCard2Dto);
    }

    /**
     * Get one of a player's two cards
     * @param type $pId
     * @param type $gInstId
     * @param type $cardNum
     * @return PokerCard 
     */
    public static function getPlayerCard($pId, $gInstId, $cardNum) {
        $resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstId
                AND PlayerId = $pId AND PlayerCardNumber = $cardNum", __FUNCTION__ . ":
                Error selecting GameCards for instance id $gInstId player id $pId
                and card number $cardNum");

// populate the return object
        $rowCard = mysql_fetch_array($resultCard);

        $pokerCard = new PokerCard($rowCard['PlayerCardNumber'],
                        $rowCard['DeckPosition'], $rowCard['CardCode']);
        $pokerCard->cardIndex = $rowCard['CardIndex'];
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
            $result = executeSQL("SELECT * FROM GAMECARD WHERE GameInstanceId = $gInstId
                    AND PlayerId IS NULL
                ORDER BY DeckPosition", $error_msg);
        } else {
            $result = executeSQL("SELECT * FROM GAMECARD WHERE GameInstanceId = $gInstId
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
}
?>
