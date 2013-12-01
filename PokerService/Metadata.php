<?php

/*
 * game card codes to numbers
 */

$itemTypeSuit = array(
	ItemType::CLUB_MARKER => 'clubs',
	ItemType::HEART_MARKER => 'hearts',
	ItemType::DIAMOND_MARKER => 'diamonds'
);

$itemTypeTimeOut = array(
	ItemType::CLUB_MARKER => $cClubMarkerTimeOut,
	ItemType::HEART_MARKER => $cHeartMarkerTimeOut,
	ItemType::DIAMOND_MARKER => $cDiamondMarkerTimeOut
);

/* https://github.com/chenosaurus/poker-evaluator/blob/master/lib/PokerEvaluator.js */
$CARDS = array(
    "2c"=> 1,
    "2d"=> 2,
    "2h"=> 3,
    "2s"=> 4,
    "3c"=> 5,
    "3d"=> 6,
    "3h"=> 7,
    "3s"=> 8,
    "4c"=> 9,
    "4d"=> 10,
    "4h"=> 11,
    "4s"=> 12,
    "5c"=> 13,
    "5d"=> 14,
    "5h"=> 15,
    "5s"=> 16,
    "6c"=> 17,
    "6d"=> 18,
    "6h"=> 19,
    "6s"=> 20,
    "7c"=> 21,
    "7d"=> 22,
    "7h"=> 23,
    "7s"=> 24,
    "8c"=> 25,
    "8d"=> 26,
    "8h"=> 27,
    "8s"=> 28,
    "9c"=> 29,
    "9d"=> 30,
    "9h"=> 31,
    "9s"=> 32,
    "Tc"=> 33,
    "Td"=> 34,
    "Th"=> 35,
    "Ts"=> 36,
    "Jc"=> 37,
    "Jd"=> 38,
    "Jh"=> 39,
    "Js"=> 40,
    "Qc"=> 41,
    "Qd"=> 42,
    "Qh"=> 43,
    "Qs"=> 44,
    "Kc"=> 45,
    "Kd"=> 46,
    "Kh"=> 47,
    "Ks"=> 48,
    "Ac"=> 49,
    "Ad"=> 50,
    "Ah"=> 51,
    "As"=> 52
);

$pokerCardName = array(
	'2h' => 'hearts_2',
	'3h' => 'hearts_3',
	'4h' => 'hearts_4',
	'5h' => 'hearts_5',
	'6h' => 'hearts_6',
	'7h' => 'hearts_7',
	'8h' => 'hearts_8',
	'9h' => 'hearts_9',
	'Th' => 'hearts_10',
	'Jh' => 'hearts_J',
	'Qh' => 'hearts_Q',
	'Kh' => 'hearts_K',
	'Ah' => 'hearts_A',
	// diamonds
	'2d' => 'diamonds_2',
	'3d' => 'diamonds_3',
	'4d' => 'diamonds_4',
	'5d' => 'diamonds_5',
	'6d' => 'diamonds_6',
	'7d' => 'diamonds_7',
	'8d' => 'diamonds_8',
	'9d' => 'diamonds_9',
	'Td' => 'diamonds_10',
	'Jd' => 'diamonds_J',
	'Qd' => 'diamonds_Q',
	'Kd' => 'diamonds_K',
	'Ad' => 'diamonds_A',
	// clubs
	'2c' => 'clubs_2',
	'3c' => 'clubs_3',
	'4c' => 'clubs_4',
	'5c' => 'clubs_5',
	'6c' => 'clubs_6',
	'7c' => 'clubs_7',
	'8c' => 'clubs_8',
	'9c' => 'clubs_9',
	'Tc' => 'clubs_10',
	'Jc' => 'clubs_J',
	'Qc' => 'clubs_Q',
	'Kc' => 'clubs_K',
	'Ac' => 'clubs_A',
	//spades
	'2s' => 'spades_2',
	'3s' => 'spades_3',
	'4s' => 'spades_4',
	'5s' => 'spades_5',
	'6s' => 'spades_6',
	'7s' => 'spades_7',
	'8s' => 'spades_8',
	'9s' => 'spades_9',
	'Ts' => 'spades_10',
	'Js' => 'spades_J',
	'Qs' => 'spades_Q',
	'Ks' => 'spades_K',
	'As' => 'spades_A',
);

