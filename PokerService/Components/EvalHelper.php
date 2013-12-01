<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');

// Include Application Scripts
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger(__FILE__);

/* * ********************************************************************** */
/**
 * Poker evaluator helper functions. All functions in this class are static.
 */
class EvalHelper {

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /**
     * Get a deck of 52 cards randomly shuffled for use in a game. There is an index value for the card order after shuffling. This original order may be changed at the seedy saloon.
     * @return PokerCard array
     */
    public static function shuffleDeck() {
        // FIXME: cache these!
        $DECK = self::init_deck();

        $indexArray = range(1, 52);
        shuffle($indexArray);
        for ($i = 1; $i <= 52; $i++) {
            
            $cardCode = self::findCardCode($DECK[$indexArray[$i-1]]);

            $returnList[$i] = new PokerCard(null, $i, $cardCode);
            $returnList[$i]->cardIndex = $indexArray[$i-1];
        }
        return $returnList;
    }

    /**
     * Given the list of card codes, identify the hand
     * @param type $cardCodes
     * @return string
     */
    public static function evalHand($cardCodes) {return "2 Pair";}

    /**
     * 2+2 EVALUATOR ONLY
     * Given a group of 7 cards, return the hand info. Let pCards be (a pointer to) an array of seven integers, each with a value between 1 and 52.
     * FIXME: This function is to be replaced by a system that calculates hands first
     * and then compares relative values if the hands are the same.
     * @param type $pCards: array of integers range 1-52 which represent a card value in 2+2 Poker Evaluator
     * @return type
     */
    public static function getHandValue($pCards) {
        global $dbName;
        $con = connectToStateDB();
        mysql_select_db('cazito5_sprint3', $con);
        $p = 53;
        for ($i = 0; $i < 7; $i++) {
            self::log()->Debug(__FUNCTION__ . " - card index $i: " . $pCards[$i]);
            $nextIndex = $p + $pCards[$i];
            self::log()->Debug(__FUNCTION__ . " - table index: " . $nextIndex);
            $sql = "SELECT * FROM HandRank WHERE i = $nextIndex";
            $result = mysql_query($sql);
            if (!$result) {
                die("Error retrieving from Hand Rank: " . mysql_error());
            }
            $row = mysql_fetch_array($result);
            $p = $row["v"];
            self::log()->Debug(__FUNCTION__ . " - retrieved value is: $p");
        }
        mysql_select_db($dbName, $con);
        return $p;
    }

    /**
     * 2+2 EVALUATOR ONLY
     * Find the name of the hand category
     * @param type $handCategory
     * @return type
     */
    public static function findHandName($handCategory) {
        switch ($handCategory) {
            case 9: return 'Straight Flush';
            case 8: return '4 Of A Kind';
            case 7: return 'Full House';
            case 6: return 'Flush';
            case 5: return 'Straigt';
            case 4: return '3 Of A Kind';
            case 3: return '2 Pair';
            case 2: return '1 Pair';
            case 1: return 'High Card';
        }
        return 'BAD';
        /*
          if ($findIndex > 6185) return('HIGH_CARD');        // 1277 high card
          if ($findIndex > 3325) return('ONE_PAIR');         // 2860 one pair
          if ($findIndex > 2467) return('TWO_PAIR');         //  858 two pair
          if ($findIndex > 1609) return('THREE_OF_A_KIND');  //  858 three-kind
          if ($findIndex > 1599) return('STRAIGHT');         //   10 straights
          if ($findIndex > 322)  return('FLUSH');            // 1277 flushes
          if ($findIndex > 166)  return('FULL_HOUSE');       //  156 full house
          if ($findIndex > 10)   return('FOUR_OF_A_KIND');   //  156 four-kind
          return('STRAIGHT_FLUSH');                   //   10 straight-flushes
         */
    }

