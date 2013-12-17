/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * This file contains data for display user and player info
 /********************************************************************************************/
/**
 * @param {playerStatusDto} playerStatusDto
 * @returns {undefined}
 */
function updatePlayerIdentity(playerStatusDto) {
    O('seatNumber').innerHTML = playerStatusDto.seatNumber;
    var playerTag = 'player' + playerStatusDto.seatNumber;
    O(playerTag + 'Id').innerHTML = playerStatusDto.playerId;
    O(playerTag + 'Name').innerHTML = playerStatusDto.playerName;
    O(playerTag + 'Image').innerHTML = playerStatusDto.playerImageUrl;
    /*    O(playerTag + 'Status').innerHTML = playerStatusDto.status;
     // updates status
     O(playerTag + 'Stake').innerHTML = playerStatusDto.currentStake; */
}
/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
function showPlayerCard(playerTag, cardPosition, cardValue) {
    var cardElementId = playerTag + 'Card' + cardPosition + 'Image';

    O(cardElementId).src = "../../../images/" + "PokerCard_" + cardValue + "_small.png";
}

/*
 * Update player data - this is not to be used for players who left the table
 */
function updatePlayerStatus(playerStatusDto) {

    var foldPlayerCards = function(playerTag) {
	S(playerTag + 'Card1Image').display = 'none';
	S(playerTag + 'Card2Image').display = 'none';

    };

    var playerTag = getPlayerPositionTag(playerStatusDto.playerId);
    // updates status
    O(playerTag + 'Stake').innerHTML = playerStatusDto.currentStake;
    O(playerTag + 'Status').innerHTML = playerStatusDto.status;
    switch (playerStatusDto.status) {
	case "Folded":
	case "Left":
	    foldPlayerCards(playerTag);
	    break;
	case "Called":
	case "Raised":
	    O(playerTag + 'Status').innerHTML = playerStatusDto.status + ' ' + playerStatusDto.lastPlayAmount;
	    break;
    }
}

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

	$('#' + playerTag + 'Info').removeClass('playerInfoNext');
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
    if (previousPlayerId !== "") {
	resetLastPlayerDisplay(getPlayerPositionTag(previousPlayerId), skipped);
	if ($.cookies.get("userPlayerId") === +previousPlayerId) {
	    O('userRaiseButton').disabled = true;
	    O('userCheckButton').disabled = true;
	    O('userCallButton').disabled = true;
	    O('userFoldButton').disabled = true;
	}
    }
    var nextPlayerTag = getPlayerPositionTag(nextMoveDto.playerId);
    applyNextPlayerDisplay(nextPlayerTag);
    O('nextPlayerId').innerHTML = nextMoveDto.playerId;

    if ($.cookies.get("userPlayerId") === +nextMoveDto.playerId) {
	O('userCallAmount').innerHTML = nextMoveDto.callAmount;
	O('userRaiseAmount').innerHTML = nextMoveDto.raiseAmount;
	O('userCallButton').innerHTML = 'Call ' + nextMoveDto.callAmount;
	O('userRaiseButton').innerHTML = 'Raise ' + nextMoveDto.raiseAmount;
	enableUserButtons(nextMoveDto.isCheckEnabled);
    }
}

/*-------------------------------------------------------------------------------------------
 * Only updates the result elements. Need to be used with showGameStatus
 * if drawing the entire board or with showGameAfterTurn
 */
