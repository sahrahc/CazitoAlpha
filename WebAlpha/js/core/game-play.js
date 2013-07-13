/** Main game play javascript
 * 
 */
// FIXME: need to split up cheating and poker playing


/*-------------------------------------------------------------------------------------------*/

function joinCasinoTable() {
    var tableId = $.cookies.get("tableId");
    var tableCode = $.cookies.get("tableCode");
    var obj = {
        requestingPlayerId: $.cookies.get("userPlayerId"),
        tableName: tableId,
        tableCode: tableCode
    };

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("JoinTable",
            obj,
            joinCasinoTableCallback);
}
;

/* AJAX add user to table, which may have a live game.
 * Complicated business rules, but minimal validation on client side.
 * 1) if game not active, don't display any cards,
 * 2) display player status in any case
 */
/**
 * The request to add user to the user was successful, and the user has a seat
 * or is in the waiting list. In either case, the table current status 
 * needs to be displayed for the user.
 * This does not need to be a REST call... 
 * @param {type} gameStatusDto
 * @returns {undefined}
 */
function joinCasinoTableCallback(gameStatusDto) {
    if (gameStatusDto.userSeatNumber !== null) {
        O('header-central').children('p')[0].innerHTML = 'Casino Table ' + gameStatusDto.casinoTableId;
    }
    else {
        O('header-central').children('p')[0].innerHTML = 'Casino Table ' + gameStatusDto.casinoTableId + ' (On Waiting List)';
    }

    $.cookies.set('userPlayerId', gameStatusDto.userPlayerId);

    O('gameSessionId').innerHTML = gameStatusDto.gameSessionId;
    // is this needed? Same as casinoTableId null or set casinoTableId is null
    O('isPractice').innerHTML = 0;
    O('gameStatus').innerHTML = gameStatusDto.gameStatus;
    // do you need this since using cookies?
    O('userPlayerId').innerHTML = gameStatusDto.userPlayerId;
    O('casinoTableId').innerHTML = gameStatusDto.casinoTableId;

    // initialize the seats. There's always at least one seat, which is occupied by user.
    if (gameStatusDto.waitingListSize !== null) {
        updateWaitingListMessage(gameStatusDto.waitingListSize);
    }
    if (gameStatusDto.gameStatus === GAME_INACTIVE) {
        resetBoardItemsDisplay();
        if (gameStatusDto.playerStatusDtos.length === 1) {
            // if user is the first on the table, a game cannot start until another user joins
            displayCenterMessage("Waiting for another user to join...");
        }
        // otherwise allow game start button
        if (gameStatusDto.playerStatusDtos.length > 1 &&
                gameStatusDto.userSeatNumber !== null) {
            O('startGameButton').disabled = false;
            unDimItem('startGameButton');
            displayCenterMessage('Please press the start button to start play');
        }
    }
    else {
        showGameStatus(gameStatusDto);
        if (gameStatusDto.gameStatus === GAME_ENDED) {
            // hide markers when displaying the result
            hideCardMarkers();
            showGameResult(gameStatusDto);
        }
    }

    startQueueing();
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: start a practice session.
 */
/**
 * 
 * @param gameStatusDto gameInstanceStatusDto
 * @returns none
 */
function startPracticeSessionCallback(gameStatusDto) {
    O('header-central').children('p')[0].innerHTML = 'Practice Game';

    $.cookies.set('userPlayerId', gameStatusDto.userPlayerId);
    O('userPlayerId').innerHTML = gameStatusDto.userPlayerId;

    O('gameSessionId').innerHTML = gameStatusDto.gameSessionId;
    O('isPractice').innerHTML = 1;

    showGameStatus(gameStatusDto);

    startQueueing();
}

function startPracticeSession() {
    // TODO: only practice sessions support by FE.
    var obj = {
        requestingPlayerId: $.cookies.get("userPlayerId")
    };

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("startPracticeSession",
            obj,
            startPracticeSessionCallback);
}

/**
 * Starts a game for a live instance.
 * @returns {undefined}
 */
function startGame() {
    sendRequest('startGame');
}

/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 **/
/*-------------------------------------------------------------------------------------------*/
function sendPlayerAction(actionType, value) {
    var eventData = {
        pokerActionType: actionType,
        actionTime: new Date(),
        actionValue: value
    };
    sendRequest(MAKE_POKER_MOVE, eventData);
}

function clickRaise() {
    var raiseAmount = O('userRaiseAmount').innerHTML;
    sendPlayerAction("Raised", raiseAmount);
}
function clickCall() {
    var callAmount = O('userCallAmount').innerHTML;
    sendPlayerAction("Called", callAmount);
}
function clickCheck() {
    sendPlayerAction("Checked", null);
}
function clickFold() {
    sendPlayerAction("Folded", null);
}

/**
 * Show ongoing game after user joins a table
 * @param {GameStatusDto} gameStatusDto
 * @returns {undefined}
 */
function showGameStatus(gameStatusDto) {

    var resetPlayersCardsDisplay = function(playerStatusDtos) {
        for (var i = 0, l = playerStatusDtos.length; i < l; i++) {
            var seatNumber = playerStatusDtos[i].seatNumber;
            S('player' + seatNumber + 'Card1Image').display = 'block';
            S('player' + seatNumber + 'Card2Image').display = 'block';
            O('player' + seatNumber + 'Card1Image').src = "../../../images/PokerCard_back_small.png";
            O('player' + seatNumber + 'Card2Image').src = "../../../images/PokerCard_back_small.png";
        }
        hideCardMarkers();
    };

    var displayUserHands = function(userPlayerHandDto) {
        var playerElement = getPlayerPositionTag($.cookies.get('userPlayerId'));
        O(playerElement + 'Card1').innerHTML = userPlayerHandDto.pokerCard1Code;
        O(playerElement + 'Card2').innerHTML = userPlayerHandDto.pokerCard2Code;
        showPlayerCard(playerElement, 1, userPlayerHandDto.pokerCard1Code);
        showPlayerCard(playerElement, 2, userPlayerHandDto.pokerCard2Code);

        // enable cheating items
        disableInstanceItems(true);
    };

    var setupPlayerStatuses = function(playerStatusDtos) {
        var playerCount = playerStatusDtos.length;
        for (var i = 0; i < playerCount; i++) {
            if (playerStatusDtos[i].seatNumber !== null) {
                updatePlayerStatus(playerStatusDtos[i]);
            }
        }
    };

    var displayDealer = function(dealerPlayerId) {
        var dealerPlayerTag = getPlayerPositionTag(dealerPlayerId);
        //O('dealerPlayerId').innerHTML = resp.dealerPlayerId;
        O('currentDealerId').innerHTML = dealerPlayerId;
        // show dealer button

        S(dealerPlayerTag + 'DealerButton').display = 'block';
    };

    /** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
     * Size playe cards to be bigger, which requires positioning changes
     * - all cards: second card moved (right and left) by card width difference
     * - bottom cards: move both cards up by card height difference
     */
    var resizeUserElements = function() {
        var uTag = getPlayerPositionTag($.cookie.get('userPlayerId'));

        // add border on status
        O(uTag + 'Info').setAttribute("class", "playerInfo playerInfoUser");
        O(uTag + 'Card1Image').setAttribute("class", "userCard " + uTag + "UserCard1");
        O(uTag + 'Card2Image').setAttribute("class", "userCard " + uTag + "UserCard2");
    };

    /*
     * Position the call, check, raise, etc. buttons next to the user status box.
     */
    var positionUserButtons = function(seatNumber) {
        var userTag = 'player' + seatNumber;

        O('userRaiseButton').setAttribute("class", "userButton " + userTag + "Raise");
        O('userCallButton').setAttribute("class", "userButton " + userTag + "Call");
        O('userCheckButton').setAttribute("class", "userButton " + userTag + "Check");
        O('userFoldButton').setAttribute("class", "userButton " + userTag + "Fold");

        S('userRaiseButton').display = 'block';
        S('userCallButton').display = 'block';
        S('userCheckButton').display = 'block';
        S('userFoldButton').display = 'block';
    };


    O('gameInstanceId').innerHTML = gameStatusDto.gameInstanceId;
    O('gameStatus').innerHTML = gameStatusDto.gameStatus;

    resetBoardItemsDisplay();
    // update everyone's statuses and cards
    resetPlayersCardsDisplay(gameStatusDto.playerStatusDtos);
    setupPlayerStatuses(gameStatusDto.playerStatusDtos);

    displayDealer(gameStatusDto.dealerPlayerId);

    // a user who has a hand and calls showGameInProgress is one who closed the browser
    // and is re-entering the table.
    if (gameStatusDto.userPlayerHandDto !== null) {
        displayUserHands(gameStatusDto.userPlayerHandDto);
    }

    if (gameStatusDto.communityCards !== null) {
        showCommunityCard(gameStatusDto.communityCards);
    }

    // check is disabled when a game first starts
    if (gameStatusDto.nextMoveDto !== null) {
        displayTurnChange(gameStatusDto.nextMoveDto, false);
    }

    // the user elements will need to be displayed 
    // on joining table or starting practice session 
    // or closing/reopening browser
    // or on game start - the user is seated at a seat that didn't have a user before.
    if (S('userCallButton').display === 'none' && gameStatusDto.userSeatNumber !== null) {
        positionUserButtons(gameStatusDto.userSeatNumber);
        resizeUserElements();
    }
}

/*-------------------------------------------------------------------------------------------
 *
 */
/**
 * Update game status after a user's turn (skipped or made move).
 * Not to be used when redrawing the entire board.
 * @param {GameStatusDto} gameStatusDto
 * @returns {undefined}
 */
function showGameAfterTurn(gameStatusDto) {
    updatePlayerStatus(gameStatusDto.turnPlayerStatusDto);

    if (gameStatusDto.gameStatus === GAME_ENDED) {
        showGameResult(gameStatusDto);
    }
    var skipped = gameStatusDto.turnPlayerStatusDto.status === SKIPPED ? true : false;

    if (gameStatusDto.cardsToSend !== null) {
        showCommunityCard(gameStatusDto.cardsToSend);
    }
    if (gameStatusDto.nextMoveDto !== null) {
        displayTurnChange(gameStatusDto.nextMoveDto, skipped);
    }
}

/*-------------------------------------------------------------------------------------------
 * Only updates the result elements. Need to be used with showGameStatus
 * if drawing the entire board.
 */
function showGameResult(gameStatusDto) {
    /**
     * Defines the given player as the winner
     * @param {type} playerTag
     * @returns {undefined}
     */
    var applyWinnerDisplay = function(playerTag) {
        var playerStyle = O(playerTag + 'Info');
        playerStyle.setAttribute("class", "playerInfo playerInfoWinner");

        O(playerTag + 'Status').setAttribute("class", "playerStatusWinner");
    };

    // show everyone's hands
    for (var i = 0, l = gameStatusDto.playerHandDtos.length; i < l; i++) {
        var playerElement = getPlayerPositionTag(gameStatusDto.playerHandDtos[i].playerId);
        showPlayerCard(playerElement, 1, gameStatusDto.playerHandDtos[i].pokerCard1Code);
        showPlayerCard(playerElement, 2, gameStatusDto.playerHandDtos[i].pokerCard2Code);
        if (gameStatusDto.playerHandDtos[i].playerId === gameStatusDto.winningPlayerId) {
            O(playerElement + 'Status').innerHTML = 'Won - ' + gameStatusDto.playerHandDtos[i].pokerHandType;
        }
        else {
            O(playerElement + 'Status').innerHTML = 'Lost - ' + gameStatusDto.playerHandDtos[i].pokerHandType;
        }
    }
    // update players, although only status and stakes should have changed 
    for (var j = 0, m = gameStatusDto.playerStatusDtos.length; j < m; j++) {
        playerElement = getPlayerPositionTag(gameStatusDto.playerStatusDtos[j].playerId);
        O(playerElement + 'Stake').innerHTML = gameStatusDto.playerStatusDtos[j].stake;
    }
    // set winner
    var winnerElement = getPlayerPositionTag(gameStatusDto.winningPlayerId);
    applyWinnerDisplay(winnerElement);
    O('startGameButton').disabled = false;
    unDimItem('startGameButton');

    disableInstanceItems(false);
}

