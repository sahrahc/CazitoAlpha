/**
 * REST services and login/logout and other features common to all
 * pages for signed and unsigned users.
 * 
 */

/********************************************************************************************/
function logoutCallback(resp) {
    $.cookies.del("userPlayerId");
    $.cookies.del('playerName');
    $.cookies.del('waitingForSeat');
    $.cookies.del('dailyDisplayed');

    $.cookies.del('tableId');
    $.cookies.del('tableCode');
    $.cookies.del('vanilla-play');
    $.cookies.del('sleeveCards');
    var sPath = window.location.pathname;
    var sPage = sPath.substring(sPath.lastIndexOf('/') + 1);

    if (sPage === "Home.php") {
	window.location.reload();
    }
    else {
	window.location.replace("Home.php");
    }
}
function logout() {
    if ($.cookies.get("joinedTable") === 1) {
	leaveSaloon();
    }

    var obj = {
	requestingPlayerId: $.cookies.get("userPlayerId")
    };
    WSClient.call("logout",
	    obj,
	    logoutCallback);

}


function loginCallback(resp) {
    O('hello').innerHTML = 'Hello ' + resp.playerName + '!';
    $.cookies.set("userPlayerId", resp.userPlayerId, {
	expires: 1,
	path: '/'
    });
    $.cookies.set("playerName", resp.playerName);
    refreshHeader();
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
	    "Submit": function() {
		$(this).dialog("close");
		login(O('playerNameLogin').value);
	    }
	}
    });
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
    $("#dialog-table-setup-message").dialog({
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
	width: 480,
	height: 430,
	buttons: {
	    // TODO: Add should be disabled by default, until user adds a card
	    Add: function() {
		$(this).dialog("close");
		if ($("#selectedCards").children().length > 0) {
		    if (cazito.getGlobalVar("hiddenItemType") === "sleeveCards") {
			loadCardsOnSleeve();
		    }
		    else if (cazito.getGlobalVar("hiddenItemType") === "grooveCards") {
			cheat({itemType: TUCKER_TABLE_SLIDE_UNDER});
		    }
		}
		//S('sleeve').display = 'block';
	    },
	    Cancel: function() {
		// cannot use O() function 
		$("#selectedCards").children().remove();
		$(this).dialog("close");
	    }
	}
    });
    // open the daily if not opened before
    if ($.cookies.get("dailyDisplayed") === null) {
	$.cookies.set("dailyDisplayed", true);
	$("#dialog-daily").dialog('open');
    }
});
