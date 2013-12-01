<?php
include_once('../Config.php');
    
executeSQL("DELETE FROM GameCard", "Error deleting GameCard");
executeSQL("DELETE FROM ExpectedPokerMove","Error deleting ExpectedPokerMove");
executeSQL("DELETE FROM GameInstance", "Error deleting GameInstance");
executeSQL("DELETE FROM PlayerState", "Error deleting PlayerState");
executeSQL("DELETE FROM GameSession", "Error deleting GameSession");
executeSQL("DELETE FROM CasinoTable", "Error deleting CasinoTable");
executeSQL("DELETE FROM Player", "Error deleting Player");
executeSQL("DELETE FROM PlayerActiveItem", "Error deleting PlayerActiveItem");
executeSQL("DELETE FROM PlayerHiddenCard", "Error deleting PlayerHiddenCard");
executeSQL("DELETE FROM PlayerAction", "Error deleting PlayerAction");
executeSQL("DELETE FROM PlayerVisibleCard", "Error deleting PlayerVisibleCard");
?>
