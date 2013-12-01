/********************************************************************************************/
/**
 * Reset cheating items on game start
 * @returns {undefined}
 */
function resetCheatingOnGameStart() {
    // cheating items - does not apply for cheating
    S(RIVER_SHUFFLER + '-image').display = 'inline';
    S(RIVER_SHUFFLER_USE + '-div').display = 'none';

    cazito.setGlobalVar("nextRiverAction", 'Look');

    if (cazito.getGlobalVar(RIVER_SHUFFLER) === 'Disabled') {
	greyItem(RIVER_SHUFFLER);
    }
    else if (cazito.getGlobalVar(RIVER_SHUFFLER) === 'Enabled') {
    //else {
	unGreyItem(RIVER_SHUFFLER);
	O(RIVER_SHUFFLER + '-image').onclick = function() {
	    cheat({itemType: RIVER_SHUFFLER});
	    hideDescription($('#' + RIVER_SHUFFLER));
	};
    }

    initCatalog();
}

function resetCheatingOnSessionStart() {
    $("#grooveCards").empty();
    $("#grooveCards").append("<p>Table Groove:</p>");
}

/********************************************************************************************/
/* make the following items drag and drop:
 * AcePusher - user hands
 * PokerPeeker - on opponent
 * AntiOilMarker - on opponent
 * @returns {undefined}
 */
function initCatalog() {
    // images are draggable
    $("#AcePusher img").draggable({
	helper: 'clone',
	appendTo: 'body',
	cursor: 'move',
	stack: '#AcePusher img'
    });
    $("#PokerPeeker img").draggable({
	helper: 'clone',
	appendTo: 'body',
	cursor: 'move',
	stack: '#PokerPeeker img'
    });
    $("#AntiOilMarker img").draggable({
	helper: 'clone',
	appendTo: 'body',
	cursor: 'move',
	stack: '#AntiOilMarker img'
    });
    /* droppables on Ace Pusher defined in displayUserHands */
    for (var i = 0; i < 4; i++) {
	$("#player" + i + "Info").data('playerSeatNumber', i).droppable({
	    accept: '#AntiOilMarker img', //, #grooveCards",
	    hoverClass: 'hovered',
	    drop: cheatOpponent
	});
    }
}

/**
 * Toggle if cheating enabled/disabled
 * @param {type} boolValue
 * @returns {undefined}
 function disableCheatingItems(boolValue) {
 if (boolValue) {
 dimItem('AcePusher-Act');
 dimItem('HeartMarker-Act');
 dimItem('ClubMarker-Act');
 dimItem('DiamondMarker-Act');
 dimItem('LookRiverCard-Act');
 dimItem('PokerPeeker-Act');
 }
 else {
 unDimItem('AcePusher-Act');
 unDimItem('HeartMarker-Act');
 unDimItem('ClubMarker-Act');
 unDimItem('DiamondMarker-Act');
 unDimItem('LookRiverCard-Act');
 unDimItem('PokerPeeker-Act');
 }
 // session level
 O('AcePusher-Act').disabled = boolValue;
 O('HeartMarker-Act').disabled = boolValue;
 O('ClubMarker-Act').disabled = boolValue;
 O('DiamondMarker-Act').disabled = boolValue;
 O('LookRiverCard-Act').disabled = boolValue;
 O('PokerPeeker-Act').disabled = boolValue;
 }
 */

/**
 * Show the back of game cards for only the players who are actively in the game
 */
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
 * Display list of hidden cards the player maintains. Refresh the entire list.
 * Hidden cards may show up on pages other than cheating play.
 * Incremental updates later - more performant with very large number of users but far more complex
 * @param {array[]} cardNameCodes
 */
function cheatUpdateHiddenCards(objSleeve, cards) {
    var objName = $(objSleeve).attr('id');
    $(objSleeve).empty();
    if (cards === null) {
	return;
    }
    if (objName === "sleeveCards") {
	$(objSleeve).append("<p>Sleeve:</ p>");
    }
    else if (objName === "grooveCards") {
	$(objSleeve).append("<p>Table Groove:</ p>");
    }
    // http://www.elated.com/articles/drag-and-drop-with-jquery-your-essential-guide/
    for (var i = 0; i < cards.length; i++) {
	$("<img class='cheatingCard' src='../../../images/PokerCard_" + cards[i]
		+ "_small.png' title='" + cards[i] + "' alt='" + cards[i] + "' />")
		.attr('id', objName + cards[i])
		.data('hiddenCardNumber', i).appendTo('#' + objName).draggable({
	    //containment: '#poker-table',
	    helper: 'clone',
	    appendTo: 'body',
	    cursor: 'move',
	    stack: '.cheatingCard, #PokerPeeker img, #AcePusher img'
		    //snap: '#poker-table',
		    //revert: true
	});
    }
    // on cookie too, for the CheatingPlay page
    // the sleeve/table groove is only valid for the session, if user clears browsing history
    // the sleeve/table groove is wiped out.
    if (objName === "sleeve") {
	$.cookies.set("sleeveCards", cards);
    }
    else {
	$.cookies.set(objName, cards);
    }
}
/**
 * Display player hands updated via cheating
 * @param {playerHandDto} playerHandDto
 */
function cheatUpdateUserHands(playerHandDto, itemType) {
    if ($.cookies.get("userPlayerId") !== playerHandDto.playerId) {
	return;
    }
    var playerElement = getPlayerPositionTag($.cookies.get("userPlayerId"));
    var cardNumber = playerHandDto.playerCardNumber;
    O(playerElement + 'Card' + cardNumber).innerHTML = playerHandDto.cardCode;
    // FIXME: the status return is null, so it's set in the UI but
    showPlayerCard(playerElement, cardNumber, playerHandDto.cardCode);

    // removed cloned card
    if (itemType === USE_CARD_ON_SLEEVE) {
	$('#sleeveCards' + playerHandDto.cardCode + 'Cloned').remove();
    }
    else if (itemType === TUCKER_TABLE_EXCHANGE) {
	$('#grooveCards' + playerHandDto.cardCode + 'Cloned').remove();
    }
}

