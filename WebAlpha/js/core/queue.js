//constServer = "http://localhost:55674/stomp";
constServer = "http://192.168.1.70:55674/stomp";
var client;

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

    var queue = '/amq/queue/p' + $.cookies.get("userPlayerId");
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
    var gameSessionId = O('gameSessionId').innerHTML;
    var obj = {
	eventType: eventType,
	gameSessionId: gameSessionId,
	requestingPlayerId: $.cookies.get("userPlayerId")
    };
    if (O('gameInstanceId').innerHTML !== null) {
	obj.gameInstanceId = O('gameInstanceId').innerHTML;
    }
    if (eventData !== null) {
	obj.eventData = eventData;
    }
    client.send('/amq/queue/s' + gameSessionId, {}, JSON.stringify(obj));
}

function sendChat(recipientPlayerId) {
    var obj = {
	senderPlayerId: $.cookies.get("userPlayerId"),
	recipientPlayerId: recipientPlayerId
    };
    if (eventData !== null) {
	obj.eventData = eventData;
    }
    client.send('/amq/queue/i' + O('gameSessionId').innerHTML, {}, JSON.stringify(obj));

}

/********************************************************************************************/
/*
 * Callback for polling function. Sets the next poll.
 */
function getEventMessageCallback(event) {
    var resp = jQuery.parseJSON(event.body);
    var message = resp.eventData;

    // ignore stale messages
    if (+resp.gameSessionId !== +O('gameSessionId').innerHTML) {
	return;
    }
    switch (resp.eventType) {
	// 1. synchronous, triggered by this or another user action
	case GAME_STARTED:
	    O('nextCommunityCardPosition').innerHTML = 0;
	    // reset players who left
	    for (var i = 0; i < 4; i++) {
		O('player' + i + 'Name').innerHTML = "Empty Seat";
		O('player' + i + 'Stake').innerHTML = "";
		O('player' + i + 'Status').innerHTML = "";
		O('player' + i + 'Image').src = "../../../images/Avatar_user0.jpeg";
	    }
	    var gameStatusDto = message;
	    for (var i = 0; i < gameStatusDto.playerStatusDtos.length; i++) {
		updatePlayerIdentity(gameStatusDto.playerStatusDtos[i]);
	    }
	    showGameStatus(message); // message is GameStatusDto
	    if ($.cookies.get("vanilla-play") === 0) {
		resetCheatingOnGameStart();
	    }
	    break;
	case CHANGE_NEXT_TURN:
	    showGameAfterTurn(message); // message is GameStatusDto;'
	    break;
	case WAIT_LIST_CHANGE:
	    updateWaitlistCount(message); //response is int
	    break;
	case USER_LEFT:
	    //showGameStatus(message); // message is GameStatusDto

	    //stopQueueing();
	    //window.location.replace("Home.php");
	    for (var i = 0, l = message.length; i < l; i++) {
		updatePlayerStatus(message[i]);
	    }
	    break;
	case SEAT_TAKEN:
	    updateUserSeatTaken(message[0]); // array of player status dto's with only one value
	    break;
	case SEAT_OFFER:
	    offerSeat(message); // response is int
	    break;
	case USER_EJECTED:
	    alert(message);
	    window.location.replace("Home.php");
	case CHEATED:
	    for (var i = 0; i < message.length; i++) {
		switch (message[i].dtoType) {
		    case CHEATED_HANDS:
			cheatUpdateUserHands(message[i].dto, message[i].itemType); // playerHandDto
			break;
		    case CHEATED_CARDS:
			cheatUpdateOthersCardsMarks(message[i].dto);
			break;
		    case CHEATED_HIDDEN:
			if (message[i].itemType === USE_CARD_ON_SLEEVE) {
			    cheatUpdateHiddenCards($("#sleeveCards"), message[i].dto);
			}
			else if (message[i].itemType === TUCKER_TABLE_SLIDE_UNDER ||
				message[i].itemType === TUCKER_TABLE_EXCHANGE ||
				message[i].itemType === TUCKER_TABLE_SLIDE_OUT) {
			    cheatUpdateHiddenCards($("#grooveCards"), message[i].dto);

			}
			break;
		    case CHEATED_NEXT:
			if (message[i].itemType === RIVER_SHUFFLER) {
			    cheatShowRiverCard(message[i].dto);
			}
			break;
		    case ITEM_LOCK:
			// locking messages not used, both FE and BE tracking to avoid additional
			// messaging
		    case ITEM_UNLOCK:
		    case ITEM_LOG:
		    case ITEM_END:
			updateCheatingEvent(message[i].itemType, message[i].dto, message[i].dtoType);
			break;
		}
	    }
	    break;
    }
}
