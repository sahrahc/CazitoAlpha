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
//	    ExpectedPokerMove
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
    
    $result = executeSQL("SELECT GameSession.id 
	    FROM GameSession
	    LEFT JOIN PlayerState on GameSession.ID = PlayerState.GameSessionId
	    WHERE PlayerState.GameSessionId is null",
            "Error selecting from GameSession with id's not in player state");
    $qConn = QueueManager::GetConnection();
    $qCh = QueueManager::GetChannel($qConn);
    while ($row= mysql_fetch_array($result, MYSQL_NUM)) {
        $q = QueueManager::GetGameSessionQueue($row[0], $qCh);
        QueueManager::DeleteQueue($q);
    }
    // how performant is this?
    executeSQL("DELETE GameSession
	    FROM GameSession
	    LEFT JOIN PlayerState on GameSession.ID = PlayerState.GameSessionId
	    WHERE PlayerState.GameSessionId is null",
            "Error Deleting from GameSession with id's not in player state");
    echo "Info: " . mysql_affected_rows() . " GameSession rows deleted for session.<br/>";

    executeSQL("DELETE GameInstance
	    FROM GameInstance
	    LEFT JOIN PlayerState on GameInstance.ID = PlayerState.GameInstanceId
	    WHERE PlayerState.GameInstanceId is null",
            "Error Deleting from GameInstance with id's not in player state");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows deleted for session.<br/>";

    echo "-----CleanUpOrphanSessions-----------------------------------<br />";
}
?>
