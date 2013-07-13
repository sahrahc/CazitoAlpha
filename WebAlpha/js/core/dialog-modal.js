/**
 * REST services and login/logout and other features common to all
 * pages for signed and unsigned users.
 * 
 */

/********************************************************************************************/
function logout() {
    var obj = {
        userPlayerId: $.cookies.get("userPlayerId")
    };
    WSClient.call("logout",
            obj,
            null);

    $.cookies.del("userPlayerId");
    window.location = 'Home.php';
}


function loginCallback(resp) {
    O('hello').innerHTML = 'Hello ' + resp.playerName;
    $.cookies.set("userPlayerId", resp.userPlayerId, {
        expires: 1,
        path: '/'
    });
    $.cookies.set("playerName", resp.playerName);
}

function login(playerName) {
    var obj = {
        playerName: playerName === "" ? null : playerName
    };
    WSClient.call("login",
            obj,
            loginCallback);

}

/********************************************************************************************/
// all 7 modal dialogs need to be here.
$(function() {
    // close all dialogs - how does this work?
    $("#dialog:ui-dialog").dialog("destroy");
    // must use # instead of O
    $("#dialog-login").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            Submit: function() {
                $(this).dialog("close");
                login(O('playerName').value);
            }
        }
    });
    document.getElementById('playerName').select();
    $("#dialog-daily").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "OK": function() {
                //startPracticeSession();
                $(this).dialog("close");
            }
        }
    });
    $("#dialog-table-setup").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "Create New Table": function() {
                createNewTable();
                $(this).dialog("close");
            },
            "Find Your Table": function() {
                findCasinoTable();
                $(this).dialog("close");
            }
        }
    });
    $("#dialog-how-to").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "OK": function() {
                $(this).dialog("close");
            }
        }
    });
    $("#dialog-new-table-message").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "OK": function() {
                $(this).dialog("close");
            }
        }
        // need to eventually add invite friend options
    });
    $("#dialog-found-table-message").dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            "OK": function() {
                $(this).dialog("close");
            }
        }
    });
    $("#dialog-card-selector").dialog({
        autoOpen: false,
        modal: false,
        width: 460,
        height: 450,
        buttons: {
            Add: function() {
                $(this).dialog("close");
                loadCardsOnSleeve();
                //S('sleeve').display = 'block';
            },
            Cancel: function() {
                O("selectedCards").children().remove();
                $(this).dialog("close");
            }
        }
    });
    // open the daily if not opened before
    if ($.cookies.get('dailyDisplayed') === null) {
        $.cookies.set('dailyDisplayed', true);
        $("#dialog-daily").dialog('open');
    }
});
