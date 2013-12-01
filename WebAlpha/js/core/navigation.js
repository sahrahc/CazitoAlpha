function refreshHeader() {
    var playerName = $.cookies.get("playerName");
    var userPlayerId = $.cookies.get("userPlayerId");
    if (userPlayerId !== null && playerName !== null) {
	O('hello').innerHTML = 'Hello ' + playerName + '!';
    }
    else {
	O('hello').innerHTML = 'Hello Guest!';

    }
    // navigation bar
    if (userPlayerId) {
	S('registerButton').display = 'none';
	S('loginButton').display = 'none';

	S('logoutButton').display = 'inline';
	S('availableCurrency').display = 'inline';
	S('buyCurrencyButton').display = 'inline';

    }
    else {
	S('registerButton').display = 'inline';
	S('loginButton').display = 'inline';

	S('logoutButton').display = 'none';
	S('availableCurrency').display = 'none';
	S('buyCurrencyButton').display = 'none';

    }
}
function refreshHeaderPlay() {
    var playerName = $.cookies.get("playerName");
    var userPlayerId = $.cookies.get("userPlayerId");
    if (userPlayerId === null || playerName === null) {
	window.location.replace("Home.php");
	return;
    }
    if (userPlayerId === null || playerName === null) {
	window.location.replace("Home.php");
	return;
    }

    O('hello').innerHTML = 'Hello ' + playerName + '!';

    S('logoutButton').display = 'inline';
    S('availableCurrency').display = 'inline';
    S('buyCurrencyButton').display = 'inline';

    S('leaveSaloonButton').display = 'inline';
    S('startGameButton').display = 'inline';

    var isPractice = O('isPractice') === null ? "0" : "1";
    if (isPractice === "1") {
	S('inviteToTable').display = 'inline';
    }
    else {
	S('inviteToTable').display = 'none';
    }
}
