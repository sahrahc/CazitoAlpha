<?php
/* Test poker eval functions by visually viewing result */
include_once(dirname(__FILE__) . '/../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger("EvalTester");
include_once('/../Components/EvalHelper.php');
include_once(dirname(__FILE__) . '/../Data/AllInclude.php');
include_once(dirname(__FILE__) . '/../DomainModel/AllInclude.php');
/*
$DECK = init_deck();

-- init_deck - uncomment to view deck.
echo 'There are ' . count($DECK) . ' elements in the deck. <br />';
echo 'The initialized deck is as follows: <br /> <br />';
for($i = 0; $i < count($DECK); $i++) {
    echo 'value for element ' . $i . ' is ' . $DECK[$i] . ' = ' . findCardName($DECK[$i]) . '<br />';
}
// 
echo "club - J is " . findDeckIndex(9, 0x8000, $DECK) . " <br />";
echo "spades - 8 is " . findDeckIndex(6, 0x1000, $DECK) . " <br />";
echo "diamond - 2 is " . findDeckIndex(0, 0x4000, $DECK) . " <br />";
echo "hearts - A is " . findDeckIndex(12, 0x2000, $DECK)  . " <br />";
*/

/**** documentation only:
 * #define	STRAIGHT_FLUSH	1
#define	FOUR_OF_A_KIND	2
#define	FULL_HOUSE	3
#define	FLUSH		4
#define	STRAIGHT	5
#define	THREE_OF_A_KIND	6
#define	TWO_PAIR	7
#define	ONE_PAIR	8
#define	HIGH_CARD	9

#define CLUB	0x8000
#define DIAMOND 0x4000
#define HEART   0x2000
#define SPADE   0x1000

#define Deuce	0
#define Trey	1
#define Four	2
#define Five	3
#define Six	4
#define Seven	5
#define Eight	6
#define Nine	7
#define Ten	8
#define Jack	9
#define Queen	10
#define King	11
#define Ace	12

$cardIndex = findDeckIndex(10, 0x2000, $DECK);
echo 'The following index should be a queen of hearts: ' . $cardIndex . '<br />';

$cardName = findCardName($DECK[$cardIndex]);
echo 'The card index converted back is: ' . $cardName . '<br />';
 */

/* testing hand ranks */
/*
$pCards[0] = findDeckIndex(8, 0x8000, $DECK); 
$pCards[1] = findDeckIndex(11, 0x4000, $DECK);
$pCards[2] = findDeckIndex(4, 0x2000, $DECK);
$pCards[3] = findDeckIndex(9, 0x2000, $DECK);
$pCards[4] = findDeckIndex(5, 0x1000, $DECK);
$pCards[5] = findDeckIndex(10, 0x1000, $DECK);
$pCards[6] = findDeckIndex(12, 0x1000, $DECK);
$pCards[4] = findDeckIndex(12, 0x4000, $DECK); // diamonds A
$pCards[6] = findDeckIndex(9, 0x1000, $DECK); // spades J
$pCards[3] = findDeckIndex(11, 0x8000, $DECK); // clubs K
$pCards[2] = findDeckIndex(10, 0x8000, $DECK); // club Q
$pCards[5] = findDeckIndex(4, 0x1000, $DECK); //spades 6
*/
/*
$pCards[5] = findDeckIndex(8, 0x8000, $DECK); // clubs 10
$pCards[6] = findDeckIndex(4, 0x8000, $DECK); //clubs 6
$pCards[1] = findDeckIndex(5, 0x8000, $DECK); // clubs 7
$pCards[0] = findDeckIndex(1, 0x8000, $DECK); //clubs 3
*/
$pokerCards = EvalHelper::shuffleDeck(1);
for ($i = 0; $i< count($pokerCards); $i++) {
    $p[$i] = $pokerCards[$i]->cardIndex;
    echo "Hand " . $pokerCards[$i]->cardName . " has index: " . $pokerCards[$i]->cardIndex . '<br />';
}
$ph = new playerHand(1, null, null);
$ph = CardHelper::identifyPlayerHand($p, $ph);
echo "Hand info is $ph->handInfo <br />";
echo "Hand category is $ph->handCategory (" . $ph->pokerHandType . ") rank $ph->rankWithinCategory. <br \>";

?>
