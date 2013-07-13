/**
 * After document loaded but before content loaded
 */
jQuery(document).ready(function() {
    jQuery('.jcarousel-skin-card').jcarousel({
        vertical: false,
        start: 1,
        size: 13,
        scroll: 7,
        visible: 8
    });
    // enable tool tips for the cheating items
    $(".preCheatImg").tooltip();
});

/**
 * After content has been loaded...
 * User must be logged in to reach this page.
 */
window.onload = function() {
    if ($.cookies.get("userPlayerId") === null) {
        $('#dialog-login').dialog('open');
        O('playerName').select();
    }
    else{
        // load the hidden cards if any
        var obj = {
            userPlayerId: $.cookies.get("userPlayerId")
        };

        // this is show card on sleeve...
        WSClient.call(LOAD_CARD_ON_SLEEVE, //"cheatLoadSleeve",
            obj,
            loadCardsOnSleeveCallback);
    }
    var playerName = $.cookies.get("playerName");
    if (playerName !== null) {
        // check if innerHTML or text?
        O('hello').innerHTML = 'Hello ' + playerName;
    }
};

/********************************************************************************************/
function addToSleeve(card) {
    $("#selectedCards").append("<img class='cImg' src='../../../images/PokerCard_" + card 
        + "_small.png' title='" + card + "' alt='" + card + "' />");
}

function showCardSelector() {
    $('#dialog-card-selector').dialog('open');
}

/********************************************************************************************/

function loadCardsOnSleeveCallback(cardList) {
    S('sleeve').display = 'block';
    // display them on sleeve. cardList is of type CheaterCardDto
    for (var j=0, m=cardList.length; j<m; j++) {
        $("#sleeve").append("<img class='cImg' src='../../../images/PokerCard_" + cardList[j]
            + "_small.png' title='" + cardList[j] + "' alt='" + cardList[j] + "' />")
    }
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
    WSClient.call(CHEAT,
        obj,
        loadCardsOnSleeveCallback);
}


