/** Main game play javascript
 * 
 */

constObjectOffset = 5;
constLargeOffset = 10;
// the following must match values in css files because can't read CSS file values
constStatusBoxWidth = 150;
constStatusUserWidth = 180;
constStatusBoxHeight = 78;

constStatusBoxNormalBGColor = '#D3D3D3';
constStatusBoxTimeOutBGColor = '#E59093';
constStatusBoxNextBGColor = '#8F8F8F';
constStatusBoxWinnerBGColor = '#1463FF';

constMessageHeight = 100;

constDealerButtonWidth = 20;
constDealerButtonHeight = 20;

constBetMessageHeight = 28;
constBetMessageWidth = 120;

userButtonWideWidth = 95;
userButtonMediumWidth = 75;
userButtonSmallWidth = 55;
userButtonHeight = 28;
constSmallOffset = 2;

constCardLargeHeight = 70;
constCardLargeWidth = 50;
constCardNormalHeight = 42;
constCardNormalWidth = 30;

constServiceUrl = "http://localhost//Sprint6//PokerService//PokerPlayerService.php";
constPollUrl = "http://localhost//Sprint6//PokerService//EventMessageService.php";
/*------------------------------------------------------------------------------------------*/
// helper functions

function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}
function S(obj) {
    return O(obj).style;
}
function C(name) {
    var elements = document.getElementsByName('*');
    var objects = [];
    for (var i = 0; i < elements.length; ++i) {
        if (elements[i].className == name) {
            objects.push(elements[i]);
            return objects;
        }
    }
    return null;
}

function getStyle(className, property) {
    var classes = document.styleSheets[0].rules || document.styleSheets[0].cssRules;
    for(var x=0, l=classes.length; x<l; x++) {
        if(classes[x].selectorText==className) {
            return classes[x].style[property];
            break;
        }
    }
    return null;
}

function getSize(pixel) {
    return Number(pixel.substr(0, pixel.length - 2));
}

/********************************************************************************************/
//FIXME: may want to keep an array of reverse key value pair
// FXME: rename to getPlayerPosition
function getPlayerElementByValue(playerId){
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

/********************************************************************************************/
function WSClient() {

}

WSClient.call = function(method, obj, callback) {
    var param = "method=" + method + "&param=" + JSON.stringify(obj);

    $.ajax({
        type: "GET",
        url: constServiceUrl,
        data: param,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (req) {
            // already parsed
            // var rval = $.parseJSON(req);
            callback(req);
        // return new GameSession(rval);
        },
        error: function (xhr) {
            alert(xhr.responseText);
            return;
        }
    });
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: add user to table, which may have a live game.
 * Complicated business rules, but minimal validation on client side.
 * 1) if game not active, don't display any cards,
 * 2) display player status in any case
 */
function addUserToCasinoTableCallback(gameStatusDto) {
    initBoardItemsDisplay();
 
    O('casinoTableId').innerHTML = gameStatusDto.casinoTableId;
    O('gameSessionId').innerHTML = gameStatusDto.gameSessionId;
    O('isPractice').innerHTML = 0;
    O('gameStatus').innerHTML = gameStatusDto.gameStatus;
    O('userPlayerId').innerHTML = gameStatusDto.userPlayerId;
    $.cookies.set('userPlayerId', gameStatusDto.userPlayerId);

    // initialize the seats. There's always at least one seat, which is occupied by user.
    setupPlayerStatuses(gameStatusDto.playerStatusDtos);
    if (gameStatusDto.playerStatusDtos.length > 4) {
        O('WaitingMessageId').innerHTML = 'There are ' + gameStatusDto.waitingListSize +
        ' players waiting for a seat';
    }
    if (gameStatusDto.gameStatus == 'Active'){
        showGameInProgress(gameStatusDto);
    }
    else if (gameStatusDto.gameStatus == 'Inactive'){
        if (gameStatusDto.playerStatusDtos.length == 1){
            // if user is the first on the table, a game cannot start until another user joins
            displayCenterMessage("Waiting for another user to join...");
        }
        if (gameStatusDto.playerStatusDtos.length > 1 &&
            gameStatusDto.userSeatNumber != null) {
            O('startGameButton').disabled = false;
            displayCenterMessage('Please press the start button to start play');
        }
    }
    if (gameStatusDto.userSeatNumber != null) {
        // only if user has a seat.
        positionUserButtons(gameStatusDto.userSeatNumber);
        resizeUserElements();
        O('casinoTableHeader').innerHTML = 'Casino Table ' + gameStatusDto.casinoTableId;
    }
    else {
        O('casinoTableHeader').innerHTML = 'Casino Table ' + gameStatusDto.casinoTableId + ' (On Waiting List)';
    }

    // startPolling();
    startQueueing();
}