$pokerCardCode = array(
	'hearts_2' => '2h',
	'hearts_3' => '3h',
	'hearts_4' => '4h',
	'hearts_5' => '5h',
	'hearts_6' => '6h',
	'hearts_7' => '7h',
	'hearts_8' => '8h',
	'hearts_9' => '9h',
	'hearts_10' => 'Th',
	'hearts_J' => 'Jh',
	'hearts_Q' => 'Qh',
	'hearts_K' => 'Kh',
	'hearts_A' => 'Ah',
	// diamonds
	'diamonds_2' => '2d',
	'diamonds_3' => '3d',
	'diamonds_4' => '4d',
	'diamonds_5' => '5d',
	'diamonds_6' => '6d',
	'diamonds_7' => '7d',
	'diamonds_8' => '8d',
	'diamonds_9' => '9d',
	'diamonds_10' => 'Td',
	'diamonds_J' => 'Jd',
	'diamonds_Q' => 'Qd',
	'diamonds_K' => 'Kd',
	'diamonds_A' => 'Ad',
	// clubs
	'clubs_2' => '2c',
	'clubs_3' => '3c',
	'clubs_4' => '4c',
	'clubs_5' => '5c',
	'clubs_6' => '6c',
	'clubs_7' => '7c',
	'clubs_8' => '8c',
	'clubs_9' => '9c',
	'clubs_10' => 'Tc',
	'clubs_J' => 'Jc',
	'clubs_Q' => 'Qc',
	'clubs_K' => 'Kc',
	'clubs_A' => 'Ac',
	//spades
	'spades_2' => '2s',
	'spades_3' => '3s',
	'spades_4' => '4s',
	'spades_5' => '5s',
	'spades_6' => '6s',
	'spades_7' => '7s',
	'spades_8' => '8s',
	'spades_9' => '9s',
	'spades_10' => 'Ts',
	'spades_J' => 'Js',
	'spades_Q' => 'Qs',
	'spades_K' => 'Ks',
	'spades_A' => 'As',
);

$HANDTYPES = array(
    "invalid hand",
    "high card",
    "one pair",
    "two pairs",
    "three of a kind",
    "straight",
    "flush",
    "full house",
    "four of a kind",
    "straight flush"
  );
// the array values are what is returned by the service	
class PokerHandType {

	private static $enum = array(
		// Royal Flush not mapped
		1 => 'Straight Flush', // 5 same suit in sequence
		2 => 'Four Of A Kind', // 4 of same rank
		3 => 'Full House', // 3 and 2 of same rank
		4 => 'Flush', // 5 same suit
		5 => 'Straight', // in sequence, different suits
		6 => 'Three Of A Kind', // 3 of same rank
		7 => 'Two Pair', // 2 pairs of same rank
		8 => 'One Pair',
		9 => 'High Card'
	);

}
// the values are what is returned by the service
// usage: var $player = PlayerStatusType::CHECKED;
final class PlayerStatusType {

	const WAITING = 'Waiting'; // waiting for game to start
	const SEATED = 'Seated'; // seated on middle of the game
	const LOST = 'Lost';
	const WON = 'Won';
	const LEFT = 'Left';
	const SKIPPED = 'Skipped'; // first and second time out
	// matches the poker action types
	const CHECKED = 'Checked';
	const CALLED = 'Called';
	const RAISED = 'Raised';
	const FOLDED = 'Folded';
	const BLIND_BET = 'BlindBet';

}

// the values are what is returned by the service
final class PokerActionType {

