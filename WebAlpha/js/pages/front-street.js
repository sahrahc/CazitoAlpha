/**
 * After document loaded but before content loaded
 */
jQuery(document).ready(function() {
    // enable tool tips for the cheating items (only one on front-street)
    $(".preCheatImg").tooltip();

    cazito.setGlobalVar("hiddenItemType", "sleeveCards");
});

/**
 * After content has been loaded...
 * User must be logged in to reach this page.
 */
window.onload = function() {
    if ($.cookies.get("userPlayerId") === null) {
	window.location.replace("Home.php");
    }
    else {
	// load the hidden cards if any
	var obj = {
	    userPlayerId: $.cookies.get("userPlayerId")
	};

	// this is show card on sleeve...
	if ($.cookies.get("sleeveCards") === null) {
	    WSClient.call("cheatGetSleeve",
		    obj,
		    loadCardsOnSleeveCallback);
	}
	else {
	    var cardList = $.cookies.get("sleeveCards", cardList);
	    if (cardList !== null) {
		cheatUpdateHiddenCards($("#sleeve"), cardList);
	    }
	}
    }
    refreshHeader();
};

/********************************************************************************************/
function removeFromSleeve(obj) {
// TODO: implement on right click on card on sleeve, UI and back-end
    $(obj).remove();
}

/********************************************************************************************/

function loadCardsOnSleeveCallback(cardList) {
    S('sleeve').display = 'block';
    // display them on sleeve. cardList is of type CheaterCardDto
    if (cardList === null) {
	return;
    }
    cheatUpdateHiddenCards($("#sleeve"), cardList);
    // empty the card list on dialog
    $("selectedCards").empty();
}

function loadCardsOnSleeve() {
    var cardNameList = [];
    $("#selectedCards").children().each(function() {
	var child = $(this);
	cardNameList.push(child.attr("title"));
    });
    var obj = {
	itemType: LOAD_CARD_ON_SLEEVE,
	userPlayerId: $.cookies.get("userPlayerId"),
	gameSessionId: null,
	gameInstanceId: null,
	cardNameList: cardNameList
    };
    /* mocked data
     var cardList = [ "hearts_J", "spades_3", "clubs_A"];
     loadCardsOnSleeveCallback(cardList);
     */
    WSClient.call("cheatLoadSleeve",
	    obj,
	    loadCardsOnSleeveCallback);
}

function startSeedyPlay() {
    window.location.replace("CheatingPlay.php");
}