function addUserToCasinoTable() {
    var tableSize = $.cookies.get("tableValue") == null ? null : ($.cookies.get("tableValue")).replace("table", "");
    var obj = {
        playerName:O('playerNameText').value,
        casinoTableId:O('tableIdText').value,
        tableSize:tableSize
    };

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("addUserToCasinoTable",
        obj,
        addUserToCasinoTableCallback);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: start a practice session.
 */
function startPracticeSessionCallback(gameInstanceSetupDto) {
    O('casinoTableHeader').innerHTML = 'Practice Game';

    initBoardItemsDisplay();
    
    // casino table is no null
    O('gameSessionId').innerHTML = gameInstanceSetupDto.gameSessionId;
    O('isPractice').innerHTML = 1;
    O('gameStatus').innerHTML = 'Active';
    // FIXME: place in cookie, also
    O('userPlayerId').innerHTML = gameInstanceSetupDto.userPlayerId;
    //resizeUserElements(gameInstanceSetupDto.userPlayerId);

    // set up the table with the new game values, such as blind bets
    setupTable(gameInstanceSetupDto);

    resizeUserElements();
    positionUserButtons(0);

    //startPolling();
    startQueueing();
}

function startPracticeSession() {
    // TODO: only practice sessions support by FE.
    obj = {
        playerName:O('playerNameText').value
    };

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("startPracticeSession",
        obj,
        startPracticeSessionCallback);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: start a new game
 * Automatically place the big and small blind bet, get dealer and deal cards
 */
function startGameCallback(gameInstanceSetupDto) {
    initBoardItemsDisplay();

    setupTable(gameInstanceSetupDto);

    O('gameStatus').innerHTML = 'Active';

}

function startGame() {
    var tableSize = $.cookies.get("tableValue") == null ? null : ($.cookies.get("tableValue")).replace("table", "");
    var obj = {
        gameSessionId:O('gameSessionId').innerHTML,
        requestingPlayerId:O('userPlayerId').innerHTML,
        isPractice:O('isPractice').innerHTML,
        tableSize:tableSize
    };

    WSClient.call("startGame",
        obj,
        startGameCallback);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: player action
Create JS object for game instance
 */
function sendPlayerActionCallback(resp) {
/*    var skipped = false;
    if (resp.playerStatusDto.status == 'Skipped') {
        skipped = true;
    }
    processStatusChange(resp.playerStatusDto);
    // disable buttons
    O('userRaiseButton').disabled = true;
    O('userCheckButton').disabled = true;
    O('userCallButton').disabled = true;
    O('userFoldButton').disabled = true;
    if (resp.isEndGameNext == 1) {
        var previousPlayerTag = getPlayerElementByValue(O('nextPlayerId').innerHTML);
        resetLastPlayerDisplay(previousPlayerTag, skipped);
        processGameResult(resp.gameResultDto);
        return;
    }
    processNextPlayer(resp, skipped); */
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

function sendPlayerAction(actionType, value) {
    var obj = {
        gameInstanceId: O('gameInstanceId').innerHTML,
        playerId: O('userPlayerId').innerHTML,
        pokerActionType: actionType,
        actionTime: new Date(),
        actionValue: value
    };

    WSClient.call("sendPlayerAction",
        obj,
        sendPlayerActionCallback);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: leave saloon, vacate seat
 */
function leaveSaloonCallback(resp) {
    stopQueueing();
    // FIXME
    window.location = resp.page + '.html';
}

function leaveSaloon() {
    var obj = {
        gameSessionId: O('gameSessionId').innerHTML,
        playerId: O('userPlayerId').innerHTML
    };
	
    WSClient.call("leaveSaloon",
        obj,
        leaveSaloonCallback);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: leave saloon, vacate seat
 */
function takeSeatCallback(resp) {
    //O('seatNumber').innerHTML = "";
    //displayCenterMessage("");
    // overlay button to allow user select seat
    S('takeSeatButton').display = 'none';
}


function takeSeat() {
    S("centerMessageId").display = 'none';
    var obj = {
        gameSessionId: O('gameSessionId').innerHTML,
        playerId: O('userPlayerId').innerHTML,
        seatNumber: O('seatNumber').innerHTML
    };
	
    WSClient.call("takeSeat",
        obj,
        takeSeatCallback);
}
/********************************************************************************************/
/*
 * Callback for polling function. Sets the next poll.
 */
function getEventMessageCallback(event) {
    var resp = jQuery.parseJSON(event.body);
    var message = resp.eventData;
    if (resp.eventType == "UserJoined") {
        if (message[0].seatNumber != null) {
            processStatusChange(message[0]);
        }
        else {
            O('WaitingMessageId').innerHTML = 'There are ' + message[0].waitingListSize +
            ' players waiting for a seat';
        }
        O('startGameButton').disabled = false;
        if (O('gameStatus').innerHTML == 'Inactive') {
            displayCenterMessage('Please press the start button to start play');
        }
        return;
    }
    if (resp.eventType == "UserLeft" || resp.eventType == "SeatTaken") {
        processStatusChange(message[0]);
        // FIXME:
        if (message[0].waitingListSize > 0) {
            O('WaitingMessageId').innerHTML = 'There are ' + message[0].waitingListSize +
            ' players waiting for a seat';
        }
        return;
    }
    if (resp.eventType == 'GameStarted'){
        initBoardItemsDisplay();
        setupTable(message);
        S("centerMessageId").display = 'none';
        O('gameStatus').innerHTML = 'Active';
        O('startGameButton').disabled = false;

        // this is in case the player was on waiting list before
        if (O('seatNumber').innerHTML != "") {
            O('casinoTableHeader').innerHTML = 'Casino Table ' + O('casinoTableId').innerHTML;
            if (S('userCallButton').display == 'none') {
                positionUserButtons(O('seatNumber').innerHTML);
                resizeUserElements();
            }
        }
        return;
    }
    if (resp.eventType == 'SeatOffer') {
        O('seatNumber').innerHTML = message;
        displayCenterMessage("You are being offered a seat...");
        // overlay button to allow user select seat
        S('takeSeatButton').display = 'block';
        S('takeSeatButton').top = S('player' + message + 'Table').top;
        S('takeSeatButton').left = S('player' + message + 'Table').left;
        S('takeSeatButton').width = getStyle('.playerTable', 'width')
        S('takeSeatButton').height = getStyle('.playerTable', 'height')
        return;
    }
    var skipped = false;
    if (message.playerStatusDto.status == 'Skipped') {
        skipped = true;
    }
    // process status change
    processStatusChange(message.playerStatusDto);
    // 1. Check if gameResultDto and update
    if (message.gameResultDto != null) {
        var previousPlayerTag = getPlayerElementByValue(O('nextPlayerId').innerHTML);
        resetLastPlayerDisplay(previousPlayerTag, skipped);
        processGameResult(message.gameResultDto);
    }
    // 2. else player made a move
    else {
        if (message.cardsToSend != null) {
            var communityCards = message.cardsToSend;
            // 2.1 Update community card
            for (var j=0, m=communityCards.length; j<m; j++) {
                showCommunityCard(communityCards[j].cardNumber-1, communityCards[j].cardName);
            }
        }
        if (message.playerStatusDto.status == "Skipped") {
            processNextPlayer(message, true);
        }
        else {
            processNextPlayer(message, false);
        }
    }
}

/*----------------------------------------------------------------------------------------- */
/*
function poll(param) {
    setTimeout(function() {

        $.ajax({
            type: "GET",
            url: constPollUrl,
            data: param,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            success: function (req) {
                // already parsed
                // var rval = $.parseJSON(req);
                getEventMessageCallback(req);
                startPolling(param);
                // return new GameSession(rval);
            },
            error: function (xhr) {
                alert(xhr);
                //alert(xhr.responseText);
                return;
            }
        });
    }, 2000)
}
*/
/*----------------------------------------------------------------------------------------- */
/*
function startPolling() {
    // get response if ready (no animation going on)
    if (cardQueue.length == 0) {
        //start polling
        obj = {
            gameSessionId:O('gameSessionId').innerHTML,
            requestingPlayerId:O('userPlayerId').innerHTML
        };
        var param = "method=getMessage&param=" + JSON.stringify(obj);
        poll(param);
    }
    else
    {
        setTimeout(startPolling, 1000);
    }
}
*/
/********************************************************************************************/
/* player status and cards */
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Show the back of game cards for all players.
 */
function initPlayersCardsDisplay() {
    S('player0Card1Image').display = 'block';
    S('player0Card2Image').display = 'block';

    S('player1Card1Image').display = 'block';
    S('player1Card2Image').display = 'block';

    S('player2Card1Image').display = 'block';
    S('player2Card2Image').display = 'block';

    S('player3Card1Image').display = 'block';
    S('player3Card2Image').display = 'block';

    O('player0Card1Image').src="../../../images/PokerCard_back_small.png";
    O('player0Card2Image').src="../../../images/PokerCard_back_small.png";

    O('player1Card1Image').src="../../../images/PokerCard_back_small.png";
    O('player1Card2Image').src="../../../images/PokerCard_back_small.png";

    O('player2Card1Image').src="../../../images/PokerCard_back_small.png";
    O('player2Card2Image').src="../../../images/PokerCard_back_small.png";

    O('player3Card1Image').src="../../../images/PokerCard_back_small.png";
    O('player3Card2Image').src="../../../images/PokerCard_back_small.png";
}

/**
 * update a player's card.
 */
function showPlayerCard(playerTag, cardPosition, cardValue) {
    var cardElementId = playerTag + 'Card' + cardPosition + 'Image';

    O(cardElementId).src = "../../../images/" + "PokerCard_" + cardValue + "_small.png";
//S(cardElementId).display = 'none'; // FIXME: deleteme
//S(cardElementId).display = 'block'; // reloads it
}

/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * When a user is current, the player status box outline and colors change. The HTML element
 * tracking current player is set to this player.
 * Used:
 * 1) processing next player
 * 2) setup new game
 * 3) show game in progress
 */
function resetLastPlayerDisplay(playerTag, skipped){
    if (skipped) {
        O(playerTag).getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxTimeOutBGColor;
    }
    else {
        O(playerTag).getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNormalBGColor;

    }
    userTag = getPlayerElementByValue(O('userPlayerId').innerHTML);
    O(playerTag).getElementsByTagName('table')[0].style.borderWidth = '1px';
    if (playerTag == userTag) {
        O(playerTag).getElementsByTagName('table')[0].style.borderWidth = "thick";
    }
}

function focusNextPlayerDisplay(playerTag){
    O(playerTag).getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNextBGColor;
    O(playerTag).getElementsByTagName('table')[0].style.borderWidth = '3px';
}

/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * When a player wins, its player status box outline and colors change
 */
function updateWinnerDisplay(playerTag){        
    S(playerTag + 'Message').fontsize = "160%";
    S(playerTag + 'Message').fontcolor = "yellow";
    O(playerTag + 'Status').innerHTML = "Won";

    O(playerTag).getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxWinnerBGColor;
    O(playerTag).getElementsByTagName('table')[0].style.borderWidth = '3px';
}

counter = null;
startX = null;
startY = null;
endX = null;
endY = null;
speedX = null;
speedY = null;
canvas = O('playGameCanvasId');
context  = canvas.getContext("2d");
cardQueue = new Array;
currentCard = null;
/*
 * shows the specified community card
 */
function showCommunityCard(cardPosition, cardValue) {
    card = new Object();
    card.position = cardPosition;
    card.image = O('communityCard' + cardPosition);
    card.image.src = "../../../images/" + "PokerCard_" + cardValue + "_small.png";
    card.playerId = -1;
    card.value = cardValue;
    cardQueue.push(card);
}

function animateCard() {
    // ready to draw new card
    if (counter == null) {

        if (cardQueue.length == 0) {
            // check again a a little later
            setTimeout(animateCard, 500);
        }
        else {
            counter = 0;
            currentCard = cardQueue.shift();
            // initialize
            var dealerPlayerTag = getPlayerElementByValue(O('currentDealerId').innerHTML);
            // show dealer button
            var dealerButtonStyle = O(dealerPlayerTag).getElementsByTagName('label')[0].style;
            startX = getSize(dealerButtonStyle.left);
            startY = getSize(dealerButtonStyle.top);
            if (currentCard.playerId == -1 ) {
                endX = getSize(S('communityCard' + currentCard.position).left);
                endY = getSize(S('communityCard' + currentCard.position).top);
            }
            speedX = (endX - startX) / 30;
            speedY = (endY - startY) / 30;

            setTimeout(drawCard, 30);
        }
    }
    // still animating a card
    else {
        setTimeout(drawCard, 30);
    }
}
function drawCard() {
    context.clearRect(0, 0, canvas.width, canvas.height);
    counter++;
    if (counter < 30) {
        context.drawImage(currentCard.image, startX, startY, constCardNormalWidth, constCardNormalHeight);
        //img.src = currentCard.image.src;
        startX += speedX;
        startY += speedY;
        setTimeout(drawCard, 30);
    }
    else if (counter >= 30) {
        counter = null;
        currentCard.image.style.display = 'block';
        //O('communityCard' + cardPosition).src = "../Images/" + "PokerCard_" + cardValue + "_small.png";

        //S('communityCard' + cardPosition).display = 'block';
        // get next
        setTimeout(animateCard, 30)
    }
}
/** ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Calculate the chip sizes and colors appropriate for the bet amount and display them
 */
function showChips(playerTag, betAmount) {
}

/**
 * animation for moving a chip from a player to the pot
 */
function moveChips() {
    context.save();
    // get list of all blind bets
    // loop to move incrementally
    context.rotate(Math.PI / 20);
    context.restore();
};

/********************************************************************************************/
/*
 * Update display for a player status that changed.
 */
function processStatusChange(playerStatus) {
    // updates status
    var playerPosition = playerStatus.seatNumber;
    O('player' + playerPosition + 'Stake').innerHTML = playerStatus.stake;
    O('player' + playerPosition + 'Status').innerHTML = playerStatus.status;
    if (playerStatus.status == "Folded" || playerStatus.status == "Left") {
        processFoldOrLeft(playerStatus.playerId, playerStatus.status);
    }
    else {
        O('player' + playerPosition + 'Id').innerHTML = playerStatus.playerId;
        O('player' + playerPosition + 'Name').innerHTML = playerStatus.playerName;
        O('player' + playerPosition + 'Image').innerHTML = playerStatus.playerImageUrl;
        O('player' + playerPosition + 'Message').value = playerStatus.playAmount;
    }
    if (O('player' + playerPosition + 'Message').value == 'null' ||
        O('player' + playerPosition + 'Message').value == '0' ) {
        O('player' + playerPosition + 'Message').value = "";
    }
}

/*-------------------------------------------------------------------------------------------
 * Highlight next player who needs to make a move. If that player is the user, make the
 * call, fold, etc. buttons available.
 */
function processNextPlayer(nextMove, skipped) {
    // set next player id
    var previousPlayerId = O('nextPlayerId').innerHTML;
    var previousPlayerTag = getPlayerElementByValue(previousPlayerId);
    O('nextPlayerId').innerHTML = nextMove.nextPlayerId;
    var nextPlayerTag = getPlayerElementByValue(O('nextPlayerId').innerHTML);
    resetLastPlayerDisplay(previousPlayerTag, skipped);
    focusNextPlayerDisplay(nextPlayerTag);

    if (O('userPlayerId').innerHTML == O('nextPlayerId').innerHTML) {
        O('userCallAmount').innerHTML = nextMove.callAmount;
        O('userRaiseAmount').innerHTML = nextMove.raiseAmount;
        O('userRaiseButton').value = 'Raise ' + nextMove.raiseAmount;
        enableUserButtons(nextMove.checkAmount);
    }
    if (O('userPlayerId').innerHTML == previousPlayerId) {
        O('userRaiseButton').disabled = true;
        O('userCheckButton').disabled = true;
        O('userCallButton').disabled = true;
        O('userFoldButton').disabled = true;
    }
}

/*-------------------------------------------------------------------------------------------
 *
 */
function processGameResult(gameResultDto) {
    // show everyone's hands
    for(var i=0, l=gameResultDto.playerHands.length; i<l; i++) {
        var playerElement = getPlayerElementByValue(gameResultDto.playerHands[i].playerId);
        showPlayerCard(playerElement, 1, gameResultDto.playerHands[i].pokerCard1.cardName)
        showPlayerCard(playerElement, 2, gameResultDto.playerHands[i].pokerCard2.cardName);
        O(playerElement + 'Message').value = gameResultDto.playerHands[i].pokerHandType;
        O(playerElement + 'Status').innerHTML = 'Lost';
    }
    // update stakes
    for (var i=0, l=gameResultDto.playerStatusDtos.length; i<l; i++) {
        var playerElement = getPlayerElementByValue(gameResultDto.playerStatusDtos[i].playerId);
        O(playerElement + 'Stake').innerHTML = gameResultDto.playerStatusDtos[i].stake;
    }
    // set winner
    var winnerElement = getPlayerElementByValue(gameResultDto.winningPlayerId);
    // FIXME: find previous
    updateWinnerDisplay(winnerElement);
    O('startGameButton').disabled = false;
}

/*-------------------------------------------------------------------------------------------
 *
 */
function processFoldOrLeft(playerId, status) {
    var playerTag = getPlayerElementByValue(playerId);
    O(playerTag + 'Message').value = status;
    S(playerTag + 'Card1Image').display = 'none';
    S(playerTag + 'Card2Image').display = 'none';
	
}

/*-------------------------------------------------------------------------------------------
 * Update the player statuses data on the browser; used both when there is an ongoing game or
 * when a user joins a table.
 */
function setupPlayerStatuses(playerStatuses) {
    var playerCount = playerStatuses.length;
    for (var i=0; i<playerCount; i++) {
        if (playerStatuses[i].seatNumber != null) {
            processStatusChange(playerStatuses[i]);
        }
    }
}
/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Set up a new game including user's cards, blind bets, dealer and player statuses
 * Two types: game is ongoing or not.
 * START POLLING
 * 1) start practice session
 * 2) user joins a table and there is no game in session (blinds, hands and dealer skipped)
 * 3) user starts new game
 * 4) polled message for new game
 */