function displayGameResult(gameStatusDto) {
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
    var showAllHands = function(playerHandsDto, winningPlayerId) {
	// show everyone's hands
	for (var i = 0, l = playerHandsDto.length; i < l; i++) {
	    var playerElement = getPlayerPositionTag(playerHandsDto[i].playerId);
	    if (playerHandsDto[i].playerId !== $.cookies.get("userPlayerId")) {
		showPlayerCard(playerElement, 1, playerHandsDto[i].pokerCard1Code);
		showPlayerCard(playerElement, 2, playerHandsDto[i].pokerCard2Code);
	    }
	    if (playerHandsDto[i].playerId === winningPlayerId) {
		O(playerElement + 'Status').innerHTML = 'Won - ' + playerHandsDto[i].pokerHandType;
	    }
	    else {
		O(playerElement + 'Status').innerHTML = 'Lost - ' + playerHandsDto[i].pokerHandType;
	    }
	}
    };
    var updateStatusNoHands = function(playerStatusDtos, winningPlayerId) {
	for (var j = 0, m = playerStatusDtos.length; j < m; j++) {
	    var playerElement = getPlayerPositionTag(playerStatusDtos[j].playerId);
	    if (+playerStatusDtos[j].playerId === winningPlayerId) {
		O(playerElement + 'Status').innerHTML = 'Won';
	    }
	    else if (playerStatusDtos[j].status === "Left" ||
		    playerStatusDtos[j].status === "Folded") {
		O(playerElement + 'Status').innerHTML = 'Lost - ' + playerStatusDtos[j].status;
	    }
	    else {
		O(playerElement + 'Status').innerHTML = 'Lost';
	    }
	}
    };
// update players, although only status and stakes should have changed 
    var updateFinalStakes = function(playerStatusDtos) {
	for (var j = 0, m = playerStatusDtos.length; j < m; j++) {
	    var playerElement = getPlayerPositionTag(playerStatusDtos[j].playerId);
	    O(playerElement + 'Stake').innerHTML = playerStatusDtos[j].currentStake;
	}
    };
    // no player hands sent if only one user remaining
    if (gameStatusDto.playerHandsDto !== null) {
	showAllHands(gameStatusDto.playerHandsDto, gameStatusDto.winningPlayerId);
    }
    else {
	updateStatusNoHands(gameStatusDto.playerStatusDtos, gameStatusDto.winningPlayerId);
    }
    updateFinalStakes(gameStatusDto.playerStatusDtos);
// set winner
    applyWinnerDisplay(getPlayerPositionTag(gameStatusDto.winningPlayerId));
    var countActive = 0;
    for (var i = 0; i < gameStatusDto.playerStatusDtos.length; i++) {
	if (gameStatusDto.playerStatusDtos[i].status !== "Left") {
	    countActive++;
	}
    }
    if (countActive > 1) {
	O('startGameButton').disabled = false;
	unDimItem('startGameButton');
    }
    else {
	O('startGameButton').disabled = true;
	displayCenterMessage("Waiting for another user to join...");
    }

    // hide markers when displaying the result
    if ($.cookies.get("vanilla-play") === 0) {
	hideCardMarkers();
    }
}
/**
 * Show complete game status; used after user joins a table or when game first starts
 * @param {GameStatusDto} gameStatusDto
 * @returns {undefined}
 */
