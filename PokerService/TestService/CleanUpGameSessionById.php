<?php

////////////////////////////////////////////////////////////
// Usage: function used only by regression tests on cleanup, 
//	  Database connection already established.
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
// Setup ///////////////////////////////////////////////////

function cleanUpGameSessionById($gameSessionId) {

    // Exception handling: verify parameter
    echo "-----CleanUpGameSessionById: Deleting session $gameSessionId --------------<br/>";
    // delete GameInstance, GameCard, PlayerAction, NextPokerMove
    // and GameSession for a GameSessionId
    executeSQL("DELETE GameCard FROM GameCard INNER JOIN GameInstance
    ON GameCard.GameInstanceId = GameInstance.Id
    WHERE GameInstance.GameSessionId = $gameSessionId", 
            "Error Deleting From GameCard for session Id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " GameCard rows deleted for session.<br />";
    
    executeSQL("DELETE PlayerAction FROM PlayerAction INNER JOIN GameInstance
    ON PlayerAction.GameInstanceId = GameInstance.Id
    WHERE GameSessionId = $gameSessionId",
            "Error Deleting From PlayerAction for session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " PlayerAction rows deleted for session.<br />";
    
    executeSQL("DELETE ExpectedPokerMove FROM ExpectedPokerMove 
    INNER JOIN GameInstance
    ON ExpectedPokerMove.GameInstanceId = GameInstance.Id
    WHERE GameInstance.GameSessionId = $gameSessionId",
            "Error Deleting from ExpectedPokerMove where session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " ExpectedPokerMove rows deleted for session.<br />";
    
    // delete from playerState
    executeSQL("DELETE FROM PlayerState WHERE GameSessionId = $gameSessionId",
            "Error Deleting from PlayerState where session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " PlayerState rows deleted for session.<br />";
    
    executeSQL("DELETE FROM GameInstance WHERE GameSessionId = $gameSessionId",
            "Error Deleting from GameInstance where sessionid $gameSessionId");
    echo "Info: " .mysql_affected_rows() . " GameInstance rows deleted for session.<br />";
    
    executeSQL("DELETE FROM GameSession WHERE Id = $gameSessionId",
            "Error Deleting from GameSession where id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " GameSession rows deleted for session.<br/>";
    echo "-----CleanUpGameSessionById----------------------------------<br />";
    
    $qConn = QueueManager::GetConnection();
    $qCh = QueueManager::GetChannel($qConn);
    $q = QueueManager::GetGameSessionQueue($gameSessionId, $qCh);
    QueueManager::DeleteQueue($q);
}

?>
