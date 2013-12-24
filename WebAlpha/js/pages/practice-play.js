jQuery(document).ready(function() {
    $.cookies.set("vanilla-play", 1);
    if ($.cookies.get("userPlayerId") === null) {
	alert('You need to sign in first, please go to home page');
	window.location.replace("Home.php");
    }
    else {
	S('waiting-list').display = 'none';
	startPracticeSession();

	O('leaveSaloonButton').value = 'Stop Practice';
    }
});