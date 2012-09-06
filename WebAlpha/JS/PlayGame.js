/** Main game play javascript
 * 
 */
// FIXME: need to split up cheating and poker playing
constServiceUrl = "http://localhost//Sprint8//PokerService//PokerPlayerService.php";
constServer = "http://localhost:55674/stomp";

/*------------------------------------------------------------------------------------------*/
// helper functions

function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}
function S(obj) {
    return O(obj).style;
}

/**
 * Removes the 'px' on a CSS dimensional attribute
 */
function getSize(pixel) {
    return Number(pixel.substr(0, pixel.length - 2));
}


function dimItem(id) {
    $('#' + id).addClass("fade");
}

function unDimItem(id) {
    $('#' + id).removeClass("fade");
}

/********************************************************************************************/
//FIXME: may want to keep an array of reverse key value pair
function getPlayerPositionTag(playerId){
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
            if (callback != null) {
                callback(req);
            }
        },
        error: function (xhr) {
            alert(xhr.responseText);
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
        O('waitingMessageId').innerHTML = 'There are ' + gameStatusDto.waitingListSize +
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
            unDimItem('startGameButton');
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

    startQueueing();
}

function addUserToCasinoTable() {
    var tableSize = $.cookies.get("tableValue").replace("table", "");
    var obj = {
        userPlayerId:$.cookies.get("userPlayerId"),
        casinoTableId: O('tableIdText').value == "" ? null : O('tableIdText').value,
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
    O('startGameButton').disabled = false;
    unDimItem('startGameButton');
    //startPolling();
    startQueueing();
}

function startPracticeSession() {
    // TODO: only practice sessions support by FE.
    var obj = {
        userPlayerId:$.cookies.get("userPlayerId")
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
/*
function startGameCallback(gameInstanceSetupDto) {
    initBoardItemsDisplay();

    setupTable(gameInstanceSetupDto);

    O('gameStatus').innerHTML = 'Active';
}
*/

function startGame() {
    var tableSize = $.cookies.get("tableValue").replace("table", "");
    var obj = {
        gameSessionId:O('gameSessionId').innerHTML,
        requestingPlayerId:O('userPlayerId').innerHTML,
        isPractice:O('isPractice').innerHTML,
        tableSize:tableSize
    };

    WSClient.call("startGame",
        obj,
        //startGameCallback);
        null);
}

/*-------------------------------------------------------------------------------------------
 * AJAX call: player action
Create JS object for game instance
 */
function sendPlayerActionCallback(resp) {
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
    window.location = resp.page + '.php';
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
/* cheating AJAX functions */
function cheatSuitMarkerCallback(suit, cardList) {
    /* FIXME: same as diamond and heart marker reuse */
    for (var j=0, m=cardList.length; j<m; j++) {
        if (cardList[j].suit != null && cardList[j].playerId != O('userPlayerId').innerHTML) {
            var playerTag = getPlayerPositionTag(cardList[j].playerId);
            var cardMarkerTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'Marker';
            // using the small marker
            var cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'SmallMarker';
            S(cardMarkerTag).display = 'block';
            /* set the class */
            O(cardMarkerTag).src = "../../../images/PokerCard_" + suit + "_small.png";
            O(cardMarkerTag).setAttribute("class", 'cardSmallMarker ' + cardMarkerClassTag);
        }
    }
}

function cheatHeartMarkerCallback(cardList) {
    //cheatSuitMarkerCallback('hearts', cardList);
}

function cheatHeartMarker() {
    var obj = {
        itemType: 'HeartMarker',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };

    WSClient.call("cheat",
        obj,
        //cheatHeartMarkerCallback);
        null);
}

function cheatClubsMarkerCallback(cardList) {
    //cheatSuitMarkerCallback('clubs', cardList);
}

function cheatClubsMarker() {
    var obj = {
        itemType: 'ClubMarker',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };

    WSClient.call("cheat",
        obj,
        cheatClubsMarkerCallback);
}

function cheatDiamondMarkerCallback(cardList) {
    //cheatSuitMarkerCallback('diamonds', cardList);
}

function cheatDiamondMarker() {
    var obj = {
        itemType: 'DiamondMarker',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };

    WSClient.call("cheat",
        obj,
        cheatDiamondMarkerCallback);
}

function cheatAcePusherCallback(returnDto) {
// FIXME: hard coded 
/*
	var playerElement = getPlayerPositionTag(returnDto.playerId);
        O(playerElement + 'Card' + returnDto.playerCardNumber).innerHTML = returnDto.cardName;
    showPlayerCard(playerElement, 1, returnDto.cardName); */
}

function cheatAcePusher(cardNumber) {
    var obj = {
        itemType: 'AcePusher',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML,
        playerCardNumber: cardNumber
    };

    WSClient.call("cheat",
        obj,
        //cheatAcePusherCallback);
        null);
}

function cheatSocialSpotterCallback(cardList) {
    //cheatMarkOthersCardsMarks(cardList);
}
function cheatSocialSpotter() {
    var obj = {
        itemType: 'SocialSpotter',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };

    WSClient.call("cheat",
        obj,
        null);
}

function cheatLookRiverCardCallback(cardList) {
    // FIXME: should be an event
    //cheatUpdateNextCards(cardList, 'This is the next river card');
    O('LookRiverCard-swap').disabled = false;
    O('LookRiverCard-look').disabled = true;
}
function cheatLookRiverCard() {
    var instanceId = O('gameInstanceId').innerHTML;
    if (instanceId == null) {
        alert('There is no active game');
        return;
    }
    var obj = {
        itemType: 'LookRiverCard',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };
    WSClient.call("cheat",
        obj,
        cheatLookRiverCardCallback);
}

function cheatSwapRiverCardCallback(playerHandDtos) {
//cheatUpdateUserHands(playerHandDtos);
    O('LookRiverCard-swap').disabled = true;
    O('LookRiverCard-look').disabled = false;
}

function cheatSwapRiverCard() {
    var obj = {
        itemType: 'SwapRiverCard',
        userPlayerId: O('userPlayerId').innerHTML,
        gameSessionId: O('gameSessionId').innerHTML,
        gameInstanceId: O('gameInstanceId').innerHTML
    };
    WSClient.call("cheat",
        obj,
        cheatSwapRiverCardCallback);
}

function cheatLoadSleeve() {
    var obj = {
        userPlayerId: $.cookies.get("userPlayerId")
    };
    
    WSClient.call("cheatLoadSleeve",
        obj,
        cheatUpdateHiddenCards);
}

/********************************************************************************************/
// Table Management event call backs
/** 
 * User got added
 */
function addUserToTable(playerStatusDto) {
    if (playerStatusDto.seatNumber != null) {
        processStatusChange(playerStatusDto);
    }
    // if no seat number, user went to waiting list.
    else {
        O('waitingMessageId').innerHTML = 'There are ' + playerStatusDto.waitingListSize +
        ' players waiting for a seat';
    }
    O('startGameButton').disabled = false;
	unDimItem('startGameButton');
	
    if (O('gameStatus').innerHTML == 'Inactive') {
        displayCenterMessage('Please press the start button to start play');
    }
}
/**
 * User left or took a seat at table
 */
function updateUserTableStatus(playerStatusDto) {
    processStatusChange(playerStatusDto);
    // FIXME:
    if (playerStatusDto.waitingListSize > 0) {
        O('waitingMessageId').innerHTML = 'There are ' + playerStatusDto.waitingListSize +
        ' players waiting for a seat';
    }
}
/**
 * Game started
 */
function processGameStarted(gameInstanceSetupDto) {
    var previouslyWaiting = false;
    if (O('seatNumber').innerHTML == "") {previouslyWaiting = true; }
    initBoardItemsDisplay();

    setupTable(gameInstanceSetupDto);

    O('gameStatus').innerHTML = 'Active';

    S("centerMessageId").display = 'none';
    O('startGameButton').disabled = false;
	unDimItem('startGameButton');
	
    // this is in case the player was on waiting list before
    if (previouslyWaiting && O('seatNumber').innerHTML != "") {
        O('casinoTableHeader').innerHTML = 'Casino Table ' + O('casinoTableId').innerHTML;
        if (S('userCallButton').display == 'none') {
            positionUserButtons(O('seatNumber').innerHTML);
            resizeUserElements();
        }
    }
    $("#nextCard").empty();
    $("#nextCard").append("<p>Next Card:</ p>");
    O('LookRiverCard-look').disabled = false;
    O('LookRiverCard-swap').disabled = true;
}
/********************************************************************************************/
// cheating events
function updateCheatingInfo(eventData, eventType) {
    // player is cheating by updating own hands
    if (eventType == 'CheatedHands') {
        cheatUpdateUserHands(eventData);
    }
    // player is cheating by knowing other players' cards value or suits
    else if (eventType == 'CheatedCards') {
        cheatUpdateOthersCardsMarks(eventData);
    }
    // player is cheating by knowing other players' cards value or suits
    else if (eventType == 'CheatedHidden') {
        cheatUpdateHiddenCards(eventData);
    }
    // player is cheating by knowing other players' cards value or suits
    else if (eventType == 'CheatedNext') {
        cheatUpdateNextCards(eventData);
    }
}
function updateCheatingEvent(eventData, eventType, eventDateTime) {
    if (eventType == 'ItemUnlock') {
        O(eventType + '-Act').disabled = false;
        unDimItem(eventType + '-Act');
		O('logFrame').innerHTML = eventDateTime + ' - ' + eventData.itemType + 'is now available. <br />' + O('logFrame').innerHTML;
    }
    else if (eventType == 'ItemLog') {
        O('logFrame').innerHTML = eventDateTime + ' - ' + eventData + '<br />' + O('logFrame').innerHTML;
    }
    else if (eventType == 'ItemEnd') {
        O(eventType + '-Act').disabled = true;
		dimItem(eventType + '-Act');
        O('logFrame').innerHTML = eventDateTime + ' - ' + eventData.itemType + 'has now ended. <br />' + O('logFrame').innerHTML;
    }
}
/**
 * Display player hands updated via cheating
 */
function cheatUpdateUserHands(playerHandDto) {
    var userPlayerId = O('userPlayerId').innerHTML;
    if (userPlayerId != playerHandDto.playerId) {
        return;
    }
    playerElement = getPlayerPositionTag(userPlayerId);
    var cardNumber = playerHandDto.playerCardNumber;
    O(playerElement + 'Card' + cardNumber).innerHTML = playerHandDto.cardName;
    // FIXME: the status return is null, so it's set in the UI but
    showPlayerCard(playerElement, cardNumber, playerHandDto.cardName);
}

/**
 * Display other players card value or suit.
 */
function cheatUpdateOthersCardsMarks(cardList) {
    for (var j=0, m=cardList.length; j<m; j++) {
        // no need to update the user
        if (cardList[j].playerId != O('userPlayerId').innerHTML) {
            var playerTag = getPlayerPositionTag(cardList[j].playerId);
            var cardMarkerTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'Marker';
            S(cardMarkerTag).display = 'block';
            /* reveal to player by setting the class attribute */
            if (cardList[j].cardName != null) {
                var cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'LargeMarker';
                O(cardMarkerTag).src = "../../../images/PokerCard_" + cardList[j].cardName + "_small.png";
                O(cardMarkerTag).setAttribute("class", 'cardLargeMarker ' + cardMarkerClassTag);
            }
            /* suit only information shows up on back of card, small size (1/3 of card) */
            else if (cardList[j].suit != null) {
                cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'SmallMarker';
                O(cardMarkerTag).src = "../../../images/PokerCard_" + cardList[j].suit + "_small.png";
                O(cardMarkerTag).setAttribute("class", 'cardSmallMarker ' + cardMarkerClassTag);
            }
        }
    }
}

/**

 * Display list of hidden cards the player maintains. Refresh the entire list.
 * Incremental updates later - more performant with very large number of users but far more complex
 */
function cheatUpdateHiddenCards(cardNameListDto) {
    if (cardNameListDto == null) {return;}
    $("#sleeve").empty();
    $("#sleeve").append("<p>Sleeve:</ p>");
    for (i=0; i<cardNameListDto.length; i++)
    {
        var card = cardNameListDto[i];
        $("#sleeve").append("<img class='cheatingCard' src='../../../images/PokerCard_" + card
            + "_small.png' title='" + card + "' alt='" + card + "' />")
    }
}

/**
 * Display the lsit of upcoming cards off the deck.
 * Incremental updates later - more performant with very large number of users but far more complex
 */
function cheatUpdateNextCards(cardNameListDto, altMessage) {
    $("#nextCard").empty();
    $("#nextCard").append("<p>Next Card:</ p>");
    for (i=0; i<cardNameListDto.length; i++)
    {
        var card = cardNameListDto[i];
        alt = altMessage == null ? card : altMessage;
        $("#nextCard").append("<img class='cheatingCard' src='../../../images/PokerCard_" + card
            + "_small.png' title='" + card + "' alt='" + alt + "' />")
    }
}

/********************************************************************************************/
/*
 * Callback for polling function. Sets the next poll.
 */
function getEventMessageCallback(event) {
    var resp = jQuery.parseJSON(event.body);
    var message = resp.eventData;
    if (resp.eventType.substr(0, 7) == "Cheated") {
        updateCheatingInfo(message, resp.eventType);
        return;
    }
    if (resp.eventType.substr(0, 4) == "Item") {
        updateCheatingEvent(message, resp.eventType, resp.eventDateTime);
        return;
    }
    if (resp.eventType == "UserJoined") {
        // array of player status dto's but only one value; could batch in the future
        addUserToTable(message[0]);
        return;
    }
    if (resp.eventType == "UserLeft" || resp.eventType == "SeatTaken") {
        // array of player status dto's but only one value; could batch in the future
        updateUserTableStatus(message[0]);
        return;
    }
    if (resp.eventType == 'GameStarted'){
        processGameStarted(message);
        return;
    }
    if (resp.eventType == 'SeatOffer') {
        O('seatNumber').innerHTML = message;
        displayCenterMessage("You are being offered a seat...");
        // overlay button to allow user select seat
        S('takeSeatButton').display = 'block';
        O('takeSeatButton').setAttribute("class", 'player' + message + 'Info');
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
        var previousPlayerTag = getPlayerPositionTag(O('nextPlayerId').innerHTML);
        resetLastPlayerDisplay(previousPlayerTag, skipped);
        processGameResult(message.gameResultDto);
    }
    // 2. else player made a move
    else {
        if (message.cardsToSend != null) {
            var communityCards = message.cardsToSend;
            // 2.1 Update community card
            for (var j=0, m=communityCards.length; j<m; j++) {
                showCommunityCard(communityCards[j].playerCardNumber-1, communityCards[j].cardName);
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

/********************************************************************************************/
/* player status and cards */
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Show the back of game cards for only the players who are actively in the game
 */
function initPlayersCardsDisplay(playerStatusDtos) {
    for(var i=0, l=playerStatusDtos.length; i<l; i++) {
        var seatNumber = playerStatusDtos[i].seatNumber;
        S('player' + seatNumber + 'Card1Image').display = 'block';
        S('player' + seatNumber + 'Card2Image').display = 'block';
        O('player' + seatNumber + 'Card1Image').src="../../../images/PokerCard_back_small.png";
        O('player' + seatNumber + 'Card2Image').src="../../../images/PokerCard_back_small.png";
    }
    hideCardMarkers();
}

function hideCardMarkers() {
    S('player0Card1Marker').display = 'none';
    S('player0Card2Marker').display = 'none';

    S('player1Card1Marker').display = 'none';
    S('player1Card2Marker').display = 'none';

    S('player2Card1Marker').display = 'none';
    S('player2Card2Marker').display = 'none';

    S('player3Card1Marker').display = 'none';
    S('player3Card2Marker').display = 'none';
	
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
    var playerStyle = O(playerTag + 'Info');
    var userTag = getPlayerPositionTag(O('userPlayerId').innerHTML);
    
    $('#' + playerTag + 'Info').removeClass('playerInfoNext')
    if (skipped) {
        playerStyle.setAttribute("class", "playerInfo playerInfoTimeOut");
    }
    else {
        if (playerTag == userTag) {
            playerStyle.setAttribute("class",  "playerInfo playerInfoUser");
        }
        else {
            playerStyle.setAttribute("class",  "playerInfo playerInfoNormal");
        }
    }
}

function focusNextPlayerDisplay(playerTag){
    var playerStyle = O(playerTag + 'Info');
    playerStyle.setAttribute("class", "playerInfo playerInfoNext");
}

/**~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * When a player wins, its player status box outline and colors change
 */
function updateWinnerDisplay(playerTag){        
    var playerStyle = O(playerTag + 'Info');
    playerStyle.setAttribute("class", "playerInfo playerInfoWinner");

    O(playerTag + 'Status').setAttribute("class", "playerStatusWinner");
/* O(playerTag + 'Status').innerHTML = "Won"; */

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
cardQueue = [];
currentCard = null;
constCardNormalWidth = 43;
constCardNormalHeight = 60;
/*
 * shows the specified community card
 */
function showCommunityCard(cardPosition, cardValue) {
    card = {
        position: cardPosition,
        image: O('communityCard' + cardPosition),
        playerId: -1,
        value: cardValue
    };
    card.image.src = "../../../images/" + "PokerCard_" + cardValue + "_small.png";
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
            var dealerPlayerTag = getPlayerPositionTag(O('currentDealerId').innerHTML);
            // get dealer button
            var dealerButtonStyle = $('#' + dealerPlayerTag + 'DealerButton');
            startX = getSize(dealerButtonStyle.css('left'));
            startY = getSize(dealerButtonStyle.css('top'));
            if (currentCard.playerId == -1 ) {
                endX = getSize($('#communityCard' + currentCard.position).css('left'));
                endY = getSize($('#communityCard' + currentCard.position).css('top'));
            }
            speedX = (endX - startX) / 30;
            speedY = (endY - startY) / 30;

            setTimeout(drawCard, 20);
        }
    }
    // still animating a card
    else {
        setTimeout(drawCard, 20);
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
}

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
        if (playerStatus.status == "Called") {
            O('player' + playerPosition + 'Status').innerHTML = 'Called ' + playerStatus.playAmount;
        }
        if (playerStatus.status == "Raised") {
            O('player' + playerPosition + 'Status').innerHTML = 'Raised ' + playerStatus.playAmount;
            
        }
    }
}

/*-------------------------------------------------------------------------------------------
 * Highlight next player who needs to make a move. If that player is the user, make the
 * call, fold, etc. buttons available.
 */
function processNextPlayer(nextMove, skipped) {
    // set next player id
    var previousPlayerId = O('nextPlayerId').innerHTML;
    var previousPlayerTag = getPlayerPositionTag(previousPlayerId);
    O('nextPlayerId').innerHTML = nextMove.nextPokerMoveDto.nextPlayerId;
    var nextPlayerTag = getPlayerPositionTag(O('nextPlayerId').innerHTML);
    resetLastPlayerDisplay(previousPlayerTag, skipped);
    focusNextPlayerDisplay(nextPlayerTag);

    if (O('userPlayerId').innerHTML == O('nextPlayerId').innerHTML) {
        O('userCallAmount').innerHTML = nextMove.nextPokerMoveDto.callAmount;
        O('userRaiseAmount').innerHTML = nextMove.nextPokerMoveDto.raiseAmount;
        O('userCallButton').value = 'Call ' + nextMove.nextPokerMoveDto.callAmount;
        O('userRaiseButton').value = 'Raise ' + nextMove.nextPokerMoveDto.raiseAmount;
        enableUserButtons(nextMove.nextPokerMoveDto.checkAmount);
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
    for(var i=0, l=gameResultDto.playerHandDtos.length; i<l; i++) {
        var playerElement = getPlayerPositionTag(gameResultDto.playerHandDtos[i].playerId);
        showPlayerCard(playerElement, 1, gameResultDto.playerHandDtos[i].pokerCard1Dto.cardName)
        showPlayerCard(playerElement, 2, gameResultDto.playerHandDtos[i].pokerCard2Dto.cardName);
        if (gameResultDto.playerHandDtos[i].playerId == gameResultDto.winningPlayerId) {
            O(playerElement + 'Status').innerHTML = 'Won - ' + gameResultDto.playerHandDtos[i].pokerHandType;
        }
        else {
            O(playerElement + 'Status').innerHTML = 'Lost - ' + gameResultDto.playerHandDtos[i].pokerHandType;
        }
    }
    // hide markers
    hideCardMarkers();
    // update stakes
    for (var j=0, m=gameResultDto.playerStatusDtos.length; j<m; j++) {
        playerElement = getPlayerPositionTag(gameResultDto.playerStatusDtos[j].playerId);
        O(playerElement + 'Stake').innerHTML = gameResultDto.playerStatusDtos[j].stake;
    }
    // set winner
    var winnerElement = getPlayerPositionTag(gameResultDto.winningPlayerId);
    // FIXME: find previous
    updateWinnerDisplay(winnerElement);
    O('startGameButton').disabled = false;
    unDimItem('startGameButton');
    
    disableInstanceItems(false);
}

/*-------------------------------------------------------------------------------------------
 *
 */
function processFoldOrLeft(playerId, status) {
    var playerTag = getPlayerPositionTag(playerId);
    S(playerTag + 'Card1Image').display = 'none';
    S(playerTag + 'Card2Image').display = 'none';
	
}

/*-------------------------------------------------------------------------------------------
 * Update the player statuses data on the browser; used both when there is an ongoing game or
 * when a user joins a table.
 */
function setupPlayerStatuses(playerStatusDtos) {
    var playerCount = playerStatusDtos.length;
    for (var i=0; i<playerCount; i++) {
        if (playerStatusDtos[i].seatNumber != null) {
            processStatusChange(playerStatusDtos[i]);
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
 * 4) polled message returns new game
 */
function setupTable(gameInstanceSetupDto) {
    O('gameInstanceId').innerHTML = gameInstanceSetupDto.gameInstanceId;

    // update everyone's statuses
    initPlayersCardsDisplay(gameInstanceSetupDto.playerStatusDtos);
    setupPlayerStatuses(gameInstanceSetupDto.playerStatusDtos);

    // put blind bets
    for (var k=0, n=gameInstanceSetupDto.blindBets.length; k<n; k++) {
        var blindPosition = getPlayerPositionTag(gameInstanceSetupDto.blindBets[k].playerId);
        O(blindPosition + 'Status').innerHTML = 'Blind Bet ' + gameInstanceSetupDto.blindBets[k].betSize;
    }

    // get the user's hands'
    var playerElement;
    playerElement = getPlayerPositionTag(O('userPlayerId').innerHTML);
    if (gameInstanceSetupDto.userPlayerHandDto != null) {
        // null if user is in waiting list
        O(playerElement + 'Card1').innerHTML = gameInstanceSetupDto.userPlayerHandDto.pokerCard1Dto.cardName;
        O(playerElement + 'Card2').innerHTML = gameInstanceSetupDto.userPlayerHandDto.pokerCard2Dto.cardName;
        showPlayerCard(playerElement, 1, gameInstanceSetupDto.userPlayerHandDto.pokerCard1Dto.cardName);
        showPlayerCard(playerElement, 2, gameInstanceSetupDto.userPlayerHandDto.pokerCard2Dto.cardName);

        // enable items
        disableInstanceItems(true);
    }
    // next player
    O('nextPlayerId').innerHTML = gameInstanceSetupDto.firstPlayerId;
    // update display with current user and allowed action
    var nextPlayerTag = getPlayerPositionTag(O('nextPlayerId').innerHTML);
    focusNextPlayerDisplay(nextPlayerTag);

    // dealer designation
    var dealerPlayerTag = getPlayerPositionTag(gameInstanceSetupDto.dealerPlayerId);
    //O('dealerPlayerId').innerHTML = resp.dealerPlayerId;
    O('currentDealerId').innerHTML = gameInstanceSetupDto.dealerPlayerId;
    // show dealer button

    S(dealerPlayerTag + 'DealerButton').display = 'block';

    if (O('userPlayerId').innerHTML == gameInstanceSetupDto.firstPlayerId){
        // call and raise amounts (only for first move raise is 2*blind
        var bigBlind = gameInstanceSetupDto.blindBets[1].betSize;
        O('userCallButton').value = 'Call ' + bigBlind;
        O('userCallAmount').innerHTML = bigBlind;
        O('userRaiseButton').value = 'Raise ' + 2*bigBlind;
        O('userRaiseAmount').innerHTML = 2*bigBlind;
        // check is disabled when a game first starts
        enableUserButtons(null);
    }

}

function disableInstanceItems(boolValue) {
	if (boolValue) {
			dimItem('AcePusher-Act');
			dimItem('HeartMarker-Act');
			dimItem('ClubMarker-Act');
			dimItem('DiamondMarker-Act');
			dimItem('LookRiverCard-Act');
			dimItem('PokerPeeker-Act');
			dimItem('RiverbendRedo-Act');
	}
	else {
		unDimItem('AcePusher-Act');
		unDimItem('HeartMarker-Act');
		unDimItem('ClubMarker-Act');
		unDimItem('DiamondMarker-Act');
		unDimItem('LookRiverCard-Act');
		unDimItem('PokerPeeker-Act');
		unDimItem('RiverbendRedo-Act');
	}
    O('AcePusher-Act').disabled = boolValue;
    O('HeartMarker-Act').disabled = boolValue;
    O('ClubMarker-Act').disabled = boolValue;
    O('DiamondMarker-Act').disabled = boolValue;
    O('LookRiverCard-Act').disabled = boolValue;
    O('PokerPeeker-Act').disabled = boolValue;
    /* session level
    O('TableTucker-Act').disabled = false;
    O('SocialSpotter-Act').disabled = false;
    O('SnakeOilMarker-Act').disabled = false;
    O('AntiOilMarker-Act').disabled = false;
    O('FaceMelter-Act').disabled = false;
    */
    O('RiverbendRedo-Act').disabled = boolValue;

}
/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Show ongoing game after user joins a table
 */
function showGameInProgress(gameStatusDto) {
    O('gameInstanceId').innerHTML = gameStatusDto.gameInstanceId;

    // update everyone's statuses
    initPlayersCardsDisplay(gameStatusDto.playerStatusDtos);

    if (gameStatusDto.communityCards != null ) {
        var m=gameStatusDto.communityCards.length;
        for (var j=0; j<m; j++) {
            showCommunityCard(gameStatusDto.communityCards[j].playerCardNumber-1, gameStatusDto.communityCards[j].cardName);
        }
    }
    if (gameStatusDto.gameResultDto != null) {
        processGameResult(gameStatusDto.gameResultDto);
    }
    // the user may have a seat if this operationi s called because re-opened the browser
    // in the middle of the game
    if (gameStatusDto.userPlayerHandDto != null) {
        var playerElement
        playerElement = getPlayerPositionTag(O('userPlayerId').innerHTML);
        O(playerElement + 'Card1').innerHTML = gameStatusDto.userPlayerHandDto.pokerCard1Dto.cardName;
        O(playerElement + 'Card2').innerHTML = gameStatusDto.userPlayerHandDto.pokerCard2Dto.cardName;
        // FIXME: the status return is null, so it's set in the UI but
        // should come from the back-end
        O(playerElement + 'Status').innerHTML = 'Ready';
        showPlayerCard(playerElement, 1, gameStatusDto.userPlayerHandDto.pokerCard1Dto.cardName);
        showPlayerCard(playerElement, 2, gameStatusDto.userPlayerHandDto.pokerCard2Dto.cardName);

        // game has not started if player hands are null
        // next player
        O('nextPlayerId').innerHTML = gameStatusDto.firstPlayerId;
        // update display with current user and allowed action
        var nextPlayerTag = getPlayerPositionTag(O('nextPlayerId').innerHTML);
        focusNextPlayerDisplay(nextPlayerTag);
    }
    
    // dealer designation
    var dealerPlayerTag = getPlayerPositionTag(gameStatusDto.dealerPlayerId);
    //O('dealerPlayerId').innerHTML = resp.dealerPlayerId;
    O('currentDealerId').innerHTML = gameStatusDto.dealerPlayerId;
    // show dealer button
    S(dealerPlayerTag + 'DealerButton').display = 'block';
    
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

    O('userRaiseButton').setAttribute("class", "userButton " + userTag + "Raise");
    O('userCallButton').setAttribute("class", "userButton " + userTag + "Call");
    O('userCheckButton').setAttribute("class", "userButton " + userTag + "Check");
    O('userFoldButton').setAttribute("class", "userButton " + userTag + "Fold");
    
    S('userRaiseButton').display = 'block';
    S('userCallButton').display = 'block';
    S('userCheckButton').display = 'block';
    S('userFoldButton').display = 'block';
}

/** ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * Size playe cards to be bigger, which requires positioning changes
 * - all cards: second card moved (right and left) by card width difference
 * - bottom cards: move both cards up by card height difference
 */
function resizeUserElements() {
    var uTag = getPlayerPositionTag(O('userPlayerId').innerHTML);
    
    // add border on status
    O(uTag + 'Info').setAttribute("class", "playerInfo playerInfoUser");
    O(uTag + 'Card1Image').setAttribute("class", "userCard " + uTag + "UserCard1");
    O(uTag + 'Card2Image').setAttribute("class", "userCard " + uTag + "UserCard2");
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
    client = Stomp.client(constServer);

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
    if (userTag != null) {
        O(userTag + 'Info').setAttribute("class",  'playerInfo playerInfoUser');
    }
    // display status
    O('nextCommunityCardPosition').value = 0;

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


//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Given a text message, display it on the center
 * FIXME: to be a popup or a rasterized image, HTML text not centering properly
 */
function displayCenterMessage(msg) {
    /* S("centerMessageId").top = bH/2 - 30 + 'px';
    S("centerMessageId").left = bW/2 - 120 + 'px'; */
    O("centerMessageId").innerHTML = msg;
    S("centerMessageId").display = 'block';
}

/********************************************************************************************/
/* display and positioning */
function logout() {
    var obj = {
        userPlayerId: $.cookies.get("userPlayerId")
    };
    WSClient.call("logout",
        obj,
        null);

    $.cookies.del("userPlayerId");
    window.location = 'SeedySaloon.php';
}

/*^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * processing: set positions for all HTML elements dynamically in case of sizing
 * dynamically position player status boxes, which are shown
 * set position for all cards and chips but keep hidden
 * TODO: may have more than 4 players
 * 1) all the player's items such as status box, messages, dealer button and cards
 * 2) community cards
 */
window.onload = function() {
    // check if there is a user id; if not redirect to the saloon
    var userPlayerId = $.cookies.get("userPlayerId");
    if (userPlayerId == null) {
        // FIXME: seedy salon is hard coded, there should be another login page.
        window.location = 'SeedySaloon.php';
    }
    O('loginInfo').innerHTML = "You are logged in as " + $.cookies.get("playerName");
    $('#dialog-modal').dialog('open');

    // load sleeve. This is the only cheating option that is activated before going to a table
    cheatLoadSleeve();
	
    var widthToHeight = 4 / 3;
    var newWidth = window.innerWidth;
    var newHeight = window.innerHeight;
    
    animateCard();

    // some cheating items are disabled because of dependencies
    O('LookRiverCard-swap').disabled = true;
    O('LookRiverCard-look').disabled = false;

    disableInstanceItems(false);
    
    O('TableTucker-Act').disabled = false;
    O('SocialSpotter-Act').disabled = false;
    O('SnakeOilMarker-Act').disabled = false;
    O('AntiOilMarker-Act').disabled = false;
    O('FaceMelter-Act').disabled = false;

}

