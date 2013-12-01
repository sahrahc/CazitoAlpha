<?php

// Description /////////////////////////////////////////////
// IN: param_casinoTableId
// OUT: param_playerId
//      param_gameSessionId
// 
// Setup ///////////////////////////////////////////////////
echo "------------------------------------------------------ <br />";
echo "Getting Latest Game Session Id for Casino Table  <br /><br />";

$casinoTableId = $_POST['param_casinoTableId'];
if (is_null($casinoTableId)) {
    echo "Missing required parameter param_casinoTableId <br /><br />";
    exit(1);    
}
if (is_null($conTest)) {
    $conTest = connectToStateDB();
}

////////////////////////////////////////////////////////////

$result = executeSQL("SELECT CurrentGameSessionId, p.id
    FROM Player p 
    INNER JOIN CasinoTable c on p.CurrentCasinoTableId = c.id
    WHERE CurrentCasinoTableId = $casinoTableId
    ORDER BY id desc", 'ERROR');
$row = mysql_fetch_array($result);

$_SESSION['param_gameSessionId'] = $row[0];
$_SESSION['param_playerId'] = $row[1];

?>