function setupTable(gameInstanceSetupDto) {
    O('gameInstanceId').innerHTML = gameInstanceSetupDto.gameInstanceId;

    // update everyone's statuses
    initPlayersCardsDisplay();
    setupPlayerStatuses(gameInstanceSetupDto.playerStatusDtos);

    // put blind bets
    for (var k=0, n=gameInstanceSetupDto.blindBets.length; k<n; k++) {
        var blindPosition = getPlayerElementByValue(gameInstanceSetupDto.blindBets[k].playerId);
        O(blindPosition + 'Message').value = gameInstanceSetupDto.blindBets[k].betSize;
    }

    // get the user's hands'
    var playerElement;
    playerElement = getPlayerElementByValue(O('userPlayerId').innerHTML);
    if (gameInstanceSetupDto.userPlayerHand != null) {
        // null if user is in waiting list
        O(playerElement + 'Card1').innerHTML = gameInstanceSetupDto.userPlayerHand.pokerCard1.cardName;
        O(playerElement + 'Card2').innerHTML = gameInstanceSetupDto.userPlayerHand.pokerCard2.cardName;
        showPlayerCard(playerElement, 1, gameInstanceSetupDto.userPlayerHand.pokerCard1.cardName);
        showPlayerCard(playerElement, 2, gameInstanceSetupDto.userPlayerHand.pokerCard2.cardName);
    }
    // next player
    O('nextPlayerId').innerHTML = gameInstanceSetupDto.firstPlayerId;
    // update display with current user and allowed action
    var nextPlayerTag = getPlayerElementByValue(O('nextPlayerId').innerHTML);
    focusNextPlayerDisplay(nextPlayerTag);

    // dealer designation
    var dealerPlayerTag = getPlayerElementByValue(gameInstanceSetupDto.dealerPlayerId);
    //O('dealerPlayerId').innerHTML = resp.dealerPlayerId;
    O('currentDealerId').innerHTML = gameInstanceSetupDto.dealerPlayerId;
    // show dealer button
    O(dealerPlayerTag).getElementsByTagName('label')[0].style.display = 'block';

    if (O('userPlayerId').innerHTML == gameInstanceSetupDto.firstPlayerId){
        // call and raise amounts (only for first move raise is 2*blind
        O('userCallAmount').innerHTML = gameInstanceSetupDto.blindBets[1].betSize;
        O('userRaiseAmount').innerHTML = 2*O('userCallAmount').innerHTML;
        O('userRaiseButton').value = 'Raise ' + O('userRaiseAmount').innerHTML;
        // check is disabled when a game first starts
        enableUserButtons(null);
    }
}

