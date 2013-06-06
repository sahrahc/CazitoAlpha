<?php

/*
 * game card codes to numbers
 */

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

final class EventType {
    /* const GAME_END = 'GameEnd'; // GameResult
      const NEW_CARDS = 'NewCards'; // array of 1 or 3 community cards. */

    const PLAYER_MOVE = 'PlayerMove'; // PlayerStatus
    //const FIRST_MOVE = 'FirstMove'; // PlayerStatus
    const TIME_OUT = 'TimeOut';
    const SEAT_OFFER = 'SeatOffer'; // int
    const SEAT_TAKEN = 'SeatTaken';
    const USER_JOINED = 'UserJoined';
    const USER_LEFT = 'UserLeft';
    const GAME_STARTED = 'GameStarted';
    //const CHEATED_HANDS = 'CheatedHands';
    //const CHEATED_CARDS = 'CheatedCards';
    const CHEATED_HIDDEN = 'CheatedHidden';
    //const CHEATED_NEXT = 'CheatedNext';

}

final class GameStatus {

    const IN_PROGRESS = 'InProgress';
    const NONE = 'None';
    const STARTED = 'Started';
    const ENDED = 'Ended';

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
    const RIVER_SHUFFLER = 'LookRiverCard';
    const RIVER_SHUFFLER_USE = 'SwapRiverCard';
    const ACTIVATE_TUCKER_TABLE = 'TableTucker';
    const SLIDE_UNDER_TABLE = 'SlideUnderTable';
    const SLIDE_OUT_OF_TABLE = 'SlideOutOfTable';
    const KEEP_FACE_CARDS = 'FaceMelter';
    const ANTI_OIL_MARKER = 'AntiOilMarker';

}

/* code passed to front-end */

final class CheatDtoType {

    const HANDS = 'CheatedHands';
    const CARDS = 'CheatedCards';
    const HIDDEN = 'CheatedHidden';
    const NEXT = 'CheatedNext';

}

final class CheatLogType {

    const ItemLog = "ItemLog";
    const ItemUnlock = "ItemUnlock";
    const ItemEnd = "ItemEnd";

}

final class ActionType {
    // table actions

    const JoinTable = "JoinTable";
    const LeaveTable = "LeaveTable";
    const TakeSeat = "TakeSeat";
    // poker actions
    const StartGame = "StartGame";
    const StartPracticeGame = "StartPracticeGame";
    const MakePokerMove = "MakePokerMove";
    const EndRound = "EndRound";
    const Cheat = "Cheat";
}

?>