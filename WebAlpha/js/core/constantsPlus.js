/*------------------------------------------------------------------------------------------*/
/* constants - events */
var GAME_STARTED = 'GameStarted';
var PLAYER_MOVE = 'PlayerMove';
var CHANGE_NEXT_TURN = 'ChangeNextTurn';
var SEAT_TAKEN = 'SeatTaken';
var WAIT_LIST_CHANGE = 'WaitListChange';
var USER_LEFT = 'UserLeft';
var SEAT_OFFER = 'SeatOffer';
var CHEATED_HANDS = "CheatedHands";
var CHEATED_CARDS = "CheatedCards";
var CHEATED_HIDDEN = "CheatedHidden";
var CHEATED_NEXT = "CheatedNext";
var ITEM_LOCK = "ItemLock";
var ITEM_UNLOCK = "ItemUnlock";
var ITEM_LOG = "ItemLog";
var ITEM_END = "ItemEnd";
/* game status */
var GAME_ACTIVE = 'Active';
var GAME_INACTIVE = 'Inactive';
var GAME_ENDED = 'Ended';
/* constants - actions */
var START_GAME = 'StartGame';
var MAKE_POKER_MOVE = 'MakePokerMove';
var JOIN_TABLE = 'JoinTable';
var LEAVE_TABLE = 'LeaveTable';
var TAKE_SEAT = 'TakeSeat';
var CHEAT = 'Cheat';
var CHAT = 'Chat';
/* player status */
var SKIPPED = 'Skipped';
/* cheat items */
var ACE_PUSHER = 'AcePusher'; // Sly McGuffin's Ace Pusher
    // Messages: 1) User hand with ace 2) old card in sleeve 
var HEART_MARKER = 'HeartMarker';
    // Messages: 1) All player hands with heart 2) Lock start message
var LOAD_CARD_ON_SLEEVE = 'LoadCardOnSleeve'; // Old Man Chalmers Reliable Card Pusher
    // No messages, sync REST call, confirmation sent
var USE_CARD_ON_SLEEVE = 'UseCardOnSleeve';
    // Messages: 1) Updated user hand 2) updated sleeve
var CLUB_MARKER = 'ClubMarker';
    // Messages: 1) All player hands with clubs 2) Lock start message
var DIAMOND_MARKER = 'DiamondMarker';
    // Messages: 1) All player hands with diamonds 2) Lock start message
var RIVER_SHUFFLER = 'LookRiverCard'; //Shelvin's Shuffler
    // Messages: 1) River card value (NEXT) 2) Lock start message 
var RIVER_SHUFFLER_USE = 'SwapRiverCard';
    // Messages: None
var POKER_PEEKER = 'PokerPeeker';
    // Messages: 1) Other player cards 2) Lock start message
var SOCIAL_SPOTTER = 'SocialSpotter';
    // Messages: 1) All player hands 2-4) up to 3 additional messages with value of community cards as dealt 

/*------------------------------------------------------------------------------------------*/
// helper functions

function O(obj) {
    if (typeof obj === 'object')
        return obj;
    else
        return document.getElementById(obj);
}
function S(obj) {
    return O(obj).style;
}
function C(name) {
    return document.getElementsByClassName(name);
}
/**
 * Removes the 'px' on a CSS dimensional attribute
 * @param {type} pixel
 * @returns {unresolved}
 */
function getSize(pixel) {
    return Number(pixel.substr(0, pixel.length - 2));
}

function dimItem(id) {
    //O(id).addClass("fade");
    $('#' + id).addClass("fade");
}

function unDimItem(id) {
    //O(id).removeClass("fade");
    $('#' + id).removeClass("fade");
}

/*******************************************************************************************/
//FIXME: may want to keep an array of reverse key value pair
function getPlayerPositionTag(playerId) {
    switch (String(playerId)) {
        case O('player0Id').innerHTML:
            return 'player0';
            break;
        case O('player1Id').innerHTML:
            return 'player1';
            break;
        case O('player2Id').innerHTML:
            return 'player2';
            break;
        default:
            return 'player3';
    }
}

