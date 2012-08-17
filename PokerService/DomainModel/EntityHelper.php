<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

/* * ************************************************************************************* */

/**
 * Helper class for retrieving entities without instantiating objects.
 */
class EntityHelper {

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /**
     * Retrieves the casino table given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
     * @param int $cTableId
     * @return CasinoTable
     */
    public static function getCasinoTable($cTableId) {
        global $dateTimeFormat;
        global $sessionExpiration;
        if (is_null($cTableId) || $cTableId == "") {
            return null;
        }
        $result = executeSQL("SELECT * FROM CasinoTable WHERE Id = $cTableId", __FUNCTION__ .
                ": Error selecting from CasinoTable casino $cTableId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $obj = new CasinoTable();
        $obj->id = $row["Id"];
        $obj->name = $row["Name"];
        $obj->tableMinimum = $row["TableMinimum"];
        $obj->numberSeats = $row["NumberSeats"];
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->currentGameSessionId = $row["CurrentGameSessionId"];
        $obj->sessionStartDateTime = $row["SessionStartDateTime"];
        //$obj->isSessionStale = false;
        
        $result = executeSQL("SELECT LastUpdateDateTime FROM GameInstance
                WHERE GameSessionId = $obj->currentGameSessionId ORDER BY StartDateTime DESC
                ", __FUNCTION__ . ":Error selecting from GameInstance session id
                $obj->currentGameSessionId");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            // expiration date time is 24 hours after the last update
            $expirationDateTime = DateTime::createFromFormat($dateTimeFormat, $row[0]);
            $expirationDateTime->add(new DateInterval($sessionExpiration)); // 24 hours
            self::log(" Last Update " . json_encode($expirationDateTime));
            $obj->isSessionStale = new DateTime() > $expirationDateTime ? true : false;
        }
        return $obj;
    }

    /**
     * Get the game instance object given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
     * @param int $gInstId
     * @return GameInstance
     */
    public static function getGameInstance($gInstId) {
        if (is_null($gInstId)) {
            return null;
        }

        // left join on casino table and practice session
        $result = executeSQL("SELECT g.*, c.TableMinimum AS CasinoMin, s.TableMinimum AS
                PracticeMin, dp.TurnNumber AS DealerTurnNumber, np.TurnNumber AS NextTurnNumber
                FROM GameInstance g
                LEFT JOIN PlayerState dp ON g.ID = dp.GameInstanceId
                    AND g.DealerPlayerId = dp.PlayerId
                LEFT JOIN PlayerState np ON g.ID = np.GameInstanceId
                    AND g.NextPlayerId = np.PlayerId
                LEFT JOIN CasinoTable c ON g.GameSessionId = c.CurrentGameSessionId
                LEFT JOIN GameSession s on g.GameSessionId = s.Id
                WHERE g.Id = $gInstId", __FUNCTION__ . ": Error selecting GameInstance instance id $gInstId");

        if (mysql_num_rows($result) == 0) {
            return null;
        }

        $row = mysql_fetch_array($result);
        $obj = new GameInstanceStatus($row["Id"]);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->nextPlayerId = $row["NextPlayerId"];
        $obj->nextTurnNumber = $row["NextTurnNumber"]; // via join
        $obj->potSize = $row["PotSize"];
        $obj->lastBetSize = $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'];

        $obj->gameInstanceSetup = new GameInstanceSetup($row["Id"], $row["GameSessionId"]);
        $obj->gameInstanceSetup->isPractice = $row["IsPractice"];
        $obj->gameInstanceSetup->startDateTime = $row["StartDateTime"];
        $obj->gameInstanceSetup->tableMinimum = $row["CasinoMin"]; // via join
        $obj->gameInstanceSetup->numberPlayers = $row["NumberPlayers"];
        if (is_null($obj->gameInstanceSetup->tableMinimum)) {
            $obj->gameInstanceSetup->tableMinimum = $row["PracticeMin"];
        }
        $obj->gameInstanceSetup->dealerPlayerId = $row["DealerPlayerId"];
        $obj->gameInstanceSetup->dealerTurnNumber = $row["DealerTurnNumber"]; // via join
        $obj->gameInstanceSetup->firstPlayerId = $row["FirstPlayerId"];
        return $obj;
    }

    /**
     * Get a game session's last instance, including the last dealer, bet size and pot size.
     * @param int $gSessionId
     * @return GameInstanceStatus
     */
    public static function getSessionLastInstance($gSessionId) {
        // left join on casino table and practice session
        $result = executeSQL("SELECT g.*, c.TableMinimum AS CasinoMin, s.TableMinimum AS
                PracticeMin, dp.TurnNumber AS DealerTurnNumber, np.TurnNumber AS NextTurnNumber
                FROM GameInstance g
                LEFT JOIN PlayerState dp ON g.ID = dp.GameInstanceId
                    AND g.DealerPlayerId = dp.PlayerId
                LEFT JOIN PlayerState np on g.ID = np.GameInstanceId
                    AND g.NextPlayerId = np.PlayerId
                LEFT JOIN CasinoTable c ON g.GameSessionId = c.CurrentGameSessionId
                LEFT JOIN GameSession s on g.GameSessionId = s.Id
                WHERE g.GameSessionId = $gSessionId ORDER BY StartDateTime DESC LIMIT 1
              ", __FUNCTION__ . ": Error selecting last instance with session id $gSessionId");

        if (mysql_num_rows($result) == 0) {
            return null;
        }

        $row = mysql_fetch_array($result);
        $obj = new GameInstanceStatus($row["Id"]);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->nextPlayerId = $row["NextPlayerId"];
        $obj->nextTurnNumber = $row["NextTurnNumber"]; // via join
        $obj->potSize = $row["PotSize"];
        $obj->lastBetSize = $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'];

        $obj->gameInstanceSetup = new GameInstanceSetup($row["Id"], $row["GameSessionId"]);
        $obj->gameInstanceSetup->isPractice = $row["IsPractice"];
        $obj->gameInstanceSetup->startDateTime = $row["StartDateTime"];
        $obj->gameInstanceSetup->tableMinimum = $row["CasinoMin"]; // via join
        if (is_null($obj->gameInstanceSetup->tableMinimum)) {
            $obj->gameInstanceSetup->tableMinimum = $row["PracticeMin"];
        }
        $obj->gameInstanceSetup->numberPlayers = $row["NumberPlayers"];
        $obj->gameInstanceSetup->dealerPlayerId = $row["DealerPlayerId"];
        $obj->gameInstanceSetup->dealerTurnNumber = $row["DealerTurnNumber"]; // via join
        $obj->gameInstanceSetup->firstPlayerId = $row["FirstPlayerId"];
        return $obj;
    }

    /**
     * Send the player hand information for every non virtual player at the beginning of the game.
     * @param type $playerId The player whose hand is being sent
     */
    public static function getUserHand($playerId, $gameInstanceId) {
        $result = executeSQL("SELECT * from GAMECARD where GameInstanceId = $gameInstanceId
                AND PlayerId = $playerId ORDER BY CardNumber", __FUNCTION__ . "
                : ERROR selecting game card for game instance id $gameInstanceId and player
                id $playerId");
        if (mysql_num_rows($result) == 0) {return null;}

        $row = mysql_fetch_array($result);
        $pokerCard1 = new PokerCard($row["CardNumber"], $row["CardIndex"], $row["CardName"]);

        $row = mysql_fetch_array($result);
        $pokerCard2 = new PokerCard($row["CardNumber"], $row["CardIndex"], $row["CardName"]);

        return new PlayerHand($playerId, $pokerCard1, $pokerCard2);
    }

    /**
     * Retrieve a player given the player id or null if not found. Exception handling in that case by calling function.
     * @param int $playerId
     * @param int $cTableId
     * @return PlayerDto
     */
    public static function getPlayer($playerId) {
        if (is_null($playerId)) {
            return null;
        }

        $result = executeSQL("SELECT * FROM Player WHERE Id = $playerId", __FUNCTION__ . ": Error selecting Player with player id $playerId");

        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $obj = new PlayerDto($row["Id"], $row['Name'], $row['ImageUrl'], null,
                        $row["BuyIn"]);
        $obj->casinoTableId = $row["CurrentCasinoTableId"];
        $obj->currentSeatNumber = $row['CurrentSeatNumber'];
        $obj->reservedSeatNumber = $row['ReservedSeatNumber'];
        $obj->isVirtual = $row['IsVirtual'];

        return $obj;
    }

    /**
     * Get all the players instance setup and status for a game instance
     * @param int gInstId
     * @return PlayerInstanceStatus[]
     */
    public static function getPlayerInstancesForGame($gInstId) {
        // sorted by seat number
        $result = executeSQL("SELECT p.Name AS Name, p.ImageURL as ImageUrl, ps.*
                FROM PlayerState ps INNER JOIN Player p ON ps.Playerid = p.Id
                WHERE GameInstanceId = $gInstId ORDER BY TurnNumber", __FUNCTION__ . ": ERROR loading PlayerStates with instance id $gInstId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $playerInstances = null;
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerInstances[$i] = new PlayerInstanceStatus();
            $playerInstances[$i]->playerId = $row["PlayerId"];
            $playerInstances[$i]->gameInstanceId = $row["GameInstanceId"];
            $playerInstances[$i]->lastUpdateDateTime = $row["LastUpdateDateTime"];
            $playerInstances[$i]->status = $row["Status"];
            $playerInstances[$i]->stake = $row["Stake"];
            $playerInstances[$i]->lastPlayAmount = $row["LastPlayAmount"];
            $playerInstances[$i]->playerPlayNumber = $row["PlayerPlayNumber"];
            $playerInstances[$i]->numberTimeOuts = $row["NumberTimeOuts"];

            $playerInstances[$i]->playerInstanceSetup = new PlayerInstanceSetup();
            $playerInstances[$i]->playerInstanceSetup->playerId = $row["PlayerId"];
            $playerInstances[$i]->playerInstanceSetup->isVirtual = $row["IsVirtual"];
            $playerInstances[$i]->playerInstanceSetup->playerName = $row["Name"];
            $playerInstances[$i]->playerInstanceSetup->playerImageUrl = $row["ImageUrl"];
            $playerInstances[$i]->playerInstanceSetup->gameSessionId = $row["GameSessionId"];
            $playerInstances[$i]->playerInstanceSetup->gameInstanceId = $row["GameInstanceId"];
            $playerInstances[$i]->playerInstanceSetup->seatNumber = $row["SeatNumber"];
            $playerInstances[$i]->playerInstanceSetup->turnNumber = $row["TurnNumber"];
            $playerInstances[$i]->playerInstanceSetup->blindBet = $row["BlindBet"];
            $i++;
        }
        return $playerInstances;
    }

    /**
     * Get a player's instance given the player and instance identifiers.
     * @param type $gInstId
     * @param type $pId
     * @return PlayerInstanceStatus
     */
    public static function getPlayerInstance($gInstId, $pId) {
        // sorted by seat number
        $result = executeSQL("SELECT p.Name AS Name, p.ImageURL as ImageUrl, ps.*
                FROM PlayerState ps INNER JOIN Player p ON ps.Playerid = p.Id
                WHERE GameInstanceId = $gInstId and PlayerId = $pId ORDER BY SeatNumber", __FUNCTION__ . ": ERROR loading PlayerState with instance id $gInstId
                and player id = $pId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $playerStatus = null;
        $i = 0;
        $row = mysql_fetch_array($result);
        $playerStatus = new PlayerInstanceStatus();
        $playerStatus->playerId = $row["PlayerId"];
        $playerStatus->gameInstanceId = $row["GameInstanceId"];
        $playerStatus->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $playerStatus->status = $row["Status"];
        $playerStatus->stake = $row["Stake"];
        $playerStatus->lastPlayAmount = $row["LastPlayAmount"];
        $playerStatus->playerPlayNumber = $row["PlayerPlayNumber"];
        $playerStatus->numberTimeOuts = $row["NumberTimeOuts"];

        $playerStatus->playerInstanceSetup = new PlayerInstanceSetup();
        $playerStatus->playerInstanceSetup->playerId = $row["PlayerId"];
        $playerStatus->playerInstanceSetup->isVirtual = $row["IsVirtual"];
        $playerStatus->playerInstanceSetup->playerName = $row["Name"];
        $playerStatus->playerInstanceSetup->playerImageUrl = $row["ImageUrl"];
        $playerStatus->playerInstanceSetup->gameSessionId = $row["GameSessionId"];
        $playerStatus->playerInstanceSetup->gameInstanceId = $row["GameInstanceId"];
        $playerStatus->playerInstanceSetup->seatNumber = $row["SeatNumber"];
        $playerStatus->playerInstanceSetup->turnNumber = $row["TurnNumber"];
        $playerStatus->playerInstanceSetup->blindBet = $row["BlindBet"];

        return $playerStatus;
    }

    public static function getPlayerStatusDtosForInstance($gInstId) {
        // sorted by seat number
        $result = executeSQL("SELECT p.Name AS Name, p.ImageURL as ImageUrl, ps.*
                FROM PlayerState ps INNER JOIN Player p ON ps.Playerid = p.Id
                WHERE GameInstanceId = $gInstId ORDER BY SeatNumber", __FUNCTION__ . ": ERROR loading PlayerStates with instance id $gInstId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $playerStatusDtos = null;
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerStatusDtos[$i] = new PlayerStatusDto(null);
            $playerStatusDtos[$i]->playerId = $row["PlayerId"];
            $playerStatusDtos[$i]->playerName = $row["Name"];
            $playerStatusDtos[$i]->playerImageUrl = $row["ImageUrl"];
            $playerStatusDtos[$i]->seatNumber = $row["SeatNumber"];
            $playerStatusDtos[$i]->status = $row["Status"];
            $playerStatusDtos[$i]->blindBet = $row["BlindBet"];
            $playerStatusDtos[$i]->stake = $row["Stake"];
            $playerStatusDtos[$i]->playAmount = $row["LastPlayAmount"];
            $playerStatusDtos[$i]->playerPlayNumber = $row["PlayerPlayNumber"];
            $i++;
        }
        return $playerStatusDtos;
    }

    /**
     * Given a game session, get the casino table entity.
     * @param int $gSessionId
     * @return CasinoTable
     */
    public static function getCasinoTableForSession($gSessionId) {
        $result = executeSQL("SELECT * FROM CasinoTable
                WHERE CurrentGameSessionId = $gSessionId", __FUNCTION__ . ": ERROR selecting from CasinoTable session id $gSessionId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $obj = new CasinoTable();
        $obj->id = $row["Id"];
        $obj->name = $row["Name"];
        $obj->tableMinimum = $row["TableMinimum"];
        $obj->numberSeats = $row["NumberSeats"];
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->currentGameSessionId = $row["CurrentGameSessionId"];
        $obj->sessionStartDateTime = $row["SessionStartDateTime"];

        return $obj;
    }

    /*     * ****************************************************************************** */
    /* Operations for creating entities */

    /**
     * Retrieves the casino table but if not found creates one with default values. Generate unique table names by appending the id to the word Table by default.
     * When a new casino table is created, a session is automatically created as well.
     * @global type $defaultTableMin
     * @param type $cTableId
     * @param type $statusDT
     * @return CasinoTable
     */
    public static function getOrCreateCasinoTable($cTableId, $statusDT, $tableSize) {
        global $numberSeats;
		global $defaultTableMin;
        $casinoTable = self::getCasinoTable($cTableId);
		if (is_null($tableSize)) {
			$tableSize = $defaultTableMin;
		}
        if (is_null($casinoTable)) {
            // resetting the id
            $nextTableId = getNextSequence('CasinoTable', 'Id');
            $gameSessionId = getNextSequence('GameSession', 'Id');
            // make the table name unique by attaching the id for now
            $tableName = "Table" . $nextTableId;
            // TODO: need business rules for setting table minimums.
            executeSQL("INSERT INTO CasinoTable (Id, Name, TableMinimum, NumberSeats, 
                    LastUpdateDateTime, CurrentGameSessionId, SessionStartDateTime) VALUES 
                    ($nextTableId, '$tableName', $tableSize, $numberSeats, '$statusDT',
                    $gameSessionId, '$statusDT')", __FUNCTION__ .
                    ": Error inserting into casino with generated id $nextTableId");
            executeSQL("INSERT INTO GameSession (Id, TableMinimum, NumberSeats, StartDateTime,
                    IsPractice) VALUES ($gameSessionId, $tableSize, $numberSeats,
                    '$statusDT', 0)", __FUNCTION__ .
                    ": Error inserting into GameSession with generated id $gameSessionId");
            $casinoTable = new CasinoTable();
            $casinoTable->id = $nextTableId;
            $casinoTable->name = $tableName;
            $casinoTable->tableMinimum = $tableSize;
            $casinoTable->numberSeats = $numberSeats;
            $casinoTable->lastUpdateDateTime = $statusDT;
            $casinoTable->currentGameSessionId = $gameSessionId;
            $casinoTable->sessionStartDateTime = $statusDT;
            // $casinoTable->isSessionStale = false; null
        }
        return $casinoTable;
    }

    /**
     * Get a player by player name or create one if now found. If the player name is Guest, then append the identifier to Guest to make it unique. If the player exists but the casino id is different than given, update to given. This is a temporary measure until business rules for table management are defined.
     * @global type $defaultTableMin
     * @param type $casinoTableId
     * @param string $playerName
     * @param type $statusDT
     * @return PlayerDto
     */
    public static function getOrCreatePlayer($cTable, $seatNum, $playerName, $isVirtual, $statusDT) {
        global $defaultTableMin;
        global $buyInMultiplier;
        global $defaultAvatarUrl;
        
        $casinoTableId = 'null';
        $tableSize = $defaultTableMin;
        if ($cTable != null) {
            $casinoTableId = $cTable->id;
            $casinoTableId = is_null($casinoTableId) ? 'null' : $casinoTableId;
            $tableSize = $cTable->tableMinimum;
        }

        $seatValue = $seatNum;
        $waitingStartDT = "null";
        // set seat
        if (is_null($seatNum)) {
            $seatValue = "null";
            $waitingStartDT = $statusDT;
        }
        // if the player name provided is guest, a new player name is randomly generated
        if ($playerName != 'Guest') {
            $result = executeSQL("SELECT * FROM Player WHERE Name = '$playerName'", __FUNCTION__ . ": Error selecting from Player with name $playerName ");
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $playerId = $row["Id"];
                self::log()->debug(__FUNCTION__ . ": player found $playerId");
                self::log()->debug(__FUNCTION__ . ": new casino table id $casinoTableId
                        previous " . $row["CurrentCasinoTableId"]);
                $obj = new PlayerDto($row["Id"], $row['Name'], $row['ImageUrl'],
                                $row['CurrentSeatNumber'], $row["BuyIn"]);
                $obj->isVirtual = $row['IsVirtual'];
                if (!is_null($cTable)) {
                    $obj->casinoTableId = $cTable->id;
                }
                $tempCasinoId = is_null($row["CurrentCasinoTableId"]) ? 'null' :
                        $row["CurrentCasinoTableId"];
                if ($tempCasinoId != $casinoTableId) {
                    $cTable->ejectPlayer($playerId, $statusDT);

                    // update player's casino table
                    $stake = $tableSize * $buyInMultiplier;
                    executeSQL("UPDATE Player SET CurrentCasinoTableId = $casinoTableId,
                            CurrentSeatNumber = $seatValue, ReservedSeatNumber = null,
                            WaitStartDateTime = '$statusDT',
                            BuyIn = $stake, LastUpdateDateTime = '$statusDT'
                            WHERE Id = $playerId", __FUNCTION__ . ": Error updating Player player id $playerId");
                    self::log()->warn(__FUNCTION__ . ": Updated casino id for player id
                            $playerId when getting player");
                    $obj->casinoTableId = $cTable->id;
                    $obj->currentSeatNumber = $seatNum;
                    $obj->reservedSeatNumber = null;
                    $obj->buyin = $stake;
                }
                return $obj;
            }
        }

        // player not found, create player
        $nextPlayerId = getNextSequence('Player', 'Id');
        if ($playerName == 'Guest') {
            $playerName = 'Guest' . $nextPlayerId;
        }
        $buyIn = $tableSize * $buyInMultiplier;
        $imageUrl = $defaultAvatarUrl;
        $seatNumber = $seatNum;
        if (is_null($seatNum)) {
            $seatNumber = "null"; // version for sql insert
        }
        executeSQL("INSERT INTO Player (Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId, 
            CurrentSeatNumber, BuyIn, LastUpdateDateTime, WaitStartDateTime)
            VALUES ($nextPlayerId, $isVirtual, '$playerName', '$imageUrl', $casinoTableId,
                $seatNumber, $buyIn, '$statusDT', '$statusDT')", __FUNCTION__ . ": Error
                inserting Player generated id $nextPlayerId for table id $casinoTableId");
        $obj = new PlayerDto($nextPlayerId, $playerName, $imageUrl, $seatNum, $buyIn);
        $obj->casinoTableId = $casinoTableId;
        $obj->isVirtual = $isVirtual;
        return $obj;
    }

    /**
     * Only practice sessions and instances are created this way.
     * @param type $gameInstanceId
     * @param type $statusDT
     * @return GameInstanceStatus
     */
    public static function createPracticeInstance($gSessionId, $playerId, $statusDT) {
        global $defaultTableMin;
        global $buyInMultiplier;
        global $numberSeats;

        $stake = $defaultTableMin * $buyInMultiplier;
        $sessionId = $gSessionId;
        if (is_null($gSessionId)) {
            // resetting the id
            $sessionId = getNextSequence('GameSession', 'Id');
            executeSQL("INSERT INTO GameSession (Id, StartDateTime, TableMinimum,
                    NumberSeats, IsPractice) VALUES($sessionId, '$statusDT', $defaultTableMin,
                    $numberSeats, 1)", __FUNCTION__ . "
                    : Error inserting GameSession with generated id $sessionId");
        }
        $nextInstanceId = getNextSequence('GameInstance', 'Id');
        $potSize = $defaultTableMin * 1.5;
        // dealer, first and next player id set after this method.
        executeSQL("INSERT INTO GameInstance (Id, GameSessionId, IsPractice, StartDateTime, 
                LastUpdateDateTime, NumberPlayers, DealerPlayerId, FirstPlayerId, NextPlayerId,
                PotSize, LastBetSize, NumberCommunityCardsShown, LastInstancePlayNumber) VALUES
                ($nextInstanceId, $sessionId, 1, '$statusDT', '$statusDT',
                $numberSeats, null, null, null, $potSize, $defaultTableMin, 0, 0)", __FUNCTION__ .
                ": Error insert into game instance with generated id $nextInstanceId");

        $gameInstance = new GameInstanceStatus($nextInstanceId);
        $gameInstance->lastUpdateDateTime = $statusDT;
        // not setting dealer id and turn
        $gameInstance->potSize = $potSize;
        $gameInstance->lastBetSize = $defaultTableMin;
        $gameInstance->numberCommunityCardsShown = 0;
        $gameInstance->lastInstancePlayNumber = 0;

        $gameInstance->gameInstanceSetup = new GameInstanceSetup($gameInstance->id, $sessionId);
        $gameInstance->gameInstanceSetup->isPractice = 1;
        $gameInstance->gameInstanceSetup->startDateTime = $statusDT;
        $gameInstance->gameInstanceSetup->tableMinimum = $defaultTableMin;
        $gameInstance->gameInstanceSetup->numberPlayers = 4;

        return $gameInstance;
    }

    /**
     * Retrieves the next move from the queue. Log warning if more than one found.
     * @param type $gInstanceId
     * @return NextPokerMove
     */
    public static function getNextMoveForInstance($gInstanceId) {
        $result = executeSQL("SELECT * FROM NextPokerMove WHERE GameInstanceId = $gInstanceId
                AND IsDeleted = 0 ORDER BY ExpirationDate DESC LIMIT 1", __FUNCTION__ . "
                : Error selecting from NextPokerMove for instance $gInstanceId");
        $row = mysql_fetch_array($result);
        $exceptionMsg = null;
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $nextMove = new NextPokerMove();
        $nextMove->id = $row["Id"];
        $nextMove->gameInstanceId = $row["GameInstanceId"];
        $nextMove->isPractice = $row["IsPractice"];
        $nextMove->playerId = $row["PlayerId"];
        $nextMove->turnNumber = $row["TurnNumber"];
        $nextMove->expirationDate = $row["ExpirationDate"];
        $nextMove->isEndGameNext = $row["IsEndGameNext"];
        $nextMove->callAmount = $row["CallAmount"];
        $nextMove->checkAmount = $row["CheckAmount"];
        $nextMove->raiseAmount = $row["RaiseAmount"];
        return $nextMove;
    }

}

?>
