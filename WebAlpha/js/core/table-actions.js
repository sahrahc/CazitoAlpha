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
    O('dialog-new-table-message').children('p')[0].innerHTML = 'You have successfully created your table ' + tableName;
    O('dialog-new-table-message').dialog('open');
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
    if (numberPlayers > 0) {
        O('dialog-found-table-message').children('p')[0].innerHTML = 'There are ' + numberPlayers + ' players at this table.';
    }
    else {
        O('dialog-found-table-message').children('p')[0].innerHTML = 'There are no players yet at this table.';
    }

    if (waitingPlayers > 0) {
        O('dialog-found-table').children('p')[1].innerHTML = 'There are ' + waitingPlayers + ' players in the waiting list.';
    }
    else {
        O('dialog-found-table').children('p')[1].innerHTML = 'There are no players on the waiting list.';
    }
    O('dialog-found-table').dialog('open');
}


function createNewTable() {
    var obj = {
        requestingPlayerId: $.cookies.get("userPlayerId"),
        tableName: O('tableName').value === "" ? null : O('tableName').value,
        tableCode: O('tableCode').value === "" ? null : O('tableCode').value
    };

    // add table code to cookie so they are not asked again when joining the
    // table (which happens if call successful)
    $.cookies.set("tableCode", O('tableCode').value, {
        expires: 1,
        path: '/'
    });

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("CreateTable",
            obj,
            createNewTableCallback);
}
;

function findCasinoTable() {
    var obj = {
        requestingPlayerId: $.cookies.get("userPlayerId"),
        tableName: O('tableName').value === "" ? null : O('tableName').value,
        tableCode: O('tableCode').value === "" ? null : O('tableCode').value
    };
    // add table code to cookie so they are not asked again when joining the
    // table (which happens if call successful)
    $.cookies.set("tableCode", O('tableCode').value, {
        expires: 1,
        path: '/'
    });

    // TODO: check if authenticated and pass -1 if not otherwise the currentPlayerId
    WSClient.call("GetTable",
            obj,
            findCasinoTableCallback);
}
;

/********************************************************************************************/
function updateUserSeatTaken(playerStatusDto) {
    if (playerStatusDto.seatNumber !== null) {
        return;
    }
    updatePlayerIdentity(playerStatusDto);

    // If taking a seat during the middle of a game, the status of the previous
    // player who left remains until a new game starts.
    if (playerStatusDto.playerId === $.cookies.get('userPlayerId')) {
        S('takeSeatButton').display = 'none';
        O('seatNumber').innerHTML = playerStatusDto.seatNumber;
        O('startGameButton').disabled = false;
        unDimItem('startGameButton');

        if (O('gameStatus').innerHTML === GAME_INACTIVE) {
            displayCenterMessage('Please press the start button to start play');
        }
    }
    // TODO: verify leaving previous player data is ok.
    if (playerStatusDto.waitingListSize > 0) {
        updateWaitingListMessage(playerStatusDto.waitingListSize);
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
    sendRequest('LeaveTable');
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
/**
 * Given a text message, display it on the center
 * FIXME: to be a popup or a rasterized image, HTML text not centering properly
 * @param {string} msg
 * @returns {undefined}
 */
function displayCenterMessage(msg) {
    /* S("centerMessageId").top = bH/2 - 30 + 'px';
     S("centerMessageId").left = bW/2 - 120 + 'px'; */
    if (msg === null) {
        S("CenterMessageId").display = 'none';
    }
    else {
        O("centerMessageId").innerHTML = msg;
        S("centerMessageId").display = 'block';
    }
}
function updateWaitingListMesage(waitingListSize) {
    O('waitingMessageId').innerHTML = 'There are ' + waitingListSize +
            ' players waiting for a seat';
}

