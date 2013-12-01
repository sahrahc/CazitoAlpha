<?php

/* Test poker eval functions by visually viewing result */
include_once(dirname(__FILE__) . '/../../Libraries/log4php/Logger.php');
include_once(dirname(__FILE__) . '/../../Helper/DataHelper.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger("EvalTester");
include_once('../Components/EvalHelper.php');
include_once('../DomainHelper/CardHelper.php');
include_once(dirname(__FILE__) . '/../Config.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
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

/* * ** documentation only:
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

/* player 2 $i = 
$gameCard2_1 = GameCard::InitPlayerCard(2, $pokerCards[8]->deckPosition, $pokerCards[8]->cardCode);
$gameCard2_2 = GameCard::InitPlayerCard(2, $pokerCards[9]->deckPosition, $pokerCards[9]->cardCode);
$playerHands2 = new PlayerHand(1, $gameCard2_1, $gameCard2_2);
CardHelper::identifyPlayerHand(array_merge($p, array($pokerCards[8]->deckPosition, $pokerCards[9]->deckPosition)), $playerHands2);
echo "Player 1 Cards: " . $pokerCards[8]->cardCode . " and " . $pokerCards[9]->cardCode . "<br/>";
echo "Hand info is $playerHands2->handInfo <br />";
echo "Hand category is $playerHands2->handCategory (" . $playerHands2->pokerHandType . ") rank $playerHands2->rankWithinCategory. <br \>";

$hH = CardHelper::getHigherCard($hH, $playerHands2);
echo "Higher card is $hH->winningPlayerId <br />";
 * 
 */
$hH;
$pokerCards;
$cc;
function initTest($indexCards = null) {
	global $hH;
	global $pokerCards;
	global $cc;
	if ($hH === null) {
		$hH = new HighestHand();		
	}
	$hH->handCategory = -1;
	$hH->rankWithinCategory = -1;
	$hH->winningPlayerId = -1;
	
	if ($indexCards === null) {
		$pokerCards = CardHelper::initRandomDeck();
	}
	else {
		$pokerCards = CardHelper::initTestingDeck($indexCards);
	}
	getCommunityCards();
}
function getCommunityCards() {
	global $pokerCards;
	global $cc;
	$cc = array();
	echo "Community cards: ";
	for ($i = 0; $i < 5; $i++) {
		$cc[$i] = $pokerCards[$i]->cardIndex;
		echo $pokerCards[$i]->cardCode . " ";
	}
	echo "<br/>";
	echo "Community card indexes: ";
	for ($i = 0; $i < 5; $i++) {
		echo $pokerCards[$i]->cardIndex . ' ';
	}
	echo "<br/><br/>";
}

/**
 * 
 * @param type $playerNumber - used as Id, determines the cards given: 
 *   6 + $playerNumber*2 
 *   6 + $playerNumber*2 + 1
 * @param type $pokerCards
 */
function processPlayer($playerNumber) {
	global $hH;
	global $pokerCards;
	global $cc;
	$i1 = 5 + $playerNumber * 2;
	$i2 = 5 + $playerNumber * 2 + 1;
	$gC1 = GameCard::InitPlayerCard($playerNumber, $pokerCards[$i1]->deckPosition, $pokerCards[$i1]->cardCode);
	$gC2 = GameCard::InitPlayerCard($playerNumber, $pokerCards[$i2]->deckPosition, $pokerCards[$i2]->cardCode);
	$playerHands = new PlayerHand($playerNumber, $gC1, $gC2);
	$cards1 = array_merge($cc, array($pokerCards[$i1]->cardIndex, $pokerCards[$i2]->cardIndex));
	CardHelper::identifyPlayerHand($cards1, $playerHands);

	echo "Player $playerNumber Cards: " . $pokerCards[$i1]->cardCode . " " . $pokerCards[$i2]->cardCode . "<br/>";
	echo " - Indexes: " . $pokerCards[$i1]->cardIndex . " " . $pokerCards[$i2]->cardIndex . "<br/>";
	echo " - Hand Type: " . $playerHands->pokerHandType . "<br/>";
	echo " - Hand Category: " . $playerHands->handCategory . "<br/>";
	echo " - Hand Info: " . $playerHands->handInfo . "<br/>";
	echo " - Hand Rank: $playerHands->rankWithinCategory. <br \>";

	$hH = CardHelper::getHigherCard($hH, $playerHands);
	echo "Higher card is by player $hH->winningPlayerId <br /><br/>";
}

/*
  http://en.wikipedia.org/wiki/List_of_poker_hands
 */
/**********************************************************************************************/
/**********************************************************************************************/
echo "/**********************************************************************************************/<br/>";
echo "Testing random deck <br/><br/>";
initTest();

processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing straight and royal flush<br/><br/>";
$indexCards[0] = array(
	'9h', 'Th', 'Jh', 'Qh', '3c',
	'8h', '3h', // player 1: 
	'Kh', 'Ah' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing 4 of a kind<br/><br/>";
$indexCards[0] = array(
	'3s', '3h', '3c', '2h', '2c',
	'8h', '3d', // player 1: 
	'2s', '2d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing full house <br/><br/>";
$indexCards[0] = array(
	'4s', '3h', '3c', '6h', '2c',
	'4h', '3d', // player 1: 
	'3s', '6d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing flush <br/><br/>";
$indexCards[0] = array(
	'4h', 'Th', 'Qh', '6h', '2h',
	'9h', '3d', // player 1: 
	'3s', '5h' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing straight<br/><br/>";
$indexCards[0] = array(
	'4s', '5h', '3c', '6h', '3s',
	'8h', '2h', // player 1: 
	'8s', '7h' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing 3 of a kind<br/><br/>";
$indexCards[0] = array(
	'4s', '3h', '3c', '6h', '2c',
	'8h', '3d', // player 1: 
	'3s', '7d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing two pair<br/><br/>";
$indexCards[0] = array(
	'4s', '3h', '3c', '6h', '2c',
	'8h', '4d', // player 1: 
	'3s', '6d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing one pair <br/><br/>";
$indexCards[0] = array(
	'4s', 'Jh', '3c', '6h', '2c',
	'8h', '3d', // player 1: 
	'3s', '7d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);

echo "/**********************************************************************************************/<br/>";
echo "Testing high card <br/><br/>";
$indexCards[0] = array(
	'4s', 'Jh', '3c', '6h', '2c',
	'8h', 'Ad', // player 1: 
	'9s', '7d' // player 2
	);
initTest($indexCards[0]);
processPlayer(0);
processPlayer(1);
?>
