// FIXME: need to split up cheating and poker playing
constServiceUrl = "http://localhost//Sprint9//PokerService//PokerPlayerService.php";

function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}
function S(obj) {
    return O(obj).style;
}
function getSize(pixel) {
    return Number(pixel.substr(0, pixel.length - 2));
}

function addToSleeve(card) {
    $("#selectedCards").append("<img class='cImg' src='../../../images/PokerCard_" + card 
        + "_small.png' title='" + card + "' alt='" + card + "' />")
}

function dimCard(id) {
    $('#' + id).addClass("fade");
}

function unDimCard(id) {
    $('#' + id).removeClass("fade");
}

function showCardSelector() {
    $('#cardSelectorDialog').dialog('open');
}

function enterTable(){
    $.cookies.set("tableValue", O('tableSizeId').value, {
        expires :1 ,
        path: '/'
    })
    window.location = "PlayGame.php";
    return false;
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
        itemType: 'LoadCardOnSleeve',
        userPlayerId: $.cookies.get("userPlayerId"),
        gameSessionId: null,
        gameInstanceId: null,
        cardNameList: cardNameList
    };
    /* mocked data
    var cardList = [ "hearts_J", "spades_3", "clubs_A"];
    loadCardsOnSleeveCallback(cardList);
    */
    WSClient.call("cheat",
        obj,
        loadCardsOnSleeveCallback);
}


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

function logout() {
    var obj = {
        userPlayerId: $.cookies.get("userPlayerId")
    };
    WSClient.call("logout",
        obj,
        null);
    $.cookies.del("userPlayerId");
    $('#loginDialog').dialog('open');
}

function loginCallback(resp) {
    $('#casinoTableHeader').children('p')[0].innerHTML = "Welcome " + resp.playerName
    + " to the Dead Man's Texas Hold Em!";
    $.cookies.set("userPlayerId", resp.userPlayerId, {
        expires :1 ,
        path: '/'
    });
    $.cookies.set("playerName", resp.playerName);
}
function login(playerName) {
    var obj = {
        playerName: playerName == "" ? null : playerName
    };
    WSClient.call("login",
        obj,
        loginCallback);

}

/********************************************************************************************/
$(function() {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#loginDialog" ).dialog({
        autoOpen:false,
        modal:true,
        buttons:  {
            Submit: function() {
                $(this).dialog("close");
                login(O('playerNameText').value);
            }
        }
    });
    $( "#cardSelectorDialog" ).dialog({
        autoOpen:false,
        modal:false,
        width: 460,
        height: 450,
        buttons:  {
            Add: function() {
                $(this).dialog("close");
                loadCardsOnSleeve();
                //S('sleeve').display = 'block';
            },
            Cancel: function() {
                $("#selectedCards").children().remove();
                $(this).dialog("close");
            }
        }
    });
});

window.onload = function() {
    if ($.cookies.get("userPlayerId") == null) {
        $('#loginDialog').dialog('open');
    }
    else{
        // load the hidden cards if any
        var obj = {
            userPlayerId: $.cookies.get("userPlayerId")
        };

        WSClient.call("cheatLoadSleeve",
            obj,
            loadCardsOnSleeveCallback);
    }
    document.getElementById('playerNameText').select();
    var playerName = $.cookies.get("playerName");
    if (playerName != null) {
        $('#casinoTableHeader').children('p')[0].innerHTML = "You are logged in as " + playerName;
    }
}
