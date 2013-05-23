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

function cleanUpPlayerById($conTest, $playerId) {
    echo "-----CleanUpPlayerBydId: $playerId --------------------------<br/>";
    // delete from player transaction entities
    $sql = "DELETE FROM PlayerState WHERE PlayerId = $playerId";
    executeSQL($sql, "Error deleting PlayerState player Id $playerId");
    echo "Info: " . mysql_affected_rows() . " PlayerState rows deleted for player.<br />";
    
    $sql = "DELETE FROM GameCard WHERE PlayerId = $playerId";
    executeSQL($sql, "Error deleting GameCard player id $playerId");
    echo "Info: " . mysql_affected_rows() . " GameCard rows deleted for player.<br />";
    
    $sql = "DELETE FROM PlayerAction WHERE PlayerId = $playerId";
    executeSQL($sql, "Error deleting PlayerAction player id $playerId");
    echo "Info: " . mysql_affected_rows() . " PlayerAction rows deleted for player.<br/>";
    
    $sql = "DELETE FROM NextPokerMove WHERE PlayerId = $playerId";
    executeSQL($sql, "Error deleting NextPokerMove player id $playerId");
    echo "Info: " . mysql_affected_rows() . " NextPokerMove rows deleted for player.<br/>";
    
    // better to delete using GameInstance by Id, only used on tests
    $sql = "UPDATE GameInstance 
    SET DealerPlayerId = null
    OR FirstPlayerId = $playerId 
    OR NextPlayerId = $playerId";
    executeSQL($sql, "Error deleting GameInstance player id $playerId");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows deleted for player.<br />";
    
    /* need to remove player from game instance */
    $sql = "UPDATE GameInstance 
    SET DealerPlayerId = null
    WHERE DealerPlayerId = $playerId";
    executeSQL($sql, "Error updating GameInstance dealer player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";

    $sql = "UPDATE GameInstance 
    SET FirstPlayerId = null
    WHERE FirstPlayerId = $playerId";
    executeSQL($sql, "Error updating GameInstance first player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";
    
    $sql = "UPDATE GameInstance 
    SET NextPlayerId = null
    WHERE NextPlayerId = $playerId";
    executeSQL($sql, "Error updating GameInstance next player id $playerId to null");
    echo "Info: " . mysql_affected_rows() . " GameInstance rows updated for player.<br />";

    // delete from Player
    $sql = "DELETE FROM Player WHERE Id = $playerId";
    executeSQL($sql, "Error deleting Player with id $playerId");
    echo "Info: " . mysql_affected_rows() . " Player rows deleted for player.<br/>";
    
    echo "-----CleanUpPlayerById---------------------------------------<br />";
}

?>
