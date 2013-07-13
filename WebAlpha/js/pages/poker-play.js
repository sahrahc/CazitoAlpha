jQuery(document).ready(function() {
    if ($.cookies.get("tableCode") === null) {
        startPracticeSession();
    }
    else {
        // make rest call to join table
        joinCasinoTable();
    }
});
