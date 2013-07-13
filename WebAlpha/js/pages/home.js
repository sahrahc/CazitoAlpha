
/**********************************************************************/
// home page options
function popupLogin() {
    $('#dialog-login').dialog('open');
}

function popupTableSetup() {
    $('#dialog-table-setup').dialog('open');
}
function startSafePlay() {
    if ($.cookies.get('tableId') === null) {
        popup('Please choose a table first');
    } else {
        window.location = 'SafePlay.php';
    }
}

function startSeedyPlay() {
    if ($.cookies.get('tableId') === null) {
        popup('Please choose a table first');
    } else {
        window.location = 'CheatingPlay.php';
    }
}

function popupHowTo() {
    $('#dialog-how-to').dialog('open');
}
/**********************************************************************/

