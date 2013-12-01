<?php

/* * ********************************************************************** */

/**
 * Poker evaluator helper functions. All functions in this class are static.
 */
class EvalHelper {
    public $ranks = array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A');

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }
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
        mysql_select_db('cazito', $con);
        $p = 53;
        for ($i = 0; $i < 7; $i++) {
            //self::log()->Debug(__FUNCTION__ . " - card index $i: " . $pCards[$i]);
            $nextIndex = $p + $pCards[$i];
            //self::log()->Debug(__FUNCTION__ . " - table index: " . $nextIndex);
            $sql = "SELECT * FROM HandRank WHERE i = $nextIndex";
            $result = mysql_query($sql);
            if (!$result) {
                die("Error retrieving from Hand Rank: " . mysql_error());
            }
            $row = mysql_fetch_array($result);
            $p = $row["v"];
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
        switch ($handCategory+1) {
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
    public static function evalRank($x) {
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
     * Deck is an array of 52 prime numbers. Tested, these values are correct
     * order: c, d, h, s from 2 to A
     * {"1":98306,"2":164099,"3":295429,"4":557831,"5":1082379,"6":2131213,"7":4228625,"8":8423187,"9":16812055,"10":33589533,"11":67144223,"12":134253349,"13":268471337,"14":81922,"15":147715,"16":279045,"17":541447,"18":1065995,"19":2114829,"20":4212241,"21":8406803,"22":16795671,"23":33573149,"24":67127839,"25":134236965,"26":268454953,"27":73730,"28":139523,"29":270853,"30":533255,"31":1057803,"32":2106637,"33":4204049,"34":8398611,"35":16787479,"36":33564957,"37":67119647,"38":134228773,"39":268446761,"40":69634,"41":135427,"42":266757,"43":529159,"44":1053707,"45":2102541,"46":4199953,"47":8394515,"48":16783383,"49":33560861,"50":67115551,"51":134224677,"52":268442665}
     * @return type 
     */
    public static function init2x2deck() {
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
    public static function find2x2DeckIndex($rank, $suitBit, $deck) {
        for ($i = 1; $i < 53; $i++) {
            $c = $deck[$i];
            if (($c & $suitBit) && (self::evalRank($c) == $rank))
                return( $i - 1); /* TODO -1? */
        }
        return( -1 );
    }

    public static function getSuitBit($suitChar) {
        switch($suitChar) {
            case 's':
                return 0x1000;
            case 'c':
                return 0x2000;
            case 'd':
                return 0x4000;
            case 'h':
                return 0x8000;
        }
    }
// ported from pokerlib.cpp
// slightly modified to print rank and suite for single card not an entire hand
// returns string value in suit underscore rank format (e.g., 'spades_7')
    public static function findCardCode($deck2x2Value) {
        $rank = array('2', '3', '4', '5', '6', '7', '8', '9', 'T', 'J', 'Q', 'K', 'A');

        //$r = ($deckValue >> 8) & 0xF;
        $r = (self::urshift($deck2x2Value, 8)) & 0xF;
        if ($deck2x2Value & 0x8000)
            $suit = 'c';
        else if ($deck2x2Value & 0x4000)
            $suit = 'd';
        else if ($deck2x2Value & 0x2000)
            $suit = 'h';
        else
            $suit = 's';

        return $rank[$r] . $suit;
    }

}
?>

