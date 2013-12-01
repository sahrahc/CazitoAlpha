jQuery(document).ready(function() {
    // make rest call to join table
    if ($.cookies.get("tableId") !== null) {
	joinCasinoTable();
    }
    if ($.cookies.get("tableId") === null) {
	alert('You need to select a table, please go to home page');
	window.location.replace("Home.php");
    }
    else {
	// use previously saved count on cookie
	updateWaitlistCount();
    }
   
    /* refreshHeaderPlay and animateCard called from joinCasinoTableCallback */
});

window.onload = function() {
    $.cookies.set("vanilla-play", 1);
    //disableCheatingItems(true);

}