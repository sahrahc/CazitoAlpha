<?php

/* custom data types (enums in Java)
 * 
 */

class Enum {

    // private static $enum = array();
    // returnDto::toDataValue("11"); 
    public function toDtoValue($name) {
        return array_search($name, self::$enum);
    }

    // evalValue::toEvaluatorType(1);
    public function toEvaluatorType($ordinal) {
        return self::$enum[$ordinal];
    }

}

/* poker card values - convert poker.h values to image files so that
 * the image can be correctly retrieved
 * FIXME: may want to rename files instead 
 * need to use pokerlib C++
 * 	int* deck = new int[52];
 *  init_deck(deck);
 *  find_card(int rank, int suit, int *deck);
 *  	the reverse is deck[index] 
 *  FIXME: call it once and keep it */

// the array values are what is returned by the service	
class PokerCardSuitType extends Enum {

    // the mapping from evaluator value to displayable value
    private static $enum = array(0x8000 => "clubs",
        0x4000 => "diamonds",
        0x2000 => "hearts",
        0x1000 => "spades");

}

// the array values are what is returned by the service	
class PokerCardRankType {

    private static $enum = array(
        0 => "2", // deuce
        1 => "3", // trey
        2 => "4", // four
        3 => "5", // five
        4 => "6", // six
        5 => "7", // seven
        6 => "8", // eight
        7 => "9", // nine
        8 => "10", // ten
        9 => "J", // jack
        10 => "Q", // queen
        11 => "K"); //king

}

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
    const LOST = 'Lost'; 
    const WON = 'Won';
    const WAITING = 'Waiting';
    const LEFT = 'Left';
    const SKIPPED = 'Skipped';
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

final class EventType {
    /*const GAME_END = 'GameEnd'; // GameResult
    const NEW_CARDS = 'NewCards'; // array of 1 or 3 community cards. */
    const PLAYER_MOVE = 'PlayerMove'; // PlayerStatus
    const FIRST_MOVE = 'FirstMove'; // PlayerStatus
    const TIME_OUT = 'TimeOut';
    const SEAT_OFFER = 'SeatOffer'; // int
    const SEAT_TAKEN = 'SeatTaken';
	const USER_JOINED = 'UserJoined';
    const USER_LEFT = 'UserLeft';
	const GAME_STARTED = 'GameStarted';
}

final class GameStatus {
    const ACTIVE = 'Active';
    const INACTIVE = 'Inactive';
}

final class ItemType {
    const HEART_MARKER = 'HeartMarker';
    const LOAD_CARD_ON_SLEEVE = 'LoadCardOnSleeve';
    const USE_CARD_ON_SLEEVE = 'UseCardOnSleeve';
    const CLUB_MARKER = 'ClubMarker';
    const DIAMOND_MARKER = 'DiamondMarker';
    const POKER_PEEKER = 'PokerPeeker';
    const SOCIAL_SPOTTER = 'SocialSpotter';
    const SNAKE_OIL_MARKER = 'SnakeOilMarker';
    const ACE_PUSHER = 'AcePusher';
    const LOOK_RIVER_CARD = 'LookRiverCard';
    const SWAP_RIVER_CARD = 'SwapRiverCard';
    const ACTIVATE_TUCKER_TABLE = 'ActivateTuckerTable';
    const SLIDE_UNDER_TABLE = 'SlideUnderTable';
    const SLIDE_OUT_OF_TABLE = 'SlideOutOfTable';
    const KEEP_FACE_CARDS = 'KeepFaceCards';
    const ANTI_OIL_MARKER = 'AntiOilMarker';
}
?>