/*^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * processing: set positions for all HTML elements dynamically in case of sizing
 * dynamically position player status boxes, which are shown
 * set position for all cards and chips but keep hidden
 * TODO: may have more than 4 players
 * 1) all the player's items such as status box, messages, dealer button and cards
 * 2) community cards
 */
jQuery(document).ready(function() {
    jQuery('#cheatingCatalogCarousel').jcarousel({
	vertical: true,
	start: 1,
	size: 11,
	scroll: 2,
	visible: 9,
	wrap: "circular"
    });

    cazito.setGlobalVar("hiddenItemType", "grooveCards");
});

window.onload = function() {
    // check if there is a user id; if not redirect to the saloon
    if ($.cookies.get("userPlayerId") === null) {
	// FIXME: seedy salon is hard coded, there should be another login page.
	window.location.replace("Home.php");
    }

    O('leaveSaloonButton').value = 'Leave Table';
    
    // make rest call to join table
    if ($.cookies.get("tableId") !== null) {
	joinCasinoTable();
    }
    if ($.cookies.get("tableId") === null) {
	alert('You need to select a table, please go to home page');
	window.location.replace("Home.php");
    }
    // use previously saved count on cookie
    updateWaitlistCount();
    $.cookies.set("vanilla-play", 0);

    // load sleeve. This is the only cheating option that is activated before going to a table
    var cardList = $.cookies.get("sleeveCards", cardList);
    if (cardList !== null) {
	cheatUpdateHiddenCards($("#sleeveCards"), cardList);
    }
    var cardList = $.cookies.get("grooveCards", cardList);
    if (cardList !== null) {
	cheatUpdateHiddenCards($("#grooveCards"), cardList);
    }
    /*
     var widthToHeight = 4 / 3;
     var newWidth = window.innerWidth;
     var newHeight = window.innerHeight;
     */
    /* refreshHeaderPlay() and animateCard() from joinCasinoTableCallback() */

    // cheating items
    resetCheatingOnSessionStart();
    resetCheatingOnGameStart();

    // why is this needed?
    //disableCheatingItems(false);

};

/********************************************************************************************/
function cheat(arg) {
    var instanceId = O('gameInstanceId').innerHTML;

    var eventData = {
	itemType: arg.itemType,
    };
    if (eventData.itemType === null) {
	eventData.itemType = $(this).attr("id");
    }
    switch (arg.itemType) {
	// nothing else to do for suit markers
	case HEART_MARKER:
	case CLUB_MARKER:
	case DIAMOND_MARKER:
	    break;
	case ACE_PUSHER:
	    eventData.playerCardNumber = arg.playerCardNumber;
	    break;
	case USE_CARD_ON_SLEEVE:
	    eventData.playerCardNumber = arg.playerCardNumber;
	    eventData.hiddenCardNumber = arg.hiddenCardNumber;
	    break;
	case RIVER_SHUFFLER:
	    // set next river action on response
	    break;
	case RIVER_SHUFFLER_USE:
	    //S(RIVER_SHUFFLER_USE + '-div').display = 'none';
	    break;
	case POKER_PEEKER:
	    eventData.otherPlayerId = arg.otherPlayerId;
	    eventData.playerCardNumber = arg.playerCardNumber;
	    break;
	case SOCIAL_SPOTTER:
	    break;
	case TUCKER_TABLE_SLIDE_UNDER:
	    eventData.cardNameList = [];
	    $("#selectedCards").children().each(function() {
		var child = $(this);
		eventData.cardNameList.push(child.attr("title"));
	    });
	    // empty the card list on dialog
	    $("selectedCards").empty();
	    break;
	case TUCKER_TABLE_EXCHANGE:
	    eventData.playerCardNumber = arg.playerCardNumber;
	    eventData.hiddenCardNumber = arg.hiddenCardNumber;
	    break;
	case SNAKE_OIL_MARKER:
	    break;
	case ANTI_OIL_MARKER:
	    eventData.otherPlayerId = arg.otherPlayerId;
	    break;
	case KEEP_FACE_CARDS:
	    break;
    }
    if ($.inArray(arg.itemTye, [HEART_MARKER, CLUB_MARKER, DIAMOND_MARKER,
	ACE_PUSHER, USE_CARD_ON_SLEEVE,
	RIVER_SHUFFLER, RIVER_SHUFFLER_USE,
	POKER_PEEKER, TUCKER_TABLE_EXCHANGE]) && instanceId === null) {
	alert('There is no active game');
	return;
    }

    sendRequest(CHEAT, eventData);

}