function showGameStatus(gameStatusDto) {
    /* Initialize board table items to values appropriate before a game starts.
     * 1) community cards set to hidden, shown number to zero.
     * 2) all player cards set to hidden
     * 3) hide dealer button
     * 4) user status boxes to normal color
     * 5) center message
     * 6) player's mesages to none and to normal size and color
     */
    var resetBoardItemsDisplay = function() {
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
	S('playerDealerButton').display = 'none';

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
	O('nextCommunityCardPosition').innerHTML = 0;

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

	/* hide markers in case they were shown */
	if ($.cookies.get("vanilla-play") === 0) {
	    hideCardMarkers();
	}
    }
    var displayUserHands = function(userPlayerHandDto) {
	var playerElement = getPlayerPositionTag($.cookies.get("userPlayerId"));
	O(playerElement + 'Card1').innerHTML = userPlayerHandDto.pokerCard1Code;
	O(playerElement + 'Card2').innerHTML = userPlayerHandDto.pokerCard2Code;
	showPlayerCard(playerElement, 1, userPlayerHandDto.pokerCard1Code);
	showPlayerCard(playerElement, 2, userPlayerHandDto.pokerCard2Code);
	if ($.cookies.get("vanilla-play") === 0) {
	    for (var i = 1; i <= 2; i++) {
		$('#' + playerElement + 'Card' + i + 'Image').data('playerCardNumber', i).droppable({
		    accept: '.cheatingCard, #AcePusher img', //, #grooveCards",
		    hoverClass: 'hovered',
		    drop: cheatChangeHand
		});
	    }
	}
    };

    var displayAllPlayers = function(playerStatusDtos, gameStatus) {
	for (var i = 0, l = playerStatusDtos.length; i < l; i++) {
	    if (playerStatusDtos[i].seatNumber !== null) {
		updatePlayerStatus(playerStatusDtos[i]);
	    }
	    var playerElement = getPlayerPositionTag(playerStatusDtos[i].playerId);
	    if (gameStatus !== GAME_INACTIVE) {
		for (var c = 1; c <= 2; c++) {
		    S(playerElement + 'Card' + c + 'Image').display = 'block';
		    O(playerElement + 'Card' + c + 'Image').src = "../../../images/PokerCard_back_small.png";
		    if ($.cookies.get("vanilla-play") === 0 &&
			    playerStatusDtos[i].playerId !== +$.cookies.get("userPlayerId")) {
			$('#' + playerElement + 'Card' + c + 'Image')
				// clunky combining two data elements on a single data function
				.data('playerCardNumberAndId', String(c) + playerStatusDtos[i].playerId).droppable({
			    accept: '#PokerPeeker img',
			    hoverClass: 'hovered',
			    drop: cheatRevealCard
			});
		    }
		}
	    }
	}
    };
    var displayDealer = function(dealerPlayerId) {
	var dealerPlayerTag = getPlayerPositionTag(dealerPlayerId);
	O('currentDealerId').innerHTML = dealerPlayerId;
	// show dealer buttondealerPlayerTag
	O('playerDealerButton').setAttribute("class", "dealerButton " + dealerPlayerTag + "DealerPosition");
	S('playerDealerButton').display = 'block';
    };
    /*
     * Position the call, check, raise, etc. buttons next to the user status box.
     */
    function positionUserButtons(seatNumber) {
	var userTag = 'player' + seatNumber;
	S('userRaiseButton').display = 'inline-block';
	S('userCallButton').display = 'inline-block';
	S('userCheckButton').display = 'inline-block';
	S('userFoldButton').display = 'inline-block';

	O('userRaiseButton').setAttribute("class", "userButton " + userTag + "Raise");
	O('userCallButton').setAttribute("class", "userButton " + userTag + "Call");
	O('userCheckButton').setAttribute("class", "userButton " + userTag + "Check");
	O('userFoldButton').setAttribute("class", "userButton " + userTag + "Fold");
	// add border on status
	O(userTag + 'Info').setAttribute("class", "playerInfo playerInfoUser");
	// resize and position
	O(userTag + 'Card1Image').setAttribute("class", "userCard " + userTag + "UserCard1");
	O(userTag + 'Card2Image').setAttribute("class", "userCard " + userTag + "UserCard2");
	O(userTag + 'Card1Slot').setAttribute("class", "userCardSlot " + userTag + "UserCard1");
	O(userTag + 'Card2Slot').setAttribute("class", "userCardSlot " + userTag + "UserCard2");
    }

    /** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^*/
    O('gameInstanceId').innerHTML = gameStatusDto.gameInstanceId;
    O('gameStatus').innerHTML = gameStatusDto.gameStatus;
    resetBoardItemsDisplay();
    // update everyone's statuses and cards
    if (gameStatusDto.playerStatusDtos.length > 0) {
	displayAllPlayers(gameStatusDto.playerStatusDtos, gameStatusDto.gameStatus);
    }
    if (gameStatusDto.dealerPlayerId !== null) {
	displayDealer(gameStatusDto.dealerPlayerId);
    }
    // a user who has a hand and calls showGameInProgress is one who closed the browser
    // and is re-entering the table.
    if (gameStatusDto.userPlayerHandDto !== null) {
	displayUserHands(gameStatusDto.userPlayerHandDto);
    }

    if (gameStatusDto.communityCards !== null) {
	showCommunityCards(gameStatusDto.communityCards);
	//animateCard();
    }

// the user elements will need to be displayed 
// not needed on starting practice session because game instance automatically started
// or closing/reopening browser (same as joining a table previously joined)
// or on game start - the user is seated at a seat that didn't have a user before.
    if (S('userCallButton').display !== 'none' && gameStatusDto.userSeatNumber !== null) {
	positionUserButtons(gameStatusDto.userSeatNumber);
    }
// check is disabled when a game first starts
    if (gameStatusDto.nextMoveDto !== null) {
	displayTurnChange(gameStatusDto.nextMoveDto, false);
    }
}

/**
 * Update game status after a user's turn (skipped or made move).
 * Not to be used when redrawing the entire board.
 * @param {GameStatusDto} gameStatusDto
 * @returns {undefined}
 */
function showGameAfterTurn(gameStatusDto) {
    if (gameStatusDto.turnPlayerStatusDto !== null) {
	updatePlayerStatus(gameStatusDto.turnPlayerStatusDto);
    }
    if (gameStatusDto.gameStatus === GAME_ENDED) {
	displayGameResult(gameStatusDto);
    }
    if (gameStatusDto.newCommunityCards !== null) {
	showCommunityCards(gameStatusDto.newCommunityCards);
	//animateCard();
    }
    if (gameStatusDto.nextMoveDto !== null) {
	var skipped = false;
	if (gameStatusDto.turnPlayerStatusDto !== null) {
	    gameStatusDto.turnPlayerStatusDto.status === SKIPPED ? true : false;
	}
	displayTurnChange(gameStatusDto.nextMoveDto, skipped);
    }
}

