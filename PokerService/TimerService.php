<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../Libraries/log4php/Logger.php');
include_once(dirname(__FILE__) . '/../../Libraries/Helper/DataHelper.php');

// Include Application Scripts
require_once('Config.php');
include_once('Data/AllInclude.php');
include_once('DomainModel/AllInclude.php');
include_once('Dto/AllInclude.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/log4php.xml');
$log = Logger::getLogger("TimerService");
/*
 * Script run by the scheduled job.
 *
 */
/* * ***************************************************************************************************** */

/**
 * checks the expiration for every entry in the NextPokerMove table
 * inserts a message in EventMessage if a turn expired ans is skipped.
 *
 */
function cleanUp() {
    global $log;
    global $dateTimeFormat;
    global $moveTimeOut;
    
    $con = connectToStateDB();
    $expirationDateTime = new DateTime();
    $expirationDateTime->sub(new DateInterval($moveTimeOut)); // 20 minutes
    $expirationString = $expirationDateTime->format($dateTimeFormat);

    // delete really old actions and events (5 minutes ago);
    $result = executeSQL("DELETE FROM EventMessage WHERE EventDateTime < '$expirationString'", __FUNCTION__ . "
        : Error deleting messages older than 5 minutes.");
    $log->warn(__FUNCTION__ . " Deleted events older than 5 minutes: " . mysql_affected_rows());

    $result = executeSQL("DELETE FROM NextPokerMove WHERE ExpirationDate < '$expirationString'", __FUNCTION__ . "
        : Error deleting next moves that expired over 5 minutes.");
    $log->warn(__FUNCTION__ . " Deleted moves that expired over 5 minutes ago: " . mysql_affected_rows());
}

function checkExpiration() {
    global $dateTimeFormat;
    global $numberSeats;
    global $log;

    $con = connectToStateDB();

    $statusDateTime = date($dateTimeFormat);
    //$currentTimeString = $statusDateTime->format($dateTimeFormat);
    // check if expiration
    $result = executeSQL("SELECT m.*, s.IsVirtual
            FROM NextPokerMove m LEFT JOIN PlayerState s
            ON m.gameInstanceId = s.GameInstanceId AND m.PlayerId = s.PlayerId
            WHERE ExpirationDate <= '$statusDateTime'
            AND IsDeleted = 0 ORDER BY GameInstanceId, ExpirationDate DESC", __FUNCTION__ . "
                : ERROR selecting all of NextPokerMove");
    // only the last record for every game instance id is processed
    // this won't be needed when only one move is stored (out of database)
    $counter = 0;
    echo mysql_num_rows($result) . " rows found. <br />";
    while ($row = mysql_fetch_array($result)) {
        // get instance data
        $gameInstanceId = $row["GameInstanceId"];
        $gameInstance = EntityHelper::getGameInstance($gameInstanceId);
        $previousNumberCards = $gameInstance->numberCommunityCardsShown;

        // Validate instance
        // FIXME : should be logged
        if (is_null($gameInstance) || !is_null($gameInstance->winningPlayerId)) {
            executeSQL("UPDATE NextPokerMove SET IsDeleted = 1 WHERE gameInstanceId = $gameInstanceId",
                    __FUNCTION__ . "
            : Error soft deleting previous instance id $gameInstanceId moves");
            executeSQL("UPDATE EventMessage SET IsDeleted = 1 WHERE gameInstanceId = $gameInstanceId",
                    __FUNCTION__ . "
            : Error soft deleting previous instance id $gameInstanceId moves");
            continue;
        }
        $counter++;

        // get move data
        $isPractice = $row["IsPractice"];
        $playerId = $row["PlayerId"];
        $isPlayerVirtual = $row["IsVirtual"];
        $isExpired = 1; // practice player does not expire
        $checkAmount = $row["CheckAmount"];
        $callAmount = $row["CallAmount"];
        $raiseAmount = $row["RaiseAmount"];

        $log->warn(__FUNCTION__ . " - Expired move for Game instance " . json_encode($gameInstance));
        if ($isPlayerVirtual == 1) {
            $practiceSession = new PracticeSession(null, null, null);
            $playerAction = $practiceSession->generateRandomAction($playerId, $gameInstanceId,
                    $statusDateTime, $checkAmount, $callAmount, $raiseAmount);
        } else {
            $playerAction = new PlayerActionDto($gameInstanceId, $row["PlayerId"],
                            null, $row["ExpirationDate"], null);
            $casinoTable = EntityHelper::getCasinoTableForSession($gameInstance->gameInstanceSetup->gameSessionId);
        }
        $log->warn(__FUNCTION__ . " - PlayerAction is " . json_encode($playerAction) . "<br />");
        $playerTurn = new PlayerTurn($playerAction, $gameInstance, $statusDateTime);

        if ($isPlayerVirtual == 1) {
            $nextPokerMove = $playerTurn->applyPlayerAction();
            $isExpired = 0;
        } else {
            $nextPokerMove = $playerTurn->skipTurn($row["Id"], $isPractice, $statusDateTime);
            if ($playerTurn->playerInstanceStatus->numberTimeOuts >=3) {
                $casinoTable->ejectPlayer($playerId, $statusDateTime);
            }
        }
        $resultDto = $gameInstance->followUpPlayerTurn($nextPokerMove,
                $playerAction->playerId,
                $playerTurn->playerInstanceStatus->playerPlayNumber, $statusDateTime);
        $resultDto->playerStatusDto = new PlayerStatusDto($playerTurn->playerInstanceStatus);

        $gameInstance->communicateMoveResult($resultDto, $isExpired);
    }
    return $counter;
}

/**
 * Gets player states on active instances with no activity in the last 2 minutes
 * Should rarely find a player because of time out.
 *
 * THIS MAY BE EXPENSIVE, all these entities should be in memory because active.
 */
function ejectInactivePlayer(){
    global $playerTimeOut;
    global $instanceTimeOut;
    global $log;
    $con = connectToStateDB();

    $statusDateTime = date($dateTimeFormat);
    
    $playerExpiration = new DateTime();
    $playerExpiration->sub(new DateInterval($playerTimeOut)); // 20 minutes
    $playerExpirationString = $playerExpiration->format($dateTimeFormat);
    $instanceExpiration = new DateTime();
    $instanceExpiration->sub(new DateInterval($instanceTimeOut)); // 20 minutes
    $instanceExpirationString = $instanceExpiration->format($dateTimeFormat);

    // inner join on casino table ensures only live games are processed.
   $result = executeSQL("SELECT ps.PlayerId AS PlayerId, c.ID as TableId From PlayerState ps
           INNER JOIN GameInstance i ON ps.GameInstanceId = i.ID
           INNER JOIN CasinoTable c on i.GameSessionId = c.CurrentGameSessionId
           WHERE i.LastUpdateDateTime < '$instanceExpirationString'
           AND ps.LastUpdateDateTime < '$playerExpirationString' LIMIT 10", __FUNCTION__ . ":
               ERROR selecting inactive players");
   while ($row = mysql_fetch_array($result)){
       $casinoTable = EntityHelper::getCasinoTable($row["TableId"]);
       $casinoTable->ejectPlayer($row["PlayerId"], $statusDateTime);
   }
}

/* * ***************************************************************************************************** */
/*
  checkExpiration();
  cleanUp();
*/
?>
