<?php

include(dirname(__FILE__) . '/../EventMessageService.php');
include_once(dirname(__FILE__) . '/../Metadata.php');
include('showObject.php');

/**********************************************************************************
 * Setup
 */
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

$con = connectToStateDB();
$result = executeSQL("SELECT m.GameSessionId, m.TargetPlayerId
    FROM EventMessage m
    INNER JOIN Player p ON p.id = m.TargetPlayerId
    WHERE p.Name = 'JP'
    ORDER BY m.GameSessionId desc", 'ERROR');
$row = mysql_fetch_array($result);
$gameSessionId = $row[0];
$playerId = $row[1];

echo '******************************************************<br />';
echo 'TEST CASE 10.1: get message for MM <br />';

$par = json_encode(array("gameSessionId"=>$gameSessionId, "requestingPlayerId"=>$playerId));
echo "parameter in $par";
if ($playerId != null) {
$message = getMessage($par);
showEventMessage($par, $message);

echo '******************************************************<br />';
echo 'TEST CASE 10.2: remove message for MM <br />';
/*
$decodedMessage= json_decode($message);
$par=json_encode(array("MessageId"=>$decodedMessage->id));
removeMessage($par);
 * 
 */
}
?>
