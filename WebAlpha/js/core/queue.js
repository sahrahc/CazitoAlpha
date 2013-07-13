constServer = "http://localhost:55674/stomp";

window.onbeforeunload = function() {
    stopQueueing();
};
window.onunload = function() {
    stopQueueing();
};
window.onreset = function() {
    stopQueueing();
};
function startQueueing() {
    // websockets
    Stomp.WebSocketClass = SockJS;
    client = Stomp.client(constServer);

    // FIXME: log error
    error_callback = function(error) {
        alert(error);
    };

    var queue = '/queue/p' + $.cookies.get('userPlayerId');
    connect_callback = function(data) {
        client.subscribe(queue, getEventMessageCallback);
    };
    // format: client.connect(login, passcode, connect_callback, error_callback);
    client.connect('guest', 'guest', connect_callback, error_callback, '/');
}

function stopQueueing() {
    if (client !== undefined && client !== null) {
        client.disconnect();
    }
}


function sendRequest(eventType, eventData) {
    var obj = {
        eventType: '\'' + eventType + '\'',
        gameSessionId: O('gameSessionId').innerHTML,
        requestingPlayerId: $.cookies.get('userPlayerId')
    };
    if (O('gameInstanceId').innerHTML !== null) {
        obj.gameInstanceId = O('gameInstanceId').innerHTML;
    }
    if (eventData !== null) {
        obj.eventData = eventData;
    }
    client.send('/queue/s' + O('gameSessionId').innerHTML, {}, obj);
}

function sendChat(recipientPlayerId) {
    var obj = {
        senderPlayerId: $.cookies.get('userPlayerId'),
        recipientPlayerId: recipientPlayerId
    };
    if (eventData !== null) {
        obj.eventData = eventData;
    }
    client.send('/queue/i' + O('gameSessionId').innerHTML, {}, obj);

}

/********************************************************************************************/
/*
 * Callback for polling function. Sets the next poll.
 */
function getEventMessageCallback(event) {
    var resp = jQuery.parseJSON(event.body);
    var message = resp.eventData;

    switch (resp.EventType) {
        // 1. synchronous, triggered by this or another user action
        case GAME_STARTED:
            showGameStatus(message); // message is GameStatusDto
            resetCheatingOnGameStart();
            return;
        case PLAYER_MOVE:
            showGameAfterTurn(message); // message is GameStatusDto;'
            if (gameStatusDto.gameStatus === GAME_ENDED) {
                // hide markers when displaying the result
                hideCardMarkers();
            }
            return;
        case WAIT_LIST_CHANGE:
            updateWaitingListMessage(message); //response is int
            return;
        case USER_LEFT:
            stopQueueing();
            // FIXME
            window.location = resp.page + '.php';
        case SEAT_TAKEN:
            updateUserSeatTaken(message[0]); // array of player status dto's with only one value
            return;
        case SEAT_OFFER:
            offerSeat(message); // response is int
            return;
        case CHEATED_HANDS:
            cheatUpdateUserHands(message); // playerHandDto
            return;
        case CHEATED_CARDS:
            cheatUpdateOthersCardsMarks(message);
            return;
        case CHEATED_HIDDEN:
            cheatUpdateHiddenCards(message);
            return;
        case CHEATED_NEXT:
            cheatUpdateNextCards(message);
            return;
        case ITEM_LOCK:
        // locking messages not used, both FE and BE tracking to avoid additional
        // messaging
        case ITEM_UNLOCK:
        case ITEM_LOG:
        case ITEM_END:
            updateCheatingEvent(message, resp.eventType, resp.eventDateTime);
            return;
    }
}