/**
 * Display other players card value or suit.
 * @param {string[]} cardList
 */
function cheatUpdateOthersCardsMarks(cardList) {
    for (var j = 0, m = cardList.length; j < m; j++) {
	// no need to update the user
	if (+cardList[j].playerId !== $.cookies.get("userPlayerId")) {
	    var playerTag = getPlayerPositionTag(cardList[j].playerId);
	    var cardMarkerTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'Marker';
	    /* reveal to player by setting the class attribute */
	    if (cardList[j].cardCode !== null) {
		var cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'LargeMarker';
		O(cardMarkerTag).src = "../../../images/PokerCard_" + cardList[j].cardCode + "_small.png";
		O(cardMarkerTag).setAttribute("class", 'cardLargeMarker ' + cardMarkerClassTag);
		S(cardMarkerTag).display = 'block';
	    }
	    /* suit only information shows up on back of card, small size (1/3 of card) */
	    else if (cardList[j].suit !== null) {
		cardMarkerClassTag = playerTag + 'Card' + cardList[j].playerCardNumber + 'SmallMarker';
		O(cardMarkerTag).src = "../../../images/PokerCard_" + cardList[j].suit + "_small.png";
		O(cardMarkerTag).setAttribute("class", 'cardSmallMarker ' + cardMarkerClassTag);
		S(cardMarkerTag).display = 'block';
	    }
	}
    }
}

/**
 * Display the river card on the catalog itself.
 * Incremental updates later - more performant with very large number of users but far more complex
 * @param {string[]} cardNameCodes list of two-character card codes
 * @param {string} altMessage
 * @returns {none}
 */
function cheatShowRiverCard(cardNameCodes) {
    S(RIVER_SHUFFLER + '-image').display = 'none';

    // argument is a list of one element
    var card = cardNameCodes[0];
    S(RIVER_SHUFFLER_USE + "-div").display = 'block';
    O(RIVER_SHUFFLER_USE + "-image").src = "../../../images/PokerCard_" + card + "_small.png";
    O(RIVER_SHUFFLER_USE + "-image").alt = cardNameCodes[0];

    cazito.setGlobalVar("nextRiverAction", 'Swap');
}

function cloneItem(itemType, clonedClass, item, place) {
    var clonedId = itemType + 'Cloned';
    var originalClasses = $(item).attr('class');
    $(item).clone().attr("id", clonedId).appendTo("body");
    $("#" + clonedId).addClass(clonedClass);
    $("#" + clonedId).removeClass(originalClasses);
    $("#" + clonedId).position({of: $(place), my: 'left top', at: 'left top'});
    // commenting out next line to keep the original image until successfully applied
    //item.draggable('option', 'revert', false);
}

function cheatOpponent(event, ui) {
    var playerPosition = $(this).data('playerSeatNumber');
    var playerId = O('player' + playerPosition + 'Id').innerHTML;
    var itemType = ui.draggable.parent().attr('id');
    if (itemType === "PokerPeeker" || itemType === "AntiOilMarker") {
	cloneItem(itemType, "clonedCheatingItem", ui.draggable, this);
	cheat({itemType: itemType, otherPlayerId: playerId});
    }
}

function cheatRevealCard(event, ui) {
    var playerCardNumber = $(this).data('playerCardNumberAndId').substr(0, 1);
    var otherPlayerId = $(this).data('playerCardNumberAndId').substr(1); //rest of string
    // style if correctly dropped first
    if (ui.draggable.parent().attr('id') === POKER_PEEKER) {
	cloneItem(ui.draggable.parent().attr('id'), "clonedCheatingItem", ui.draggable, this);
	cheat({itemType: POKER_PEEKER, playerCardNumber: playerCardNumber, otherPlayerId: otherPlayerId});
    }
}
function cheatChangeHand(event, ui) {
    var playerCardNumber = $(this).data('playerCardNumber');
    // style if correctly dropped first
    if (ui.draggable.parent().attr('id') === "AcePusher") {
	cloneItem(ui.draggable.parent().attr('id'), "clonedCheatingItem", ui.draggable, this);
	cheat({itemType: 'AcePusher', playerCardNumber: playerCardNumber});
    }
    else if (ui.draggable.attr('class').match('cheatingCard') !== null) {
	cloneItem(ui.draggable.attr("id"), "clonedCheatingCard", ui.draggable, this);
	cheatUseHiddenCard(event, ui, playerCardNumber);
    }
}
function cheatUseHiddenCard(event, ui, playerCardNumber) {
    var hiddenCardNumber = ui.draggable.data('hiddenCardNumber');
    /* only cheating cards processed
     if (ui.draggable.attr('class').match('cheatingCard') === null) {
     return;
     } */
    //ui.draggable.draggable('disable');
    //$(this).droppable( 'disable' );

    var itemType;
    if (ui.draggable.parent().attr('id') === "sleeveCards") {
	itemType = USE_CARD_ON_SLEEVE;
    }
    else if (ui.draggable.parent().attr('id') === "grooveCards") {
	itemType = TUCKER_TABLE_EXCHANGE;
    }

    // styling use, sound effect would be nice. Actual changes to source and hand
    // happen if request processed successfully.
    cheat({itemType: itemType, playerCardNumber: playerCardNumber, hiddenCardNumber: hiddenCardNumber});
}

