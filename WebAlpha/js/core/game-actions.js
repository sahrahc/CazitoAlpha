/** Main game play javascript
 * 
 */
// FIXME: need to split up cheating and poker playing


/*-------------------------------------------------------------------------------------------*/

/**
 * Only called upon loading of SafePlay or CheatingPlay
 * @returns {undefined}
 */
function joinCasinoTable() {
    var tableId = $.cookies.get("tableId");
    var tableCode = $.cookies.get("tableCode");
    var obj = {
	requestingPlayerId: $.cookies.get("userPlayerId"),
	tableId: tableId,
	tableCode: tableCode
    };

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("joinTable",
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
    $.cookies.set("joinedTable", 1);
    if (gameStatusDto.userSeatNumber !== null) {
	O('main').getElementsByTagName("h1")[0].innerHTML = 'Casino Table ' + $.cookies.get('tableName');
// the user elements will need to be displayed 
// not needed on starting practice session because game instance automatically started
// or closing/reopening browser (same as joining a table previously joined)
// or on game start - the user is seated at a seat that didn't have a user before.
    }
    else {
	O('main').getElementsByTagName("h1")[0].innerHTML = 'Casino Table ' + $.cookies.get('tableName') + ' (On Waiting List)';
    }

    if (gameStatusDto.userPlayerId !== $.cookies.get("userPlayerId")) {
	alert('You are trying to hack me!');
    }
    //$.cookies.set("userPlayerId", gameStatusDto.userPlayerId);

    O('gameSessionId').innerHTML = gameStatusDto.gameSessionId;
    // is this needed? Same as casinoTableId null or set casinoTableId is null
    O('isPractice').innerHTML = "0";
    O('gameStatus').innerHTML = gameStatusDto.gameStatus;
    // do you need this since using cookies?
    O('userPlayerId').innerHTML = gameStatusDto.userPlayerId;
    O('casinoTableId').innerHTML = gameStatusDto.casinoTableId;

    // initialize the seats. There's always at least one seat, which is occupied by user.
    if (gameStatusDto.waitingListSize !== null && gameStatusDto.waitingListSize > 0) {
	updateWaitlistCount(gameStatusDto.waitingListSize);
    }
    for (var i = 0; i < 4; i++) {
	O('player' + i + 'Name').innerHTML = "Empty Seat";
	O('player' + i + 'Stake').innerHTML = "";
	O('player' + i + 'Status').innerHTML = "";
	O('player' + i + 'Image').src = "../../../images/Avatar_user0.jpeg";
    }
    var playerTag = 'player' + gameStatusDto.userSeatNumber;
    O(playerTag + 'Id').innerHTML = gameStatusDto.userPlayerId;
    
    for (var i = 0; i < gameStatusDto.playerStatusDtos.length; i++) {
	updatePlayerIdentity(gameStatusDto.playerStatusDtos[i]);
    }
    showGameStatus(gameStatusDto);
    if (gameStatusDto.gameStatus === GAME_INACTIVE) {
	/*	resetBoardItemsDisplay();
	 
	 for (var i = 0; i < gameStatusDto.playerStatusDtos.length; i++) {
	 updateUserSeatTaken(gameStatusDto.playerStatusDtos[i]);
	 } */
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
    else if (gameStatusDto.gameStatus === GAME_ENDED) {
	displayGameResult(gameStatusDto);
    }

    refreshHeaderPlay();
    startQueueing();
    animateCard();
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
    O('main').getElementsByTagName("h1")[0].innerHTML = 'Practice Game';

    //$.cookies.set("userPlayerId", gameStatusDto.userPlayerId);
    O('userPlayerId').innerHTML = gameStatusDto.userPlayerId;

    O('gameSessionId').innerHTML = gameStatusDto.gameSessionId;
    O('isPractice').innerHTML = "1";
    refreshHeaderPlay();

    for (var i = 0; i < gameStatusDto.playerStatusDtos.length; i++) {
	updatePlayerIdentity(gameStatusDto.playerStatusDtos[i]);
    }
    showGameStatus(gameStatusDto);

    startQueueing();
    animateCard();
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
    if (O('isPractice').innerHTML === "1") {
	sendRequest('StartPracticeGame');
    }
    else {
	sendRequest('StartGame');
    }
}

function endPractice() {
    if (O('isPractice').innerHTML === "1") {
	sendRequest('EndPractice');
    }
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
/*
 jQuery(document).ready(function() {
 alert('What is wrong with game-actions.js'); 
 
 O('userRaiseButton').onclick =
 
 
 };*/

function clickRaiseButton() {
    var raiseAmount = O('userRaiseAmount').innerHTML;
    sendPlayerAction("Raised", raiseAmount);
}
function clickCallButton() {
    var callAmount = O('userCallAmount').innerHTML;
    sendPlayerAction("Called", callAmount);
}
function clickCheckButton() {
    sendPlayerAction("Checked", null);
}
function clickFoldButton() {
    sendPlayerAction("Folded", null);
}
/*-------------------------------------------------------------------------------------------
 *
 */