    /**
     * 2+2 EVALUATOR ONLY
     * Simulates an unsigned right shift in PHP which is a bitwise operation not natively supported.
     * @param type $integerValue
     * @param type $shiftBy
     * @return type
     */
    public static function urshift($integerValue, $shiftBy) {
        return ($integerValue >= 0) ? ($integerValue >> $shiftBy) :
                (($integerValue & 0x7fffffff) >> $shiftBy) |
                (0x40000000 >> ($shiftBy - 1));
    }

    /*     * ********************************************************************** */

    /**
     * 2+2 EVALUATOR ONLY
     * ported from array.h
     * #define	RANK(x)		((x >> 8) & 0xF)
     * @param type $x
     * @return type 
     */
    private static function evalRank($x) {
        return ((self::urshift($x, 8)) & 0xF);
    }

    /* function urshift($a, $b)
      {
      $z = hexdec(80000000);
      if ($z & $a)
      {
      $a = ($a >> 1);
      $a &= (~$z);
      $a |= 0x40000000;
      $a = ($a >> ($b - 1));
      } else {
      $a = ($a >> $b);
      }
      return $a;
      }
     */

// ported from array.h
    /*
     * * each of the thirteen card ranks has its own prime number
     * *
     * * deuce = 2
     * * trey  = 3
     * * four  = 5
     * * five  = 7
     * * ...
     * * king  = 37
     * * ace   = 41
     */
//$primes = array( 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41 );
// ported from pokerli.cpp
//
//   This routine initializes the deck.  A deck of cards is
//   simply an integer array of length 52 (no jokers).  This
//   array is populated with each card, using the following
//   scheme:
//
//   An integer is made up of four bytes.  The high-order
//   bytes are used to hold the rank bit pattern, whereas
//   the low-order bytes hold the suit/rank/prime value
//   of the card.
//
//   +--------+--------+--------+--------+
//   |xxxbbbbb|bbbbbbbb|cdhsrrrr|xxpppppp|
//   +--------+--------+--------+--------+
//
//   p = prime number of rank (deuce=2,trey=3,four=5,five=7,...,ace=41)
//   r = rank of card (deuce=0,trey=1,four=2,five=3,...,ace=12)
//   cdhs = suit of card
//   b = bit turned on depending on rank of card
//
// JMD: added "void" return type
    /**
     * 2+2 EVALUATOR ONLY
     *
     * @return type 
     */
    public static function init_deck() {
        $n = 1;
        $suit = 0x8000;
        $primes = array(2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41);
        // left shifting << by one is the same as multiplication by 2
        // right shifting >> by one is the same as division by 2
        // for ( $i = 0; $i < 4; $i++, $suit >>= 1 ) {
        for ($i = 0; $i < 4; $i++, $suit = self::urshift($suit, 1)) {
            for ($j = 0; $j < 13; $j++, $n++) {
                $deck[$n] = $primes[$j] | ($j << 8) | $suit | (1 << (16 + $j));
            }
        }
        return $deck;
    }

// ported from pokerlib.cpp
    /*
     * Randomly generates all the cards in a single session to minimize duplicates
     * and stores them in database. Returns the list of cards because used
     * by calling function.
     * FIXME: this will be refactored to deal cards as needed but
     * duplicates will be checked
     */
    public static function findDeckIndex($rank, $suit, $deck) {
        for ($i = 1; $i < 53; $i++) {
            $c = $deck[$i];
            if (($c & $suit) && (self::evalRank($c) == $rank))
                return( $i );
        }
        return( -1 );
    }

// ported from pokerlib.cpp
// slightly modified to print rank and suite for single card not an entire hand
// returns string value in suit underscore rank format (e.g., 'spades_7')
    private static function findCardCode($deckValue) {
        $rank = array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A');

        //$r = ($deckValue >> 8) & 0xF;
        $r = (self::urshift($deckValue, 8)) & 0xF;
        if ($deckValue & 0x8000)
            $suit = 'c';
        else if ($deckValue & 0x4000)
            $suit = 'd';
        else if ($deckValue & 0x2000)
            $suit = 'h';
        else
            $suit = 's';

        return $rank[$r] . $suit;
    }

}
?>

