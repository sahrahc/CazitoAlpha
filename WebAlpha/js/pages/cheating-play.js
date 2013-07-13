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
    if ($.cookies.get("userPlayerId") === null) {
        // FIXME: seedy salon is hard coded, there should be another login page.
        window.location = 'Home.php';
    }
    O('hello').innerHTML = "Hello " + $.cookies.get("playerName");
    //$('#dialog-modal').dialog('open');

    // load sleeve. This is the only cheating option that is activated before going to a table
    cheatLoadSleeve();

    /*
     var widthToHeight = 4 / 3;
     var newWidth = window.innerWidth;
     var newHeight = window.innerHeight;
     */
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

};
/********************************************************************************************/
function cheatAcePusher(cardNumber) {
    var eventData = {
        itemType: ACE_PUSHER,
        playerCardNumber: cardNumber
    };
    sendRequest(CHEAT, eventData);
}

function cheat(itemType) {
    if (itemType === RIVER_SHUFFLER || itemType === RIVER_SHUFFLER_USE) {
        var instanceId = O('gameInstanceId').innerHTML;
        if (instanceId === null) {
            alert('There is no active game');
            return;
        }
    }
 
    var eventData = {
        itemType: itemType
    };
    sendRequest(CHEAT, eventData);
    
    // if sendRequest successful, disable. Lock out end message to be received
    // lock out should start on successful use of item, so message will need
    // to be sent.
    switch (itemType) {
        case RIVER_SHUFFLER:
            O('LookRiverCard-swap').disabled = false;
            O('LookRiverCard-look').disabled = true;
        case RIVER_SHUFFLER_USE:
            O('LookRiverCard-swap').disabled = true;
            O('LookRiverCard-look').disabled = false;
    };
}
/*
function cheatLoadSleeve() {
    var obj = {
        userPlayerId: $.cookies.get("userPlayerId")
    };

    WSClient.call(LOAD_CARD_ON_SLEEVE, //"cheatLoadSleeve",
            obj,
            cheatUpdateHiddenCards);
}
*/
/********************************************************************************************/
function cheatSuitMarkerCallback(suit, cardList) {
    /* FIXME: same as diamond and heart marker reuse */
    for (var j = 0, m = cardList.length; j < m; j++) {
        if (cardList[j].suit !== null && cardList[j].playerId !== $.cookies.get("userPlayerId")) {
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

/********************************************************************************************/
function updateCheatingEvent(eventData, eventType, eventDateTime) {
    if (eventType === ITEM_UNLOCK) {
        O(eventType + '-Act').disabled = false;
        unDimItem(eventType + '-Act');
        O('logFrame').innerHTML = eventDateTime + ' - ' + eventData.itemType + 'is now available. <br />' + O('logFrame').innerHTML;
    }
    else if (eventType === ITEM_LOG) {
        O('logFrame').innerHTML = eventDateTime + ' - ' + eventData + '<br />' + O('logFrame').innerHTML;
    }
    else if (eventType === ITEM_END) {
        O(eventType + '-Act').disabled = true;
        dimItem(eventType + '-Act');
        O('logFrame').innerHTML = eventDateTime + ' - ' + eventData.itemType + 'has now ended. <br />' + O('logFrame').innerHTML;
    }
}
/**
 * Display player hands updated via cheating
 * @param {playerHandDto} playerHandDto
 */
function cheatUpdateUserHands(playerHandDto) {
    if ($.cookies.get("userPlayerId") !== playerHandDto.playerId) {
        return;
    }
    var playerElement = getPlayerPositionTag($.cookies.get("userPlayerId"));
    var cardNumber = playerHandDto.playerCardNumber;
    O(playerElement + 'Card' + cardNumber).innerHTML = playerHandDto.cardName;
    // FIXME: the status return is null, so it's set in the UI but
    showPlayerCard(playerElement, cardNumber, playerHandDto.cardName);
}

/**
 * Display other players card value or suit.
 * @param {string[]} cardList
 */
function cheatUpdateOthersCardsMarks(cardList) {
    for (var j = 0, m = cardList.length; j < m; j++) {
        // no need to update the user
        if (cardList[j].playerId !== $.cookies.get("userPlayerId")) {
            var playerTag = getPlayerPositionTag(cardList[j].playerId);
            var cardMarkerTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'Marker';
            S(cardMarkerTag).display = 'block';
            /* reveal to player by setting the class attribute */
            if (cardList[j].cardName !== null) {
                var cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'LargeMarker';
                O(cardMarkerTag).src = "../../../images/PokerCard_" + cardList[j].cardName + "_small.png";
                O(cardMarkerTag).setAttribute("class", 'cardLargeMarker ' + cardMarkerClassTag);
            }
            /* suit only information shows up on back of card, small size (1/3 of card) */
            else if (cardList[j].suit !== null) {
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
 * @param {array[]} cardNameCodes
 */
function cheatUpdateHiddenCards(cardNameCodes) {
    if (cardNameCodes === null) {
        return;
    }
    $("#sleeve").empty();
    $("#sleeve").append("<p>Sleeve:</ p>");
    for (var i = 0; i < cardNameCodes.length; i++)
    {
        var card = cardNameCodes[i];
        $("#sleeve").append("<img class='cheatingCard' src='../../../images/PokerCard_" + card
                + "_small.png' title='" + card + "' alt='" + card + "' />");
    }
}

/**
 * Display the lsit of upcoming cards off the deck.
 * Incremental updates later - more performant with very large number of users but far more complex
 * @param {string[]} cardNameCodes list of two-character card codes
 * @param {string} altMessage
 * @returns {none}
 */
function cheatUpdateNextCards(cardNameCodes, altMessage) {
    $("#nextCard").empty();
    $("#nextCard").append("<p>Next Card:</ p>");
    for (var i = 0; i < cardNameCodes.length; i++)
    {
        var card = cardNameCodes[i];
        var alt = altMessage === null ? card : altMessage;
        $("#nextCard").append("<img class='cheatingCard' src='../../../images/PokerCard_" + card
                + "_small.png' title='" + card + "' alt='" + alt + "' />");
    }
}

