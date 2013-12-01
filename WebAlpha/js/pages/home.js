
/**********************************************************************/
// home page options

function startPracticePlay() {
    window.location.replace("PracticePlay.php");
}

function popupTableSetup() {
    $('#dialog-table-setup').dialog('open');
}
function startSafePlay() {
    if ($.cookies.get("tableId") === null) {
	alert('Please choose a table first');
    } else {
	window.location.replace("SafePlay.php");
    }
}

function startSeedyPlay() {
    if ($.cookies.get("tableCode") === null) {
	alert('Please choose a table first');
    } else {
	window.location.replace("FrontStreet.php");
    }
}

function popupHowTo() {
    $('#dialog-how-to').dialog('open');
}
/**********************************************************************/
window.onload = function() {
    S('waiting-list').display = 'none';
    refreshHeader();
}