/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Show ongoing game after user joins a table
 */
function showGameInProgress(gameStatusDto) {
    O('gameInstanceId').innerHTML = gameStatusDto.gameInstanceId;

    // update everyone's statuses
    initPlayersCardsDisplay();

    if (gameStatusDto.communityCards != null ) {
        var m=gameStatusDto.communityCards.length;
        for (var j=0; j<m; j++) {
            showCommunityCard(gameStatusDto.communityCards[j].cardNumber-1, gameStatusDto.communityCards[j].cardName);
        }
    }
    if (gameStatusDto.gameResultDto != null) {
        processGameResult(gameStatusDto.gameResultDto);
    }
    // the user may have a seat if this operationi s called because re-opened the browser
    // in the middle of the game
    if (gameStatusDto.userPlayerHand != null) {
        var playerElement
        playerElement = getPlayerElementByValue(O('userPlayerId').innerHTML);
        O(playerElement + 'Card1').innerHTML = gameStatusDto.userPlayerHand.pokerCard1.cardName;
        O(playerElement + 'Card2').innerHTML = gameStatusDto.userPlayerHand.pokerCard2.cardName;
        // FIXME: the status return is null, so it's set in the UI but
        // should come from the back-end
        O(playerElement + 'Status').innerHTML = 'Ready';
        showPlayerCard(playerElement, 1, gameStatusDto.userPlayerHand.pokerCard1.cardName);
        showPlayerCard(playerElement, 2, gameStatusDto.userPlayerHand.pokerCard2.cardName);

        // game has not started if player hands are null
        // next player
        O('nextPlayerId').innerHTML = gameStatusDto.firstPlayerId;
        // update display with current user and allowed action
        var nextPlayerTag = getPlayerElementByValue(O('nextPlayerId').innerHTML);
        focusNextPlayerDisplay(nextPlayerTag);
    }
    
    // dealer designation
    var dealerPlayerTag = getPlayerElementByValue(gameStatusDto.dealerPlayerId);
    //O('dealerPlayerId').innerHTML = resp.dealerPlayerId;
    O('currentDealerId').innerHTML = gameStatusDto.dealerPlayerId;
    // show dealer button
    O(dealerPlayerTag).getElementsByTagName('label')[0].style.display = 'block';

    // check is disabled when a game first starts
    // FIXME:
    if (gameStatusDto.nextMove != null) {
        processNextPlayer(gameStatusDto.nextMoveDto, false);

        enableUserButtons(gameStatusDto.nextMoveDto.checkAmount);
    }
    else {
        enableUserButtons(null);
    }
}

