/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * This file contains data for display user and player info
 * 
 * Player status (moved, skipped, next, winner, etc, require different highlighting and
 * colors. These are styling set by css classes.
 */


/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Initialize board table items to values appropriate before a game starts.
 * 1) community cards set to hidden, shown number to zero.
 * 2) all player cards set to hidden
 * 3) hide dealer button
 * 4) user status boxes to normal color
 * 5) center message
 * 6) player's mesages to none and to normal size and color
 *
 */
function resetBoardItemsDisplay() {

    O('centerMessageId').innerHTML = "";

    S('player0Card1Image').display = 'none';
    S('player0Card2Image').display = 'none';

    S('player1Card1Image').display = 'none';
    S('player1Card2Image').display = 'none';

    S('player2Card1Image').display = 'none';
    S('player2Card2Image').display = 'none';

    S('player3Card1Image').display = 'none';
    S('player3Card2Image').display = 'none';

    // hide dealer buttons
    // FIXME: single button, move with CSS class
    S('player0DealerButton').display = 'none';
    S('player1DealerButton').display = 'none';
    S('player2DealerButton').display = 'none';
    S('player3DealerButton').display = 'none';

    O('player0Info').setAttribute("class", "playerInfo playerInfoNormal");
    O('player1Info').setAttribute("class", "playerInfo playerInfoNormal");
    O('player2Info').setAttribute("class", "playerInfo playerInfoNormal");
    O('player3Info').setAttribute("class", "playerInfo playerInfoNormal");

    O('player0Status').setAttribute("class", "playerStatus");
    O('player1Status').setAttribute("class", "playerStatus");
    O('player2Status').setAttribute("class", "playerStatus");
    O('player3Status').setAttribute("class", "playerStatus");

    // user should be known before a game starts
    var userTag = getPlayerPositionTag(O('userPlayerId').innerHTML);
    if (userTag !== null) {
        O(userTag + 'Info').setAttribute("class", 'playerInfo playerInfoUser');
    }
    // display status
    //O('nextCommunityCardPosition').value = 0;

    // erase any previous message
    /*
     O('player0Message').value = "";
     O('player1Message').value = "";
     O('player2Message').value = "";
     O('player3Message').value = "";
     */
    O('userRaiseButton').disabled = true;
    O('userCheckButton').disabled = true;
    O('userCallButton').disabled = true;
    O('userFoldButton').disabled = true;

    // everything is hidden
    S('communityCard0').display = 'none';
    S('communityCard1').display = 'none';
    S('communityCard2').display = 'none';
    S('communityCard3').display = 'none';
    S('communityCard4').display = 'none';

    hideCardMarkers();
}

/********************************************************************************************/
/**
 * Change who is on seat
 * @param {playerStatusDto} playerStatusDto
 * @returns {undefined}
 */
function updatePlayerIdentity(playerStatusDto) {
    var playerPosition = playerStatusDto.seatNumber;
    O('player' + playerPosition + 'Id').innerHTML = playerStatusDto.playerId;
    O('player' + playerPosition + 'Name').innerHTML = playerStatusDto.playerName;
    O('player' + playerPosition + 'Image').innerHTML = playerStatusDto.playerImageUrl;

}
/*
 * Update player data - this is not to be used for players who left the table
 */
function updatePlayerStatus(playerStatusDto) {


    foldPlayerCards = function(playerId) {
        var playerTag = getPlayerPositionTag(playerId);
        S(playerTag + 'Card1Image').display = 'none';
        S(playerTag + 'Card2Image').display = 'none';

    };

    // updates status
    var playerPosition = playerStatusDto.seatNumber;
    O('player' + playerPosition + 'Stake').innerHTML = playerStatusDto.stake;
    O('player' + playerPosition + 'Status').innerHTML = playerStatusDto.status;
    if (playerStatusDto.status === "Folded" || playerStatusDto.status === "Left") {
        foldPlayerCards(playerStatusDto.playerId);
    }
    else {
        if (playerStatusDto.status === "Called" || playerStatusDto.status === "Raised") {
            O('player' + playerPosition + 'Status').innerHTML = playerStatusDto.status + ' ' + playerStatusDto.playAmount;
        }
    }
}

/* ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Set up a new game including user's cards, blind bets, dealer and player statuses
 * Two types: game is ongoing or not.
 * START POLLING
 * 1) start practice session
 * 2) user joins a table and there is no game in session (blinds, hands and dealer skipped)
 * 3) user starts new game
 * 4) polled message returns new game
 */

/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 /* Appky css display changes due to player actions */

/*-------------------------------------------------------------------------------------------
 * Highlight next player who needs to make a move. If that player is the user, make the
 * call, fold, etc. buttons available.
 */
function displayTurnChange(nextMoveDto, skipped) {
    /* 
     * enable user buttons on two ocasions
     * 1) user has next move
     * 2) user has first move
     */
    var enableUserButtons = function(isCheckEnabled) {
        O('userRaiseButton').disabled = false;
        O('userCheckButton').disabled = isCheckEnabled === 1 ? true : false;
        O('userCallButton').disabled = false;
        O('userFoldButton').disabled = false;
    };

    /**
     * Show a player as having skipped or normal (because just moved, no longer next)
     * @param {type} playerTag
     * @param {type} skipped
     * @returns {undefined}
     */
    var resetLastPlayerDisplay = function(playerTag, skipped) {
        var playerStyle = O(playerTag + 'Info');
        var userTag = getPlayerPositionTag($.cookies.get("userPlayerId"));

        O(playerTag + 'Info').removeClass('playerInfoNext');
        if (skipped) {
            playerStyle.setAttribute("class", "playerInfo playerInfoTimeOut");
        }
        else {
            if (playerTag === userTag) {
                playerStyle.setAttribute("class", "playerInfo playerInfoUser");
            }
            else {
                playerStyle.setAttribute("class", "playerInfo playerInfoNormal");
            }
        }
    };

    /**
     * Sets the given player as the next player
     * @param {type} playerTag
     * @returns {undefined}
     */
    var applyNextPlayerDisplay = function(playerTag) {
        var playerStyle = O(playerTag + 'Info');
        playerStyle.setAttribute("class", "playerInfo playerInfoNext");
    };

    // previous player id if any
    var previousPlayerId = O('nextPlayerId').innerHTML;
    if (previousPlayerId !== null) {
        resetLastPlayerDisplay(getPlayerPositionTag(previousPlayerId), skipped);
        if ($.cookies.get("userPlayerId") === previousPlayerId) {
            O('userRaiseButton').disabled = true;
            O('userCheckButton').disabled = true;
            O('userCallButton').disabled = true;
            O('userFoldButton').disabled = true;
        }
    }
    var nextPlayerTag = getPlayerPositionTag(nextMoveDto.playerId);
    applyNextPlayerDisplay(nextPlayerTag);
    O('nextPlayerId').innerHTML = nextMoveDto.playerId;

    if ($.cookies.get("userPlayerId") === nextMoveDto.playerId) {
        O('userCallAmount').innerHTML = nextMoveDto.callAmount;
        O('userRaiseAmount').innerHTML = nextMoveDto.raiseAmount;
        O('userCallButton').value = 'Call ' + nextMoveDto.callAmount;
        O('userRaiseButton').value = 'Raise ' + nextMoveDto.raiseAmount;
        enableUserButtons(nextMoveDto.isCheckEnabled);
    }
}

/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 /* Player Card display */
/**
 * Show a player's card, either the user or everyone's after the game ends
 * @param {string} playerTag is player + seat number (e.g., player0
 * @param {int} cardPosition either 1 or 2
 * @param {varchar(25)} cardValue is the card value (e.g., clubs_10)
 * @returns {none}
 */
function showPlayerCard(playerTag, cardPosition, cardValue) {
    var cardElementId = playerTag + 'Card' + cardPosition + 'Image';

    O(cardElementId).src = "../../../images/" + "PokerCard_" + cardValue + "_small.png";
}


/* cheating */

/********************************************************************************************/
