<?php

////////////////////////////////////////////////////////////
// Usage: function used by cleanup on regression tests
//        remove sessions without players - to be run
//        after all test players are removed.
//        
//	  Need to be rewritten for in memory database with
//	  same logic when data store migrated to Redis
//	  
//	    GameCard
//	    PlayerAction
//	    NextPokerMove
//	    PlayerState
//	    GameInstance
//	    GameSession
// 
// IN: $conTest
//     $gameSessionId
// OUT: none
// 
////////////////////////////////////////////////////////////
function cleanUpOrphanCasino($conTest) {
    echo "-----CleanUpOrphanSessions-----------------------------------<br/>";
    
    // how performant is this?
    $sql = "DELETE GameSession
	    FROM GameSession
	    LEFT JOIN PlayerState on GameSession.ID = PlayerState.GameSessionId
	    WHERE PlayerState.GameSessionId is null";	
    executeSQL($sql, "Error Deleting from GameSession with id's not in player state");
    echo "Info: " . mysql_affected_rows() . " GameSession rows deleted for session.<br/>";

    $sql = "DELETE GameInstance
	    FROM GameInstance
	    LEFT JOIN PlayerState on GameInstance.ID = PlayerState.GameInstanceId
	    WHERE PlayerState.GameInstanceId is null";	
    executeSQL($sql, "Error Deleting from GameInstance with id's not in player state");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows deleted for session.<br/>";

    echo "-----CleanUpOrphanSessions-----------------------------------<br />";
}
?>