/********************************************************************************************/
/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Position the call, check, raise, etc. buttons next to the user status box.
 */
function positionUserButtons(seatNumber) {
    var userTag = 'player' + seatNumber;

    var userCheckButtonStyle = S('userCheckButton');
    var userRaiseButtonStyle = S('userRaiseButton');
    var userCallButtonStyle = S('userCallButton');
    var userFoldButtonStyle = S('userFoldButton');
    //var userRaiseAmountStyle = S('userRaiseAmount');

    // top - two layers
    // top layer: raise amount and button
    // bottom layer: fold, call, check
    switch (userTag) {
        case "player0":
        case "player3":
            var minBottomOffset = bH - constStatusBoxHeight - constLargeOffset - ubH;
            userRaiseButtonStyle.top = minBottomOffset - (ubH + constSmallOffset) + 'px';
            userCheckButtonStyle.top = minBottomOffset + 'px';
            //userRaiseAmountStyle.top = minBottomOffset - (ubH + constSmallOffset) + 'px';
            break;
        case "player1":
        case "player2":
            var minTopOffset = constStatusBoxHeight + constLargeOffset + constSmallOffset;
            userRaiseButtonStyle.top = minTopOffset + 'px';
            userCheckButtonStyle.top = minTopOffset + (ubH + constSmallOffset) + 'px';
            //userRaiseAmountStyle.top = minTopOffset + (ubH + constSmallOffset) + 'px';
            break;
    }
    userCallButtonStyle.top = userRaiseButtonStyle.top;
    userFoldButtonStyle.top = userCheckButtonStyle.top;

    switch (userTag) {
        case "player0":
        case "player1":
            var minLeftOffset = 0;
            //userRaiseAmountStyle.left = minLeftOffset + 'px';
            break;
        case "player2":
        case "player3":
            minLeftOffset = bW - (statusW + constObjectOffset);
            //userRaiseAmountStyle.left = minRightOffset + 'px';
            break;
    }
    userRaiseButtonStyle.left = minLeftOffset + 'px';
    userCallButtonStyle.left = minLeftOffset + ubWW + constObjectOffset + 'px';
    userCheckButtonStyle.left = minLeftOffset + 'px';
    userFoldButtonStyle.left = minLeftOffset + ubMW + constObjectOffset + 'px';

    userCheckButtonStyle.display = 'block';
    userCallButtonStyle.display = 'block';
    userCallButtonStyle.display = 'block';
    userFoldButtonStyle.display = 'block';
    //userRaiseAmountStyle.display = 'block';
    userRaiseButtonStyle.display = 'block';
}

