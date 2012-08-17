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
     * @param int $gInstanceId The game
     * @param int $numCards The number of cards to return
     * @return array[PokerCard]
     */
    public static function getCommunityCards($gInstanceId, $numCards) {

        self::log()->Debug(__FUNCTION__ . " - Requested card count: $numCards");

        if (is_null($numCards) || $numCards == 0) {
            return null;
        }

        $resultCard = executeSQL("SELECT * FROM GameCard WHERE GameInstanceId = $gInstanceId
                AND PlayerId = -1 AND CardNumber <= $numCards", __FUNCTION__ . ":
                Error selecting $numCards cards from GameCard instance id $gInstanceId");

        // populate the return object
        $counter = 0;
        while ($rowCard = mysql_fetch_array($resultCard)) {
            self::log()->Debug(__FUNCTION__ . " - Card Number " . $rowCard['CardNumber']);

            $pokerCards[$counter] = new PokerCard($rowCard['CardNumber'],
                    $rowCard['CardIndex'], $rowCard['CardName']);
            $counter++;
        }
        return $pokerCards;
    }

    /**
     * Given a set of seven cards, return the hand information, which includes evaluator's specific data (e.g., 2+2 evaluator's info, category and rank within the category) and the English name of the hand.
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
     * Takes the highest hand to date and returns it updated if the player hand has a higher value.
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

}

?>
