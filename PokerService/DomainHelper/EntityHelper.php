<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
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
     * @global type $dateTimeFormat
     * @global type $sessionExpiration
     * @param type $cTableId
     * @return CasinoTable 
     */
    public static function getCasinoTable($cTableId) {
        global $dateTimeFormat;
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
        // sets the stale indicator flag if no active sessions for the table
        return $obj;
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

    /**
     * Get or create a casino table for a user when he joins a table. Getting or creating
     * separates the logic for checking if a casino table exists from a user joining a table.
     * When a new casino table is created, a session is automatically created as well.
     * @global type $numberSeats
     * @global type $defaultTableMin
     * @param type $cTableId
     * @param type $tableSize
     * @param type $statusDT
     * @return CasinoTable 
     */
    public static function getOrCreateCasinoTable($cTableId, $tableSize, $playerId) {
        global $numberSeats;
        global $defaultTableMin;
        $statusDT = Context::GetStatusDT();

        $casinoTable = self::getCasinoTable($cTableId);
        if (is_null($casinoTable)) {
            if (is_null($tableSize)) {
                $tableSize = $defaultTableMin;
            }
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
            executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
                TableMinimum, NumberSeats, StartDateTime,
                    IsPractice) VALUES ($gameSessionId, $playerId, $tableSize, $numberSeats,
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
        }
        return $casinoTable;
    }

    public static function GetGameSession($gameSessionId) {
        $result = executeSQL("SELECT * FROM GameSession WHERE Id = $gameSessionId", __FUNCTION__ . ": ERROR selecting from GameSession id $gameSessionId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $isPractice = $row["IsPractice"];
        if ($isPractice) {
            $gameSession = new PracticeSession($row["Id"], $row["RequestingPlayerId"]);
            $gameSession->tableMinimum = $row["TableMinimum"];
            $gameSession->numberSeats = $row["NumberSeats"];
        } else {
            $gameSession = new GameSession($row["Id"]);
        }
        return $gameSession;
    }

    public static function CreatePracticeSession($playerId) {
        global $numberSeats;
        global $defaultTableMin;

        $nextSessionId = getNextSequence('GameSession', 'Id');
        $gameSession = new PracticeSession($nextSessionId, $playerId);
        $gameSession->startDateTime = Context::GetStatusDT();
        $gameSession->numberSeats = $numberSeats;
        $gameSession->tableMinimum = $defaultTableMin;

        executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
            StartDateTime, TableMinimum, NumberSeats,
                IsPractice) VALUES ($gameSession->Id, $gameSession->requestingPlayerId,
                $gameSession->startDateTime, $gameSession->tableMinimum,
                $gameSession->numberSeats, $gameSession->isPractice), "
                , __FUNCTION__ . ": ERROR insert into game session");
    }

    /**
     * Returns all the active sessions, live and practice
     * @param type $casinoTableId
     * @return int[]
     */
    public static function GetActiveGameSessionIds() {
        global $dateTimeFormat;
        global $sessionExpiration;
        $expirationDateTime = DateTime::createFromFormat($dateTimeFormat, $row[0]);
        $expirationDateTime->add(new DateInterval($sessionExpiration)); // 24 hours

        $result = executeSQL("SELECT s.Id FROM GameSession s " .
                " LEFT JOIN CasinoTable c on c.CurrentGameSessionId = s.Id " .
                " INNER JOIN GameInstance i on s.GameInstanceId = i.Id" .
                " WHERE s.LastUpdateDateTime < '$expirationDateTime' " .
                ": Error selecting active practice and live game sessions");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $gameSessionIds = null;
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $gameSessionIds[$i] = $row["Id"];
        }
        return $gameSessionIds;
    }

    public static function GetPlayersForCasinoTable($cTableId) {
        $result = executeSQL("SELECT * FROM Player WHERE CurrentCasinoTableId = $cTableId
            ORDER BY CurrentSeatNumber", __FUNCTION__ . ": ERROR selecting from CasinoTable id $cTableId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $players[$i] = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
            $players[$i]->lastUpdateDateTime = $row["LastUpdateDateTime"];
            $players[$i]->casinoTableId = $row["CurrentCasinoTableId"];
            $players[$i]->currentSeatNumber = $row["CurrentSeatNumber"];
            $players[$i]->reservedSeatNumber = $row["ReservedSeatNumber"];
            $players[$i]->waitStartDateTime = $row["WaitStartDateTime"];
            $players[$i]->buyIn = $row["BuyIn"];

            $i++;
        }
        return $players;
    }

    /*     * ********************************************************************************* */

    /**
     * Get the game instance object given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
     * @param int $gInstId
     * @return GameInstance
     */
    public static function GetGameInstance($gInstId) {
        if (is_null($gInstId)) {
            return null;
        }

        // left join on casino table and practice session
        $result = executeSQL("SELECT g.* 
                FROM GameInstance g
                WHERE g.Id = $gInstId", __FUNCTION__ . ": Error selecting GameInstance instance id $gInstId");

        if (mysql_num_rows($result) == 0) {
            return null;
        }

        $row = mysql_fetch_array($result);
        $obj = new GameInstance($row["Id"]);
        $obj->gameSessionId = $row["GameSessionId"];
        $obj->status = $row["Status"];
        $obj->startDateTime = $row["StartDateTime"];
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->numberPlayers = $row["NumberPlayers"];
        $obj->dealerPlayerId = $row["DealerPlayerId"];
        $obj->firstPlayerId = $row["FirstPlayerId"];
        $obj->nextPlayerId = $row["NextPlayerId"];
        $obj->currentPotSize = $row["CurrentPotSize"];
        $obj->lastBetSize = $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'];

        return $obj;
    }

    /**
     * Get a game session's last instance, including the last dealer, bet size and pot size.
     * @param int $gSessionId
     * @return GameInstance
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
        $obj = new GameInstance($row["Id"]);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->nextPlayerId = $row["NextPlayerId"];
        $obj->nextTurnNumber = $row["NextTurnNumber"]; // via join
        $obj->potSize = $row["PotSize"];
        $obj->lastBetSize = $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'];

        $obj->gameSessionId = $row["GameSessionId"];
        $obj->isPractice = $row["IsPractice"];
        $obj->startDateTime = $row["StartDateTime"];
        $obj->tableMinimum = $row["CasinoMin"]; // via join
        if (is_null($obj->tableMinimum)) {
            $obj->tableMinimum = $row["PracticeMin"];
        }
        $obj->numberPlayers = $row["NumberPlayers"];
        $obj->dealerPlayerId = $row["DealerPlayerId"];
        $obj->dealerTurnNumber = $row["DealerTurnNumber"]; // via join
        $obj->firstPlayerId = $row["FirstPlayerId"];
        return $obj;
    }

    /**
     * Given a game instance for a practice session, return the player
     * @param gInstId
     * @return \Player
     * @throws Exception
     */
    public static function getPracticeInstancePlayer($gInstId) {

        $result = executeSQL("SELECT p.* FROM Player p
            INNER JOIN PlayerState s on p.id = s.PlayerId
            INNER JOIN GameSession g on g.id = s.GameSessionId
            WHERE g.IsPractice = 1 AND s.GameInstanceId = $gInstId", __FUNCTION__ . ": Error selecting Player on practice instance $gInstId");

        if (mysql_num_rows($result) == 0) {
            throw new Exception("Invalid game instance id");
        }
        $row = mysql_fetch_array($result);
        $obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        /* $obj->casinoTableId = $row["CurrentCasinoTableId"];
          $obj->currentSeatNumber = $row["CurrentSeatNumber"];
          $obj->reservedSeatNumber = $row["ReservedSeatNumber"];
          $obj->waitStartDateTime = $row["WaitStartDateTime"];
         */
        $obj->buyIn = $row["BuyIn"];

        return $obj;
    }

    /*     * ********************************************************************************* */

    /**
     * Retrieve a player given the player id or null if not found. Exception handling in that case by calling function.
     * @param int $playerId
     * @return Player, throws exception if not found
     */
    public static function getPlayer($playerId) {

        $result = executeSQL("SELECT * FROM Player WHERE Id = $playerId", __FUNCTION__ . ": Error selecting Player with player id $playerId");

        if (mysql_num_rows($result) == 0) {
            throw new Exception("Invalid user id");
        }
        $row = mysql_fetch_array($result);
        $obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $obj->casinoTableId = $row["CurrentCasinoTableId"];
        $obj->currentSeatNumber = $row["CurrentSeatNumber"];
        $obj->reservedSeatNumber = $row["ReservedSeatNumber"];
        $obj->waitStartDateTime = $row["WaitStartDateTime"];
        $obj->buyIn = $row["BuyIn"];

        return $obj;
    }

    /**
     *
     * @param type $playerName
     * @return Player
     */
    public static function getPlayerByName($playerName) {
        $result = executeSQL("SELECT * FROM Player WHERE Name = '$playerName'", __FUNCTION__ . "
                : Error selecting from Player with name $playerName ");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $playerId = $row["Id"];
            self::log()->debug(__FUNCTION__ . ": player found $playerId");
            $player = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
            $player->lastUpdateDateTime = $row["LastUpdateDateTime"];
            $player->casinoTableId = $row["CurrentCasinoTableId"];
            $player->currentSeatNumber = $row['CurrentSeatNumber'];
            $player->reservedSeatNumber = $row['ReservedSeatNumber'];
            $player->waitStartDateTime = $row["WaitStartDateTime"];
            $player->buyIn = $row["BuyIn"];
            return $player;
        }
        return null;
    }

    /**
     * Create a new player - returns a DTO.
     * @global type $defaultAvatarUrl
     * @param string $playerName
     * @param type $statusDT
     * @return Player
     */
    public static function createPlayer($playerName) {
        global $defaultAvatarUrl;
        $statusDT = Context::GetStatusDT();
        $nextPlayerId = getNextSequence('Player', 'Id');
        if ($playerName == 'Guest') {
            $playerName = 'Guest' . $nextPlayerId;
        }
        $imageUrl = $defaultAvatarUrl;

        executeSQL("INSERT INTO Player (Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId,
            CurrentSeatNumber, BuyIn, LastUpdateDateTime, WaitStartDateTime)
            VALUES ($nextPlayerId, 0, '$playerName', '$imageUrl', null,
                null, null, '$statusDT', '$statusDT')", __FUNCTION__ . ": Error
                inserting Player generated id $nextPlayerId");
        $player = new Player($nextPlayerId, $playerName, $imageUrl, 0);
        return $player;
    }

    /**
     * Creates a practice virtual player
     * @global type $defaultAvatarUrl
     * @global type $defaultTableMin
     * @global type $buyInMultiplier
     * @param type $playerName
     * @param type $seatNum
     * @param type $statusDT
     * @return Player
     */
    public static function createPracticePlayer($playerName, $seatNum) {
        global $defaultAvatarUrl;
        global $defaultTableMin;
        global $buyInMultiplier;

        $nextPlayerId = getNextSequence('Player', 'Id');
        $imageUrl = $defaultAvatarUrl;
        $buyIn = $defaultTableMin * $buyInMultiplier;
        $statusDT = Context::GetStatusDT();

        executeSQL("INSERT INTO Player (Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId,
            CurrentSeatNumber, BuyIn, LastUpdateDateTime, WaitStartDateTime)
            VALUES ($nextPlayerId, 1, '$playerName', '$imageUrl', null,
                $seatNum, $buyIn, '$statusDT', '$statusDT')", __FUNCTION__ . ": Error
                inserting Player generated id $nextPlayerId");
        $player = new Player($nextPlayerId, $playerName, $imageUrl, 1);
        $player->currentSeatNumber = $seatNum;
        $player->lastUpdateDateTime = $statusDT;
        $player->buyIn = $buyIn;
        return $player;
    }

    /**
     * Get a player by player name or create one if now found. If the player name is Guest,
     * then append the identifier to Guest to make it unique. This get or create operation separates the login logic from the player management logic.
     * @global type $defaultTableMin
     * @param type $casinoTableId
     * @param string $playerName
     * @param type $statusDT
     * @return Player
     */
    public static function getOrCreatePlayer($playerName, $statusDT) {
        $player = null;
        if ($playerName != 'Guest') {
            $player = self::getPlayerByName($playerName);
        }
        if (is_null($player)) {
            $player = self::createPlayer($playerName, $statusDT);
        }
        return $player;
    }

    /*     * ********************************************************************************* */

    /**
     * Get a player's instance given the player and instance identifiers.
     * @param type $gInstId
     * @param type $pId
     * @return PlayerInstance
     */
    public static function getPlayerInstance($gameId, $pId, $isSessionId = false) {
        if ($isSessionId) {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameSessionId = $gameId and PlayerId = $pId ORDER BY SeatNumber", __FUNCTION__ . ": ERROR loading PlayerState with instance id $gameId
                and player id = $pId");
        } else {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameInstanceId = $gameId and PlayerId = $pId ORDER BY SeatNumber", __FUNCTION__ . ": ERROR loading PlayerState with instance id $gameId
                and player id = $pId");
        }
        // sorted by seat number
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $playerStatus = new PlayerInstance();
        $playerStatus->playerId = $row["PlayerId"];
        $playerStatus->gameInstanceId = $row["GameInstanceId"];
        $playerStatus->isVirtual = $row["IsVirtual"];
        $playerStatus->gameSessionId = $row["GameSessionId"];
        $playerStatus->lastUpdateDateTime = $row["LastUpdateDateTime"];
        $playerStatus->seatNumber = $row["SeatNumber"];
        $playerStatus->turnNumber = $row["TurnNumber"];
        $playerStatus->status = $row["Status"];
        $playerStatus->currentStake = $row["CurrentStake"];
        $playerStatus->lastPlayAmount = $row["LastPlayAmount"];
        $playerStatus->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"];
        $playerStatus->numberTimeOuts = $row["NumberTimeOuts"];

        return $playerStatus;
    }

    /**
     * Get the player status Dtos for all players in an instance.
     * @param type $gInstId
     * @return PlayerStatusDto array
      public static function getPlayerStatusDtosForInstance($gameInstance, $addName = false) {
      // sorted by seat number
      $result = executeSQL("SELECT p.Name AS Name, p.ImageURL as ImageUrl, ps.*
      FROM PlayerState ps INNER JOIN Player p ON ps.Playerid = p.Id
      WHERE GameInstanceId = $gameInstance->id ORDER BY SeatNumber", __FUNCTION__ . ": ERROR loading PlayerStates with instance id $gameInstance->id");
      if (mysql_num_rows($result) == 0) {
      return null;
      }
      $playerStatusDtos = null;
      $i = 0;
      while ($row = mysql_fetch_array($result)) {
      $playerStatusDtos[$i] = new PlayerStatusDto();
      $playerStatusDtos[$i]->playerId = $row["PlayerId"];
      if ($addName) {
      $playerStatusDtos[$i]->playerName = $row["Name"];
      $playerStatusDtos[$i]->playerImageUrl = $row["ImageUrl"];
      }
      $playerStatusDtos[$i]->seatNumber = $row["SeatNumber"];
      $playerStatusDtos[$i]->status = $row["Status"];
      $playerStatusDtos[$i]->currentStake = $row["CurrentStake"];
      $playerStatusDtos[$i]->lastPlayAmount = $row["LastPlayAmount"];
      $playerStatusDtos[$i]->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"];
      $i++;
      }
      return $playerStatusDtos;
      }
     */
    /*     * ********************************************************************************* */
}

?>