/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Size playe cards to be bigger, which requires positioning changes
 * - all cards: second card moved (right and left) by card width difference
 * - bottom cards: move both cards up by card height difference
 */
function resizeUserElements() {
    var uTag = getPlayerElementByValue(O('userPlayerId').innerHTML);
    
    // add border on status
    var userTableStyle = O(uTag).getElementsByTagName('table')[0].style;
    userTableStyle.borderStyle = "dotted";
    userTableStyle.borderColor = "#504D64";
    userTableStyle.borderWidth = "thick";
    
    var extraW = constCardLargeWidth - constCardNormalWidth + constObjectOffset;
    var extraH = constCardLargeHeight - constCardNormalHeight + constObjectOffset;
    if (uTag == 'player0' || uTag == 'player3') {
        var newTop =  getSize(S(uTag + 'Card1Image').top) - extraH + 'px';
        S(uTag + 'Card1Image').top = newTop;
        S(uTag + 'Card2Image').top = newTop;
    }
    switch (uTag) {
        case 'player0':
        case 'player1':
            var newLeft = getSize(S(uTag + 'Card2Image').left) + extraW + 'px';
            S(uTag + 'Card2Image').left = newLeft;
            break;
        case 'player2':
        case 'player3':
            var newRight = getSize(S(uTag + 'Card2Image').left) - extraW + 'px';
            S(uTag + 'Card2Image').left = newRight;
            S(uTag + 'Card1Image').left = getSize(S(uTag + 'Card1Image').left) - constObjectOffset + 'px';
            break;
    }
    $("#" + uTag + 'Card1Image').css("height", constCardLargeHeight);
    $("#" + uTag + 'Card1Image').css("width", constCardLargeWidth);

    $("#" + uTag + 'Card2Image').css("height", constCardLargeHeight);
    $("#" + uTag + 'Card2Image').css("width", constCardLargeWidth);
}

/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * enable user buttons on two ocasions
 * 1) user has next move
 * 2) user has first move
 */
function enableUserButtons(checkAmount) {
    O('userRaiseButton').disabled = false;
    O('userCheckButton').disabled = checkAmount == null ? true : false;
    O('userCallButton').disabled = false;
    O('userFoldButton').disabled = false;
}

/********************************************************************************************/
/* display and positioning */
/*^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * processing: set positions for all HTML elements dynamically in case of sizing
 * dynamically position player status boxes, which are shown
 * set position for all cards and chips but keep hidden
 * TODO: may have more than 4 players
 * 1) all the player's items such as status box, messages, dealer button and cards
 * 2) community cards
 */
