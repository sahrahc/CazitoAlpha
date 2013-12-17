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
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $p = $row["v"];
        }
        mysql_select_db($dbName, $con);
        return $p;
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

}
?>