/********************************************************************************************/
function updateCheatingEvent(itemType, eventData, eventType) {
    /*
     if (eventType === ITEM_UNLOCK) {
     O(itemType).disabled = false;
     unDimItem(itemType);
     }
     else if (eventType === ITEM_END) {
     O(itemType).disabled = true;
     dimItem(itemType);
     }
     else if (eventType === ITEM_LOG && eventData.isDisabled === 1
     && itemType !== RIVER_SHUFFLER_USE
     && itemType !== TUCKER_TABLE_SLIDE_OUT
     && itemType !== TUCKER_TABLE_EXCHANGE) {
     O(itemType).disabled = true;
     //dimItem(itemType + '-Act');
     }
     */
    if (eventData.isDisabled === 0) {
	if (itemType === RIVER_SHUFFLER_USE || itemType === RIVER_SHUFFLER) {
	    // second condition should never happen
	    cazito.setGlobalVar(RIVER_SHUFFLER, 'Enabled');
	}
	else if (itemType === SNAKE_OIL_MARKER_COUNTERED) {
	    unGreyItem(SNAKE_OIL_MARKER);
	} else {
	    unGreyItem(itemType);
	}
	// click must match cheatingCatalog.html
	switch (itemType) {
	    /* simple queue message */
	    case HEART_MARKER:
	    case CLUB_MARKER:
	    case DIAMOND_MARKER:
	    case SOCIAL_SPOTTER:
	    case SNAKE_OIL_MARKER:
		O(itemType).onclick = function() {
		    cheat({itemType: itemType});
		};
		break;
	    case SNAKE_OIL_MARKER_COUNTERED:
		O(SNAKE_OIL_MARKER).onclick = function() {
		    cheat({itemType: SNAKE_OIL_MARKER});
		};
		break;
	    case RIVER_SHUFFLER:
		O(RIVER_SHUFFLER_USE + '-image').onclick = function() {
		    cheat({itemType: RIVER_SHUFFLER});
		};
		break;
	    case RIVER_SHUFFLER_USE:
		O(RIVER_SHUFFLER + '-image').onclick = function() {
		    cheat({itemType: RIVER_SHUFFLER});
		    hideDescription($('#' + RIVER_SHUFFLER));
		};
		break;
		/* draggables */
	    case ACE_PUSHER:
	    case POKER_PEEKER:
	    case ANTI_OIL_MARKER:
		$('#' + itemType + ' img').draggable('enable');
		break;
		/* not time-locked
		 case KEEP_FACE_CARDS:
		 case TUCKER_TABLE_SLIDE_UNDER:
		 case TUCKER_TABLE_SLIDE_OUT:
		 case TUCKER_TABLE_EXCHANGE:
		 */
	}
    }
    else if (eventData.isDisabled === 1) {
	if (itemType === RIVER_SHUFFLER) {
	    cazito.setGlobalVar(RIVER_SHUFFLER, 'Disabled');
	}
	else if (itemType === RIVER_SHUFFLER_USE) {
	    cazito.setGlobalVar(RIVER_SHUFFLER, 'Disabled');
	    // river shuffler use does not get disabled/greyed only hidden
	    greyItem(RIVER_SHUFFLER);
	    O(RIVER_SHUFFLER + '-image').onclick = null;
	} else if (itemType === POKER_PEEKER || itemType === ACE_PUSHER || itemType === ANTI_OIL_MARKER) {
	    greyItem(itemType);
	    //disable draggable
	    $('#' + itemType + ' img').draggable('disable');
	}
	else {
	    greyItem(itemType);
	    O(itemType).onclick = null;
	}
    }

    O('logEvent').innerHTML = eventData.info + '<br />' + O('logEvent').innerHTML;

    // if pokerpeeker or anti-oil marker effectively applied, the cloned element
    // on top of droppable should be removed.
    if (itemType === POKER_PEEKER) {
	$('#PokerPeekerCloned').remove();
    }
    else if (itemType === ANTI_OIL_MARKER) {
	$('#AntiOilMarkerCloned').remove();
    }
    else if (itemType === ACE_PUSHER) {
	$('#AcePusherCloned').remove();
    }
    else if (itemType === RIVER_SHUFFLER_USE) {
	cazito.setGlobalVar("nextRiverAction", 'Look');
	S(RIVER_SHUFFLER + '-image').display = 'inline';
	S(RIVER_SHUFFLER_USE + '-div').display = 'none';
    }
}
/********************************************************************************************/

function showDescription(itemType) {
    if (itemType === null) {
	//itemType = $(this).parent().attr("id");
	return;
    }
    if (itemType.id === RIVER_SHUFFLER &&
	    cazito.getGlobalVar("nextRiverAction") === 'Swap') {
	S(RIVER_SHUFFLER_USE + '-Desc').display = 'block';
    }
    else {
	S(itemType.id + '-Desc').display = 'block';
    }
}

function hideDescription(itemType) {
    if (itemType === null) {
	//itemType = $(this).parent().attr("id");
	null;
    }
    if (itemType.id === RIVER_SHUFFLER &&
	    cazito.getGlobalVar("nextRiverAction") === 'Swap') {
	S(RIVER_SHUFFLER_USE + '-Desc').display = 'none';
    }
    else {
	S(itemType.id + '-Desc').display = 'none';
    }
}

/********************************************************************************************/