window.onload = function() {
    $('#dialog-modal').dialog('open');
    document.getElementById('playerNameText').select();
    
    var widthToHeight = 4 / 3;
    var newWidth = window.innerWidth;
    var newHeight = window.innerHeight;

    // board table sizes
    bW = getSize(S('boardTable').width);
    bH = getSize(S('boardTable').height);

    // player status sizes
    var boxWStyle = getStyle('.playerTable', 'width');
    var boxHStyle = getStyle('.playerTable', 'height');
    statusW = Number(boxWStyle.substr(0, boxWStyle.length - 2));
    statusH = Number(boxHStyle.substr(0, boxHStyle.length - 2));

    // dealer button sizes
    var dWStyle = getStyle('.dealerButton', 'width');
    var dHStyle = getStyle('.dealerButton', 'height');
    dW = Number(dWStyle.substr(0, dWStyle.length - 2));
    dH = Number(dHStyle.substr(0, dHStyle.length - 2));

    // button width and height
    ubH = userButtonHeight;
    ubWW = userButtonWideWidth;
    ubMW = userButtonMediumWidth;
    ubSW = userButtonSmallWidth;

    // community card sizes
    var cardWStyle = getStyle('.communityCard', 'width');
    var cardHStyle = getStyle('.communityCard', 'height');
    cardW = Number(cardWStyle.substr(0, cardWStyle.length - 2));
    cardH = Number(cardHStyle.substr(0, cardHStyle.length - 2));

    $("#playGameCanvasId").css("top", getSize(S('boardTable').top));
    $("#playGameCanvasId").css("left", getSize(S('boardTable').left));
    canvas.top = S('boardTable').top;
    canvas.left = S('boardTable').left;
    S('gameBackground').height = S('boardTable').height;
    S('gameBackground').width = S('boardTable').width;
    S('gameBackground').top = S('boardTable').top;
    S('gameBackground').left = S('boardTable').left;
    //test canvas
    //context.fillStyle="#FF0000";
    //context.fillRect(0, 0, 400, 400);
    positionSizeBoardItems();
    animateCard();

}

window.onbeforeunload = function() {
    stopQueueing();
}

window.onunload = function(){
    stopQueueing();
}
window.onreset = function() {
    stopQueueing();
}
function startQueueing() {
    // websockets
    Stomp.WebSocketClass = SockJS;
    client = Stomp.client('http://127.0.0.1:55674/stomp');

    // FIXME: log error
    error_callback = function(error) {
        alert (error);
    }

    var queue = '/queue/' + O('userPlayerId').innerHTML;
    connect_callback = function(data) {
        client.subscribe(queue, getEventMessageCallback);
    }
    // format: client.connect(login, passcode, connect_callback, error_callback);
    client.connect('guest', 'guest', connect_callback, error_callback, '/');
}

function stopQueueing() {
    if (client != undefined && client != null) {
        client.disconnect();
    }
}
/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Position items on the board dynamically. 
 * TODO: use window size and test different sizes
 */
