/*-----------------------------------------------------------------------------------------*/
/**
 * If response to a 'create new table' request is successful,
 * display the appropriate message and save the casino table id.
 * @param {type} casinoTableDto
 * @returns {undefined}
 */
function createNewTableCallback(casinoTableDto) {
    // add table id to cookie for use when joining table
    $.cookies.set("tableId", casinoTableDto.casinoTableId, {
	expires: 1,
	path: '/'
    });
    // messages to user:
    var tableName = casinoTableDto.casinoTableName;
    O('dialog-table-setup-message').children[0].innerHTML = 'You have successfully created your table ' + tableName;
    $('#dialog-table-setup-message').dialog('open');

    // on cookie in case 
    $.cookies.del('waitingForSeat');
}

/**
 * If the request to save casino table callback is successful, then notify
 * user. Also show the number of current players and waiting list players.
 * @param {type} casinoTableDto
 * @returns {undefined}
 */
function findCasinoTableCallback(casinoTableDto) {
    // add table id to cookie for use when joining table
    $.cookies.set("tableId", casinoTableDto.casinoTableId, {
	expires: 1,
	path: '/'
    });
    // messages to user:
    var numberPlayers = casinoTableDto.numberCurrentPlayers;
    var waitingPlayers = casinoTableDto.numberWaitingPlayers;
    var msg;
    if (numberPlayers > 0) {
	msg = 'There are ' + numberPlayers + ' players at this table.';
    }
    else {
	msg = 'There are no players yet at this table.';
    }

    if (waitingPlayers > 0) {
	msg += '<br/>There are ' + waitingPlayers + ' players in the waiting list.';
	// on cookie because heading changes
	$.cookies.set("waitingForSeat", waitingPlayers);
    }
    else {
	msg += '<br/>There are no players on the waiting list.';
    }
    O('dialog-table-setup-message').children[0].innerHTML = msg;
    $('#dialog-table-setup-message').dialog('open');
}


function createNewTable() {
    var userPlayerId = $.cookies.get("userPlayerId");
    if (userPlayerId === null) {
	alert('Need to login first');
	return;
    }
    var tableName = O('tableName') !== null && O('tableName').value === '' ? null : O('tableName').value;
    var tableCode = O('tableCode') !== null && O('tableCode').value === '' ? null : O('tableCode').value;
    var tableSize = O('tableSizeId');
    var obj = {
	requestingPlayerId: userPlayerId,
	tableName: tableName === null ? null : O('tableName').value,
	tableCode: tableCode === null ? null : O('tableCode').value,
	tableSize: tableSize.options[tableSize.selectedIndex].text
    };

    // add table code to cookie so they are not asked again when joining the
    // table (which happens if call successful)
    $.cookies.set("tableCode", O('tableCode').value, {
	expires: 1,
	path: '/'
    });
    $.cookies.set("tableName", O('tableName').value, {
	expires: 1,
	path: '/'
    });

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call(CREATE_TABLE,
	    obj,
	    createNewTableCallback);
}
;

function findCasinoTable() {
    var tableName = O('tableName') !== null && O('tableName').value === '' ? null : O('tableName').value;
    var tableCode = O('tableCode') !== null && O('tableCode').value === '' ? null : O('tableCode').value;
    var obj = {
	requestingPlayerId: $.cookies.get("userPlayerId"),
	tableName: tableName === null ? null : O('tableName').value,
	tableCode: tableCode === null ? null : O('tableCode').value
    };
    // add table code to cookie so they are not asked again when joining the
    // table (which happens if call successful)
    $.cookies.set("tableCode", O('tableCode').value, {
	expires: 1,
	path: '/'
    });
    $.cookies.set("tableName", O('tableName').value, {
	expires: 1,
	path: '/'
    });

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call(GET_TABLE,
	    obj,
	    findCasinoTableCallback);
}
;

/********************************************************************************************/
function updateUserSeatTaken(playerStatusDto) {
    if (playerStatusDto.seatNumber === null) {
	return;
    }
    updatePlayerIdentity(playerStatusDto);
    var userTag = 'player' + playerStatusDto.seatNumber;
    O(userTag + 'Status').innerHTML = playerStatusDto.status; // Seated
    O(userTag + 'Stake').innerHTML = '';
    O(userTag + 'Info').setAttribute("class", 'playerInfo playerInfoUser');

    // If taking a seat during the middle of a game, the status of the previous
    // player who left remains until a new game starts.
    // how do you detect game in progress?
    //if (O('gameStatus').innerHTML !== ) {
    S('takeSeatButton').display = 'none';
    O('startGameButton').disabled = false;
    unDimItem('startGameButton');

    //}
    displayCenterMessage(null);
    if (O('gameStatus').innerHTML === GAME_INACTIVE) {
	displayCenterMessage('Please press the start button to start play');
    }
    // TODO: verify leaving previous player data is ok.
    if (playerStatusDto.waitingListSize > 0) {
	updateWaitlistCount(playerStatusDto.waitingListSize);
    }
}

function takeSeat() {
    sendRequest('TakeSeat', O('seatNumber').innerHTML);
}

function offerSeat(seatNumber) {
    displayCenterMessage("You are being offered a seat...");
    // overlay button to allow user select seat
    S('takeSeatButton').display = 'block';
    O('takeSeatButton').setAttribute("class", 'player' + seatNumber + 'Info');
}

function leaveSaloon() {
    $.cookies.set("joinedTable", 0);
    if (O('isPractice').innerHTML === "1") {
	sendRequest('EndPractice');
    }
    else {
	sendRequest('LeaveTable');
    }
    window.location.replace("Home.php");
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Given a text message, display it on the center
 * FIXME: to be a popup or a rasterized image, HTML text not centering properly
 * @param {string} msg
 * @returns {undefined}
 */
/* S("centerMessageId").top = bH/2 - 30 + 'px';
 S("centerMessageId").left = bW/2 - 120 + 'px'; */
function displayCenterMessage(msg) {
    if (msg === null) {
	S("centerMessageId").display = 'none';
    }
    else {
	O("centerMessageId").innerHTML = msg;
	S("centerMessageId").display = 'block';
    }
}

function updateWaitlistCount(waitingListSize) {
    var waitingForSeat = waitingListSize;
    if (waitingListSize !== null) {
	$.cookies.set("waitingForSeat", waitingListSize);
    }
    else {
	waitingForSeat = $.cookies.get("waitingForSeat");
    }
    if (waitingForSeat > 0) {
	S('waiting-list').display = 'inline';
	O('waiting-list').innerHTML = waitingForSeat + ' players are waiting for a seat in your table.';
    }
    else {
	S('waiting-list').display = 'none';
    }
}