	const BLIND_BET = 'BlindBet';
	const CALLED = 'Called';
	const RAISED = 'Raised';
	const CHECKED = 'Checked';
	const FOLDED = 'Folded';

}

final class GameStatus {

	const IN_PROGRESS = 'InProgress';
	const NONE = 'None';
	const STARTED = 'Started';
	const ENDED = 'Ended';

}

final class ItemType {

	const ACE_PUSHER = 'AcePusher'; // Sly McGuffin's Ace Pusher
	const HEART_MARKER = 'HeartMarker';
	const LOAD_CARD_ON_SLEEVE = 'LoadCardOnSleeve'; // Old Man Chalmers Reliable Card Pusher
	const USE_CARD_ON_SLEEVE = 'UseCardOnSleeve';
	const CLUB_MARKER = 'ClubMarker';
	const DIAMOND_MARKER = 'DiamondMarker';
	const RIVER_SHUFFLER = 'LookRiverCard'; //Shelvin's Shuffler
	const RIVER_SHUFFLER_USE = 'SwapRiverCard';
	const POKER_PEEKER = 'PokerPeeker';
	const SOCIAL_SPOTTER = 'SocialSpotter';
	const TUCKER_TABLE_SLIDE_UNDER = 'TableTuckerSlideUnder';
	const TUCKER_TABLE_SLIDE_OUT = 'TableTuckerSlideOut'; // remove without using, why bother removing?
	const TUCKER_TABLE_EXCHANGE = 'TableTuckerExchange'; // exchange with 1st or 2nd card 
	const SNAKE_OIL_MARKER = 'SnakeOilMarker';
	const ANTI_OIL_MARKER = 'AntiOilMarker'; //Old Doc McSneaky Snake Liver Crazy Maker
	const SNAKE_OIL_MARKER_COUNTERED = 'SnakeOilMarkerCountered';
	const KEEP_FACE_CARDS = 'FaceMelter';
	const KEEP_FACE_CARDS_APPLIED = 'FaceMelterApplied';
}

/* code passed to front-end */

final class CheatDtoType {

	const CheatedHands = 'CheatedHands'; // CheaterCardDto list
	const CheatedCards = 'CheatedCards'; // CheaterCardDto list
	const CheatedHidden = 'CheatedHidden'; // list of card names
	const CheatedNext = 'CheatedNext'; // single card name
	const ItemLog = "ItemLog";
	const ItemUnlock = "ItemUnlock";
	const ItemEnd = "ItemEnd";
	const ItemLock = "ItemLock";

	//const CheatInfo = "CheatInfo";
}

/**
 * Actions that the user takes from the front-end
 */
final class ActionType {

	// table actions

	const JoinTable = "JoinTable";
	const LeaveTable = "LeaveTable";
	const EndPractice = "EndPractice";
	const TakeSeat = "TakeSeat";
	// poker actions
	const StartGame = "StartGame";
	const StartPracticeGame = "StartPracticeGame";
	const MakePokerMove = "MakePokerMove";
	const EndRound = "EndRound";
	const Cheat = "Cheat";
	const Chat = "Chat";

}

define("PLAYER_MOVE", "PlayerMove");
define("GAME_STARTED", "GameStarted");

/**
 * Events (sync and asynch) from server to client
 * user joined is not an event because given as REST response
 */
final class EventType {

	const GameStarted = 'GameStarted';
	const ChangeNextTurn = 'ChangeNextTurn'; // PlayerStatus
	const SeatOffer = 'SeatOffer'; // int
	const SeatTaken = 'SeatTaken';
	const UserLeft = 'UserLeft';
	//const CHEATED_HANDS = 'CheatedHands';
	//const CHEATED_CARDS = 'CheatedCards';
	const CHEATED = 'Cheated';
	const UserEjected = 'UserEjected';

	//const CHEATED_NEXT = 'CheatedNext';
}

?>