<?php

$HR;
$HR_shm_key = 0xff3;
$HR_size = 32487834;
/*
 * Ported poker evaluator. 
 * FIXME: local cache the deck and handsrank.
 */

// ported from array.h
//#define	RANK(x)		((x >> 8) & 0xF)
function RANK2($x) {
    return (($x >> 8) & 0xF);
}
// ported from array.h
/*
** each of the thirteen card ranks has its own prime number
**
** deuce = 2
** trey  = 3
** four  = 5
** five  = 7
** ...
** king  = 37
** ace   = 41
*/
$primes = array( 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41 );

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
function init_deck2()
{
    $n = 0;
    $suit = 0x8000;
    global $primes;
    // left shifting << by one is the same as multiplication by 2
    // right shifting >> by one is the same as division by 2
    for ( $i = 0; $i < 4; $i++, $suit >>= 1 ) {
        for ( $j = 0; $j < 13; $j++, $n++ ) {
            $deck[$n] = $primes[$j] | ($j << 8) | $suit | (1 << (16+$j));
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
function findDeckIndex2($rank, $suit, $deck )
{
	for ( $i = 0; $i < 52; $i++ )
	{
		$c = $deck[$i];
		if ( ($c & $suit)  &&  (evalRank($c) == $rank) )
			return( $i );
	}
	return( -1 );
}

// ported from pokerlib.cpp
// slightly modified to print rank and suite for single card not an entire hand
// returns string value in suit underscore rank format (e.g., 'spades_7')
function findCardName2($deckValue)
{
    $rank = array('2','3','4','5','6','7','8','9','10','J','Q','K','A');

        $r = ($deckValue >> 8) & 0xF;
        if ( $deckValue & 0x8000 )
            $suit = 'clubs';
        else if ( $deckValue & 0x4000 )
            $suit = 'diamonds';
        else if ( $deckValue & 0x2000 )
            $suit = 'hearts';
        else
            $suit = 'spades';

        return $suit . '_' . $rank[$r];
}

// written as a function to force GC
function getTokens($f){
    gc_enable();
        echo "file name : c:\cazito\workspaceCpp\TwoPlusTwoGenerator\Debug\HANDRANKS" . $f . ".dat<br />";
        $HR_string = file_get_contents("c:\cazito\workspaceCpp\TwoPlusTwoGenerator\Debug\HANDRANKS" . $f . ".dat");
        $HR_tokens = explode("\n", $HR_string);
        unset($HR_string);
        gc_collect_cycles();
    return $HR_tokens;
}
function processFile($f) {
    gc_enable();
    $HR_tokens = getTokens($f);
        $maxI = count($HR_tokens)-1;
        echo "DEBUG InitTheEvaluator - Size of HR $f: " . $maxI . "<br />";
        for ($i = 0; $i < $maxI; $i++) {
            $HR_internal[$i] = intval($HR_tokens[$i]);
        }
        unset($HR_tokens);
        gc_collect_cycles();
    return $HR_internal;
}
// Initialize the 2+2 evaluator by loading the HANDRANKS.DAT file and
// mapping it to our 32-million member HR array. Call this once and
// forget about it.
function InitTheEvaluator()
{
    global $HR;

    //memset(HR, 0, sizeof(HR));
    // $HR = array_pad(array(0), 0, 32487834);
    // $HR = array('c:\wamp\www\PokerService\HANDRANKS.dat'); 

    // very slow, but only needs to be done once.
    gc_enable();
    for ($f = 0; $f < 17; $f++) {
        $HR_partial = processFile($f);
        echo "Size of HR processed: " . count($HR_partial) . "<br />";
        if ($f == 0) {
            $HR = $HR_partial;
        }
        else {
            echo 'Merging arrays... <br />';
            $HR = array_merge($HR, $HR_partial);
        }
        echo "Total Size of HR: " . count($HR) . "<br />";
    }
    
    // FILE * fin = fopen("HANDRANKS.DAT", "rb");
    // Load the HANDRANKS.DAT file data into the HR array
    // size_t bytesread = fread(HR, sizeof(HR), 1, fin);
    // fclose(fin);
}

// Given a group of 7 cards, return the hand category & rank. Let
// pCards be (a pointer to) an array of seven integers, each with
// a value between 1 and 52.
function getHandValue2($pCards)
{
    global $HR;
    global $HR_shm_key;
    global $HR_size;

    echo "HR shared memory key : $HR_shm_key <br />"; 
    $HR_shm_id = shmop_open($HR_shm_key, "a", 0, 0);
    echo "Reading from the shared memory segment\n";
    if (!$HR_shm_id){
        die("Could not open shared memory");
    }
    $HR_string = shmop_read($HR_shm_id, 0, $HR_size*PHP_INT_SIZE);
    $HR = unserialize($HR_string);
 
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[0] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[1] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[2] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[3] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[4] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[5] . "<br />";
    echo 'Debug PokerEval $pCards[0]: ' . $pCards[6] . "<br />";
    $p = $HR[53 + $pCards[0]];
    $p = $HR[$p + $pCards[1]];
    $p = $HR[$p + $pCards[2]];
    $p = $HR[$p + $pCards[3]];
    $p = $HR[$p + $pCards[4]];
    $p = $HR[$p + $pCards[5]];
    if (count($pCards) == 7) {
        $p = $HR[$p + $pCards[6]];
    }
    return $p;
}

/*
 * Helper functions for poker card management
 */

$pokerSuitTypes = array('clubs', 'diamonds', 'hearts', 'spades');
$DECK = init_deck();

function dealAllCards($numPlayers, $instanceId) {
    // FIXME: cache these!
    global $DECK;
    if (is_null($DECK)){
        $DECK = init_deck();
    }
    // total number of cards is 2* number of players + five community cards
    $totalCards = 2 * $numPlayers + 5;
    $indexArray = range(0, 51);
    shuffle($indexArray);
    echo "TEST dealAllCards *** The shuffled deck is: ";
    foreach ($indexArray as $number) {
        echo "$number ";
    }
    echo "<br /> <br />";
    $cardIndex = array_rand($indexArray, $totalCards);
    echo "TEST dealAllCards *** The picked cards are: ";
    foreach ($cardIndex as $number) {
        echo "$number ";
    }
    for ($i = 0; $i < $totalCards; $i++) {
        $card[$i] = findCardName($DECK[$cardIndex[$i]]);
        $returnList[$i] = new PokerCard($i+1, $cardIndex[$i], $card[$i]);
    }
    return $returnList;
}

?>

