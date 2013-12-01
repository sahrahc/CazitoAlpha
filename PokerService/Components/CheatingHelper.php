<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

class CheatingHelper {

    /**
     *
     * @param type $gameInstance
     * @param type $suit
     * @return CheaterCardDto list
     */
    public static function getSuitForAllGameCards($gameInstance, $suit) {
        $cheaterCardDtos = null;
        $gameCards = $gameInstance->getAllGameCards();

        $counter = 0;
        foreach ($gameCards->playerHands as $pH) {
            $suitValue = null;
            if (!is_null($suit) && strpos($pH->pokerCard1->cardName, $suit) !== false) {
                $suitValue = $suit;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 1,
                            null, $suitValue);

            // second card
            $suitValue = null;
            if (!is_null($suit) && strpos($pH->pokerCard2->cardName, $suit) !== false) {
                $suitValue = $suit;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 2,
                            null, $suitValue);
        }
        return $cheaterCardDtos;
    }

    public static function pushRandomAce($gameInstanceId, $playerId, $cardNumber) {
        $pokerCards = CardHelper::getPlayerHand($playerId, $gameInstanceId, $cardNumber);
        $deck = EvalHelper::init_deck();
        $suits = array('spades', 'hearts','clubs','clubs');
        $suitsBit = array(0x1000, 0x2000, 0x4000, 0x8000);
        //$ranks = array('2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A');
        $randIndex = rand(0,3);
        $randSuit = $suits[$randIndex];
        $cardName = $randSuit . '_A';
        $cardIndex = EvalHelper::findDeckIndex($randSuit, $suitsBit[$randIndex], $deck);
        CardHelper::updatePlayerHand($playerId, $gameInstanceId, $cardNumber, $cardIndex, $cardName);
        return new CheaterCardDto($playerId, $cardNumber, $cardName, $randSuit);
    }
}

?>
