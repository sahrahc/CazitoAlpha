<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../Libraries/Helper/WebServiceDecoder.php');
include_once(dirname(__FILE__) . '/../../Libraries/Helper/DataHelper.php');
include_once(dirname(__FILE__) . '/../../Libraries/log4php/Logger.php');

// Include Application Scripts
require_once('DomainModel/EventMessage.php');
require_once('Config.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/log4php.xml');
$logE = Logger::getLogger(__FILE__);

$server = new WebServiceDecoder;

$server->initialize();

/* * ************************************************************************************** */

/**
 * Get the message from the queue. This operation supports polling by the browser for events. The return DTO may include multiple events, like game ended and seat became available. A message or event skipped is detected by the browser by comparing the player id who makes the move. Players who get skipped generate an event so player id turns should reliably detect missing events. If an event is missed, the browser must get the status for everyone (getGameStatus).
 * The queue is temporarily a table.
 * @param type $requestingPlayerId
 * @param type $gameSessionId
 */
function getMessage($par) {
    global $logE;
    $decodedPar = json_decode($par, true);
    $gameSessionId = $decodedPar["gameSessionId"];
    $requestingPlayerId = $decodedPar["requestingPlayerId"];
    $con = connectToStateDB();

    $result = executeSQL("SELECT * FROM EventMessage WHERE GameSessionId = $gameSessionId
            AND TargetPlayerId = $requestingPlayerId AND
            IsDeleted = 0 ORDER BY EventDateTime DESC", __FUNCTION__ . "
            : Error retrieving EventMessage for player $requestingPlayerId and session id
            $gameSessionId");
    if (mysql_num_rows($result) == 0) {
        $status = "None";
        return json_encode($status);
    }
    $row = mysql_fetch_array($result);
    $eventMessage = new EventMessage($row["GameSessionId"], $row["TargetPlayerId"],
                    $row["EventType"], $row["EventDateTime"], $row["JsonEvent"]);
    $eventMessage->id = $row["Id"];
    $logE->debug(__FUNCTION__ . " - Event ID retrieved is " . $row["Id"]);
//$eventMessage->nextPlayerId = $row["NextPlayerId"];

    removeMessage($eventMessage->id);
    return json_encode($eventMessage);
}

/**
 * Mark a message as read. This is not needed with a queue.
 * Temporary function so as to not place in EntityHelper until queue ready.
 * @param type $messageId
 */
function removeMessage($messageId) { //$par) {
    //$decodedPar = json_decode($par, true);
    //$messageId = $decodedPar["MessageId"];
    $con = connectToStateDB();

    executeSQL("UPDATE EventMessage SET IsDeleted = 1 WHERE ID = $messageId", __FUNCTION__ . "
        :Error setting deleting flag on EventMessage where id $messageId");
}

/* * ************************************************************************************** */
$server->register("getMessage");
$server->register("removeMessage");
/*
// fixme: convert to POST
$method = $_GET["method"];
$param = $_GET["param"];
$server->serve($method, $param);
*/

 ?>
