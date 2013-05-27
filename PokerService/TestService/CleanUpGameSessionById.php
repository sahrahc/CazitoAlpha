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

function cleanUpGameSessionById($conTest, $gameSessionId) {

    // Exception handling: verify parameter
    echo "-----CleanUpGameSessionById: Deleting session $gameSessionId --------------<br/>";
    // delete GameInstance, GameCard, PlayerAction, NextPokerMove
    // and GameSession for a GameSessionId
    $sql = "DELETE GameCard FROM GameCard INNER JOIN GameInstance
    ON GameCard.GameInstanceId = GameInstance.Id
    WHERE GameInstance.GameSessionId = $gameSessionId";
    executeSQL($sql, "Error Deleting From GameCard for session Id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " GameCard rows deleted for session.<br />";
    
    $sql = "DELETE PlayerAction FROM PlayerAction INNER JOIN GameInstance
    ON PlayerAction.GameInstanceId = GameInstance.Id
    WHERE GameSessionId = $gameSessionId";
    executeSQL($sql, "Error Deleting From PlayerAction for session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " PlayerAction rows deleted for session.<br />";
    
    $sql = "DELETE NextPokerMove FROM NextPokerMove 
    INNER JOIN GameInstance
    ON NextPokerMove.GameInstanceId = GameInstance.Id
    WHERE GameInstance.GameSessionId = $gameSessionId";
    executeSQL($sql, "Error Deleting from NextPokerMove where session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " NextPokerMove rows deleted for session.<br />";
    
    // delete from playerState
    $sql = "DELETE FROM PlayerState WHERE GameSessionId = $gameSessionId";
    executeSQL($sql, "Error Deleting from PlayerState where session id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " PlayerState rows deleted for session.<br />";
    
    $sql = "DELETE FROM GameInstance WHERE GameSessionId = $gameSessionId";
    executeSQL($sql, "Error Deleting from GameInstance where sessionid $gameSessionId");
    echo "Info: " .mysql_affected_rows() . " GameInstance rows deleted for session.<br />";
    
    $sql = "DELETE FROM GameSession WHERE Id = $gameSessionId";
    executeSQL($sql, "Error Deleting from GameSession where id $gameSessionId");
    echo "Info: " . mysql_affected_rows() . " GameSession rows deleted for session.<br/>";
    echo "-----CleanUpGameSessionById----------------------------------<br />";
    
}

?>
