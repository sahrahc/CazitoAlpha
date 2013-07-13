<?php

////////////////////////////////////////////////////////////
// Usage: function used on regression test cleanup
//	  Deletes given player and all history.
//	  Deletes game instances for which the player is
//	  the dealer
//	  
//	  Need to be rewritten for in memory database with
//	  same logic when data store migrated to Redis
//	  
//	    GameCard
//	    PlayerAction
//	    NextPokerMove
//	    PlayerState
// 
// IN: $conTest
//     $playerId
// OUT: none
// 
////////////////////////////////////////////////////////////

function cleanUpPlayerById($playerId) {
    echo "-----CleanUpPlayerBydId: $playerId --------------------------<br/>";
    // delete from player transaction entities
    executeSQL("DELETE FROM PlayerState WHERE PlayerId = $playerId", 
            "Error deleting PlayerState player Id $playerId");
    echo "Info: " . mysql_affected_rows() . " PlayerState rows deleted for player.<br />";
    
    executeSQL("DELETE FROM GameCard WHERE PlayerId = $playerId",
            "Error deleting GameCard player id $playerId");
    echo "Info: " . mysql_affected_rows() . " GameCard rows deleted for player.<br />";
    
    executeSQL("DELETE FROM PlayerAction WHERE PlayerId = $playerId", 
            "Error deleting PlayerAction player id $playerId");
    echo "Info: " . mysql_affected_rows() . " PlayerAction rows deleted for player.<br/>";
    
    executeSQL("DELETE FROM ExpectedPokerMove WHERE PlayerId = $playerId",
            "Error deleting ExpectedPokerMove player id $playerId");
    echo "Info: " . mysql_affected_rows() . " ExpectedPokerMove rows deleted for player.<br/>";
    
    // better to delete using GameInstance by Id, only used on tests
    executeSQL("UPDATE GameInstance 
    SET DealerPlayerId = null
    OR FirstPlayerId = $playerId 
    OR NextPlayerId = $playerId",
            "Error deleting GameInstance player id $playerId");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows deleted for player.<br />";
    
    /* need to remove player from game instance */
    executeSQL("UPDATE GameInstance 
    SET DealerPlayerId = null
    WHERE DealerPlayerId = $playerId", 
            "Error updating GameInstance dealer player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";

    executeSQL("UPDATE GameInstance 
    SET FirstPlayerId = null
    WHERE FirstPlayerId = $playerId", 
            "Error updating GameInstance first player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";
    
    executeSQL("UPDATE GameInstance 
    SET NextPlayerId = null
    WHERE NextPlayerId = $playerId",
            "Error updating GameInstance next player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";

    // delete from Player
    executeSQL("DELETE FROM Player WHERE Id = $playerId",
            "Error deleting Player with id $playerId");
    echo "Info: " . mysql_affected_rows() . " Player rows deleted for player.<br/>";
    
    $qConn = QueueManager::GetConnection();
    $qCh = QueueManager::GetChannel($qConn);
    $q = QueueManager::GetPlayerQueue($playerId, $qCh);
    // practice players don't have queues.
    if ($q) {
        QueueManager::DeleteQueue($q);
    }
    echo "-----CleanUpPlayerById---------------------------------------<br />";
}

?>
