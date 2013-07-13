<?php

////////////////////////////////////////////////////////////
// Usage: function used only by on regression test cleanup
//        database connection already established.
//        
//	  Need to be rewritten for in memory database with
//	  same logic when data store migrated to Redis
//	  
//	  To be used after deleting players and sessions.
//        Deletes casino tables that have no players or game
//        sessions. 
//        
//	    CasinoTable
// 
// IN: $conTest
// OUT: none
// 
// Setup ///////////////////////////////////////////////////

function cleanUpOrphanCasino() {
    echo "-----CleanOrphanCasino---------------------------------------<br/>";

    // delete casino tables without players
    executeSQL("DELETE CasinoTable FROM CasinoTable 
    LEFT JOIN Player 
    ON CasinoTable.Id = Player.CurrentCasinoTableId
    And Player.CurrentCasinoTableId IS NULL", 
            "Error deleting from CasinoTable with no players");
    echo "Info: " . mysql_affected_rows() . " CasinoTable rows deleted because no players.<br />";

    // delete casino table without game sessions
    executeSQL("DELETE CasinoTable FROM CasinoTable
    LEFT JOIN GameSession 
    ON CasinoTable.CurrentGameSessionId = GameSession.Id
    And GameSession.Id IS NULL",
            "Error deleting from CasinoTable with no game session");
    echo "Info: " . mysql_affected_rows() . " CasinoTable rows deleted because no game sesions.<br/>";
    
    echo "-----CleanUpOrphanCasino-------------------------------------<br />";
    
}

?>