function positionSizeBoardItems() {
    // get the fixed sizes first, so they can be centered and offset appropriatel
    // get handlers to common elements

    // FIXME: assuming first label is dealer button, may want to use id
    var player0Style = O('player0').getElementsByTagName('table')[0].style;
    var player0DealerStyle = O('player0').getElementsByTagName('label')[0].style;
    var player0Card1Style = S('player0Card1Image');
    var player0Card2Style = S('player0Card2Image');
    var player0MessageStyle = S('player0Message');

    var player1Style = O('player1').getElementsByTagName('table')[0].style;
    var player1DealerStyle = O('player1').getElementsByTagName('label')[0].style;
    var player1Card1Style = S('player1Card1Image');
    var player1Card2Style = S('player1Card2Image');
    var player1MessageStyle = S('player1Message');

    var player2Style = O('player2').getElementsByTagName('table')[0].style;
    var player2DealerStyle = O('player2').getElementsByTagName('label')[0].style;
    var player2Card1Style = S('player2Card1Image');
    var player2Card2Style = S('player2Card2Image');
    var player2MessageStyle = S('player2Message');

    var player3Style = O('player3').getElementsByTagName('table')[0].style;
    var player3DealerStyle = O('player3').getElementsByTagName('label')[0].style;
    var player3Card1Style = S('player3Card1Image');
    var player3Card2Style = S('player3Card2Image');
    var player3MessageStyle = S('player3Message');

    var communityCard0Style = S('communityCard0');
    var communityCard1Style = S('communityCard1');
    var communityCard2Style = S('communityCard2');
    var communityCard3Style = S('communityCard3');
    var communityCard4Style = S('communityCard4');

    var playerLeft = constObjectOffset;
    var playerBottomTop = bH - constStatusBoxHeight - constObjectOffset; // for math
    /////////////////////////////////////////////////////////
    player0Style.top = playerBottomTop + 'px';
    player0Style.left = playerLeft + 'px';
    player0Style.width = constStatusBoxWidth + 'px';

    player0DealerStyle.top = bH - dH - constLargeOffset + 'px';
    player0DealerStyle.left = constStatusBoxWidth + 2*constLargeOffset + 'px';

    player0Card1Style.top = bH - constCardNormalHeight - dH - constLargeOffset + 'px'; // offset by button
    player0Card1Style.left = constStatusBoxWidth + 2*constLargeOffset + 'px';

    // see player0card1Style.
    player0Card2Style.top = bH - constCardNormalHeight - dH - constLargeOffset + 'px';
    player0Card2Style.left = constStatusBoxWidth + constCardNormalWidth + 3*constLargeOffset + 'px';

    player0MessageStyle.top = bH - (constStatusBoxHeight + constObjectOffset + 2*constBetMessageHeight) + 'px';
    player0MessageStyle.left = constStatusBoxWidth + constObjectOffset + 'px';
    player0MessageStyle.textAlign = "center";
    /////////////////////////////////////////////////////////
    player1Style.top = constObjectOffset + 'px';
    player1Style.left = constObjectOffset + 'px';

    player1DealerStyle.top = player1Style.top;
    player1DealerStyle.left = constStatusBoxWidth + constLargeOffset + 'px';

    player1Card1Style.top = constDealerButtonHeight + constLargeOffset + 'px';
    player1Card1Style.left = player0Card1Style.left;

    player1Card2Style.top = player1Card1Style.top;
    player1Card2Style.left = player0Card2Style.left;
    
    player1MessageStyle.top = constStatusBoxHeight + constObjectOffset + 2*constBetMessageHeight + 'px';
    player1MessageStyle.left = player0MessageStyle.left;
    player1MessageStyle.textAlign = "center";

    /////////////////////////////////////////////////////////

    player2Style.top = player1Style.top;
    player2Style.left = bW - (constStatusBoxWidth + constObjectOffset) + 'px';

    player2DealerStyle.top = player2Style.top;
    player2DealerStyle.left = bW - (constStatusBoxWidth + constLargeOffset + dW) + 'px';

    player2Card1Style.top = player1Card1Style.top;
    player2Card1Style.left = bW - (constStatusBoxWidth + 2*constLargeOffset + constCardNormalWidth) + 'px';

    player2Card2Style.top = player1Card2Style.top;
    player2Card2Style.left = bW - (constStatusBoxWidth + 3*constLargeOffset + 2*constCardNormalWidth) + 'px';

    player2MessageStyle.top = player1MessageStyle.top;
    player2MessageStyle.left = bW - (constStatusBoxWidth + constObjectOffset + constBetMessageWidth ) + 'px';
    player2MessageStyle.textAlign = "center";

    /////////////////////////////////////////////////////////

    player3Style.top = player0Style.top;
    player3Style.left = player2Style.left;

    player3DealerStyle.top = player0DealerStyle.top;
    player3DealerStyle.left = player2DealerStyle.left;

    player3Card1Style.top = player0Card1Style.top;
    player3Card1Style.left = player2Card1Style.left;

    player3Card2Style.top = player0Card1Style.top;
    player3Card2Style.left = player2Card2Style.left;

    player3MessageStyle.top = player0MessageStyle.top;
    player3MessageStyle.left = player2MessageStyle.left;
    player3MessageStyle.textAlign = "center";

    // community cards
    communityCard0Style.top = Math.round(bH / 2 - (cardH / 2)) + 'px';
    communityCard1Style.top = communityCard0Style.top;
    communityCard2Style.top = communityCard0Style.top;
    communityCard3Style.top =communityCard0Style.top;
    communityCard4Style.top =communityCard0Style.top;
	
    communityCard0Style.left = bW - Math.round(bW / 2) - 2*(cardW + constObjectOffset) + 'px';
    communityCard1Style.left = bW - Math.round(bW / 2) - 1*(cardW + constObjectOffset) + 'px';
    communityCard2Style.left = bW - Math.round(bW / 2) + 'px';
    communityCard3Style.left = bW - Math.round(bW / 2) + 1*(cardW + constObjectOffset) + 'px';
    communityCard4Style.left = bW - Math.round(bW / 2) + 2*(cardW + constObjectOffset) + 'px';

    // un-hide the status boxes. just in case, should neve be hidden...
    S('player0').display = 'block';
    S('player1').display = "inline";
    S('player2').display = "inline";
    S('player3').display = "inline";

}

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
function initBoardItemsDisplay() {
    // everything is hidden
    S('communityCard0').display = 'none';
    S('communityCard1').display = 'none';
    S('communityCard2').display = 'none';
    S('communityCard3').display = 'none';
    S('communityCard4').display = 'none';

    S('player0Card1Image').display = 'none';
    S('player0Card2Image').display = 'none';

    S('player1Card1Image').display = 'none';
    S('player1Card2Image').display = 'none';

    S('player2Card1Image').display = 'none';
    S('player2Card2Image').display = 'none';

    S('player3Card1Image').display = 'none';
    S('player3Card2Image').display = 'none';

    // hide dealer buttons
    O('player0').getElementsByTagName('label')[0].style.display = 'none';
    O('player1').getElementsByTagName('label')[0].style.display = 'none';
    O('player2').getElementsByTagName('label')[0].style.display = 'none';
    O('player3').getElementsByTagName('label')[0].style.display = 'none';

    // display status
    O('player0').getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNormalBGColor;
    O('player0').getElementsByTagName('table')[0].style.borderWidth = '1px';

    O('player1').getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNormalBGColor;
    O('player1').getElementsByTagName('table')[0].style.borderWidth = '1px';

    O('player2').getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNormalBGColor;
    O('player2').getElementsByTagName('table')[0].style.borderWidth = '1px';

    O('player3').getElementsByTagName('table')[0].style.backgroundColor = constStatusBoxNormalBGColor;
    O('player3').getElementsByTagName('table')[0].style.borderWidth = '1px';

    // O('gamePlayerNumber').innerHTML = 0;
    // O('nextPlayerId').innerHTML = '0';
    O('nextCommunityCardPosition').value = 0;

    // erase any previous message
    O('player0Message').value = "";
    O('player1Message').value = "";
    O('player2Message').value = "";
    O('player3Message').value = "";

    // reset the size, which may have been changed during the game.
    S('player0Message').fontsize = "120%";
    S('player1Message').fontsize = "120%";
    S('player2Message').fontsize = "120%";
    S('player3Message').fontsize = "120%";

    S('player0Message').fontcolor = "black";
    S('player1Message').fontcolor = "black";
    S('player2Message').fontcolor = "black";
    S('player3Message').fontcolor = "black";

    O('userRaiseButton').disabled = true;
    O('userCheckButton').disabled = true;
    O('userCallButton').disabled = true;
    O('userFoldButton').disabled = true;

    O('centerMessageId').innerHTML = "";
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Given a text message, display it on the center
 * FIXME: to be a popup or a rasterized image, HTML text not centering properly
 */
function displayCenterMessage(msg) {
    S("centerMessageId").top = bH/2 - 30 + 'px';
    S("centerMessageId").left = bW/2 - 120 + 'px';
    O("centerMessageId").innerHTML = msg;
    S("centerMessageId").display = 'block';
}

