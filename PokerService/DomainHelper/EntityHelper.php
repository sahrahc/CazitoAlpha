<?php

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

    private static function mapCasinoTableSqlRow($result) {
        global $dateTimeFormat;
        $row = mysql_fetch_array($result);
        $obj = new CasinoTable();
        $obj->id = (int) $row["Id"];
        $obj->name = $row["Name"];
		$obj->code = $row["Code"];
        $obj->tableMinimum = (int) $row["TableMinimum"];
        $obj->numberSeats = (int) $row["NumberSeats"];
        $obj->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
        $obj->currentGameSessionId = is_null($row["CurrentGameSessionId"]) ? null : (int) $row["CurrentGameSessionId"];
        $obj->sessionStartDateTime = is_null($row["SessionStartDateTime"]) ? null : DateTime::createFromFormat($dateTimeFormat, $row["SessionStartDateTime"]);
        return $obj;
    }

    /**
     * Retrieves the casino table given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
     * @global type $dateTimeFormat
     * @global type $sessionExpiration
     * @param type $cTableId
     * @return CasinoTable 
     */
    public static function getCasinoTable($cTableId) {
        if (is_null($cTableId) || $cTableId == "") {
            return null;
        }
        $result = executeSQL("SELECT * FROM CasinoTable WHERE Id = $cTableId", __FUNCTION__ .
                ": Error selecting from CasinoTable casino $cTableId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        return self::mapCasinoTableSqlRow($result);
    }

    /**
     * Retrieves the casino table given the identifier or null if not found. Exception handling if not found to be decided by the calling operation.
     * @global type $dateTimeFormat
     * @global type $sessionExpiration
     * @param type $cTableId
     * @return CasinoTable 
     */
    public static function getCasinoTableByCode($tableCode) {
        if (is_null($tableCode) || $tableCode == "") {
            return null;
        }
        $result = executeSQL("SELECT * FROM CasinoTable WHERE Code = '$tableCode'", __FUNCTION__ .
                ": Error selecting from CasinoTable casino $tableCode");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        return self::mapCasinoTableSqlRow($result);
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
        return self::mapCasinoTableSqlRow($result);
    }

    /**
     * Get or create a casino table for a user when he joins a table. Getting or creating
     * separates the logic for checking if a casino table exists from a user joining a table.
     * When a new casino table is created, a session is automatically created as well.
     * @global type $numberSeats
     * @global type $defaultTableMin
     * @param type $cTableId
     * @param type $betSize
     * @param type $statusString
     * @return CasinoTable 
     */
    public static function createCasinoTable($tableName, $tableCode, $betSize, $numberSeats, $playerId) {
        $statusString = Context::GetStatusDTString();

        // resetting the id
        $nextTableId = getNextSequence('CasinoTable', 'Id');
        $gameSessionId = getNextSequence('GameSession', 'Id');
        // TODO: need business rules for setting table minimums.
        executeSQL("INSERT INTO CasinoTable (Id, Name, Code, TableMinimum, NumberSeats,
                    LastUpdateDateTime, CurrentGameSessionId, SessionStartDateTime) VALUES
                    ($nextTableId, '$tableName', '$tableCode', $betSize, $numberSeats, 
                    '$statusString', $gameSessionId, '$statusString')", __FUNCTION__ .
                ": Error inserting into casino with generated id $nextTableId");
        executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
                TableMinimum, NumberSeats, StartDateTime, IsPractice,
                    IsActive) VALUES ($gameSessionId, $playerId, $betSize, $numberSeats,
                    '$statusString', 0, 1)", __FUNCTION__ .
                ": Error inserting into GameSession with generated id $gameSessionId");
        $casinoTable = new CasinoTable();
        $casinoTable->id = $nextTableId;
        $casinoTable->name = $tableName;
		$casinoTable->code = $tableCode;
        $casinoTable->tableMinimum = $betSize;
        $casinoTable->numberSeats = $numberSeats;
        $casinoTable->lastUpdateDateTime = Context::GetStatusDT();
        $casinoTable->currentGameSessionId = $gameSessionId;
        $casinoTable->sessionStartDateTime = Context::GetStatusDT();

        // creates queue for game session
        QueueManager::addGameSessionQueue($gameSessionId, Context::GetQCh());
        return $casinoTable;
    }

    public static function GetGameSession($gameSessionId) {
        $result = executeSQL("SELECT * FROM GameSession WHERE Id = $gameSessionId", __FUNCTION__ . ": ERROR selecting from GameSession id $gameSessionId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $isPractice = is_null($row["IsPractice"]) ? null : (int) $row["IsPractice"];
        if ($isPractice) {
            $gameSession = new PracticeSession($row["Id"], $row["RequestingPlayerId"]);
            $gameSession->tableMinimum = is_null($row["TableMinimum"]) ? null : (int) $row["TableMinimum"];
            $gameSession->numberSeats = is_null($row["NumberSeats"]) ? null : (int) $row["NumberSeats"];
        } else {
            $gameSession = new GameSession($row["Id"], $row["RequestingPlayerId"]);
        }
        $gameSession->isActive = is_null($row["IsActive"]) ? null : (int) $row["IsActive"];
        return $gameSession;
    }

    public static function CreatePracticeSession($playerId) {
        global $numberSeats;
        global $dateTimeFormat;
        global $defaultTableMin;

        $nextSessionId = getNextSequence('GameSession', 'Id');
        $gameSession = new PracticeSession($nextSessionId, $playerId);
        $gameSession->startDateTime = Context::GetStatusDT();
        $gameSession->numberSeats = $numberSeats;
        $gameSession->tableMinimum = $defaultTableMin;
        $startString = $gameSession->startDateTime->format($dateTimeFormat);

        executeSQL("INSERT INTO GameSession (Id, RequestingPlayerId,
            StartDateTime, TableMinimum, NumberSeats, IsActive,
                IsPractice) VALUES ($gameSession->id, $gameSession->requestingPlayerId,
                '$startString', $gameSession->tableMinimum, $gameSession->numberSeats, 
                $gameSession->isActive, $gameSession->isPractice) "
                , __FUNCTION__ . ": ERROR insert into game session");
        return $gameSession;
    }

    /**
     * Returns all the active sessions, live and practice
     * @param type $casinoTableId
     * @return int[]
     */
    public static function GetActiveGameSessionIds() {
        global $dateTimeFormat;
        global $sessionExpiration;
        $expirationDateTime = Context::GetStatusDT();
        $expirationDateTime->sub(new DateInterval($sessionExpiration)); // 24 hours
        $expString = $expirationDateTime->format($dateTimeFormat);

        $result = executeSQL("SELECT distinct(s.Id) Id FROM GameSession s " .
                " LEFT JOIN GameInstance i on s.Id = i.GameSessionId" .
                " WHERE (i.LastUpdateDateTime >= '$expString' OR " .
                " (s.StartDateTime >= '$expString' AND i.Id is null)) " .
				" AND s.IsActive = 1", 
				": Error selecting active practice and live game sessions");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $gameSessionIds = null;
        $i = 0;
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $gameSessionIds[$i++] = (int) $row["Id"];
        }
        return $gameSessionIds;
    }

    public static function GetPlayersForCasinoTable($cTableId) {
        global $dateTimeFormat;
        $result = executeSQL("SELECT * FROM Player WHERE CurrentCasinoTableId = $cTableId
            ORDER BY CurrentSeatNumber", __FUNCTION__ . ": ERROR selecting from CasinoTable id $cTableId");
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $i = 0;
        while ($row = mysql_fetch_array($result)) {
            $players[$i] = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
            $players[$i]->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
            $players[$i]->casinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
            $players[$i]->currentSeatNumber = $row["CurrentSeatNumber"] == null ? null : (int) $row["CurrentSeatNumber"];
            $players[$i]->reservedSeatNumber = $row["ReservedSeatNumber"] == null ? null : (int) $row["ReservedSeatNumber"];
            $players[$i]->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
            $players[$i]->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];

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
        global $dateTimeFormat;
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
        $obj->gameSessionId = $row["GameSessionId"] == null ? null : (int) $row["GameSessionId"];
        $obj->status = $row["Status"];
        $obj->startDateTime = $row["StartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["StartDateTime"]);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
        $obj->numberPlayers = $row["NumberPlayers"] == null ? null : (int) $row["NumberPlayers"];
        $obj->dealerPlayerId = $row["DealerPlayerId"] == null ? null : (int) $row["DealerPlayerId"];
        $obj->firstPlayerId = $row["FirstPlayerId"] == null ? null : (int) $row["FirstPlayerId"];
        $obj->nextPlayerId = $row["NextPlayerId"] == null ? null : (int) $row["NextPlayerId"];
        $obj->currentPotSize = $row["CurrentPotSize"] == null ? null : (int) $row["CurrentPotSize"];
        $obj->lastBetSize = $row["LastBetSize"] == null ? null : (int) $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'] == null ? null : (int) $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'] == null ? null : (int) $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'] == null ? null : (int) $row['WinningPlayerId'];

        return $obj;
    }

    /**
     * Get a game session's last instance, including the last dealer, bet size and pot size.
     * @param int $gSessionId
     * @return GameInstance
     */
    public static function getSessionLastInstance($gSessionId) {
        global $dateTimeFormat;
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
        $obj->gameSessionId = $row["GameSessionId"] == null ? null : (int) $row["GameSessionId"];
        $obj->status = $row["Status"];
        $obj->startDateTime = $row["StartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["StartDateTime"]);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);

        $obj->numberPlayers = $row["NumberPlayers"] == null ? null : (int) $row["NumberPlayers"];
        $obj->dealerPlayerId = $row["DealerPlayerId"] == null ? null : (int) $row["DealerPlayerId"];
        $obj->firstPlayerId = $row["FirstPlayerId"] == null ? null : (int) $row["FirstPlayerId"];
        $obj->nextPlayerId = $row["NextPlayerId"] == null ? null : (int) $row["NextPlayerId"];
        $obj->currentPotSize = $row["CurrentPotSize"] == null ? null : (int) $row["CurrentPotSize"];
        $obj->lastBetSize = $row["LastBetSize"] == null ? null : (int) $row["LastBetSize"];
        $obj->numberCommunityCardsShown = $row['NumberCommunityCardsShown'] == null ? null : (int) $row['NumberCommunityCardsShown'];
        $obj->lastInstancePlayNumber = $row['LastInstancePlayNumber'] == null ? null : (int) $row['LastInstancePlayNumber'];
        $obj->winningPlayerId = $row['WinningPlayerId'] == null ? null : (int) $row['WinningPlayerId'];

        /*
          $obj->tableMinimum = $row["CasinoMin"] == null ? null : (int)$row["CasinoMin"]; // via join
          if (is_null($obj->tableMinimum)) {
          $obj->tableMinimum = $row["PracticeMin"] == null ? null : (int)$row["PracticeMin"];
          }
         * 
         */
        return $obj;
    }

    /**
     * Given a game instance for a practice session, return the player
     * @param gInstId
     * @return \Player
     * @throws Exception
     */
    public static function getPracticeInstancePlayer($gInstId) {
        global $dateTimeFormat;

        $result = executeSQL("SELECT p.* FROM Player p
            INNER JOIN PlayerState s on p.id = s.PlayerId
            INNER JOIN GameSession g on g.id = s.GameSessionId
            WHERE g.IsPractice = 1 AND s.GameInstanceId = $gInstId
                ORDER BY p.CurrentSeatNumber", __FUNCTION__ .
                ": Error selecting Player on practice instance $gInstId");

        if (mysql_num_rows($result) == 0) {
            throw new Exception("Invalid game instance id");
        }
        $row = mysql_fetch_array($result);
        $obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
        $obj->lastUpdateDateTime = DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
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
        global $dateTimeFormat;

        $result = executeSQL("SELECT * FROM Player WHERE Id = $playerId", __FUNCTION__ . ": Error selecting Player with player id $playerId");

        if (mysql_num_rows($result) == 0) {
            throw new Exception("Invalid user id");
        }
        $row = mysql_fetch_array($result);
        $obj = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
        $obj->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
        $obj->currentCasinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
        $obj->currentSeatNumber = $row["CurrentSeatNumber"] == null ? null : (int) $row["CurrentSeatNumber"];
        $obj->reservedSeatNumber = $row["ReservedSeatNumber"] == null ? null : (int) $row["ReservedSeatNumber"];
        $obj->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
        $obj->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];

        return $obj;
    }

    /**
     *
     * @param type $playerName
     * @return Player
     */
    public static function getPlayerByName($playerName) {
        global $dateTimeFormat;

        $result = executeSQL("SELECT * FROM Player WHERE Name = '$playerName'", __FUNCTION__ . "
                : Error selecting from Player with name $playerName ");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $playerId = $row["Id"];
            self::log()->debug(__FUNCTION__ . ": player found $playerId");
            $player = new Player($row["Id"], $row['Name'], $row['ImageUrl'], $row['IsVirtual']);
            $player->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
            $player->currentCasinoTableId = $row["CurrentCasinoTableId"] == null ? null : (int) $row["CurrentCasinoTableId"];
            $player->currentSeatNumber = $row['CurrentSeatNumber'] == null ? null : (int) $row['CurrentSeatNumber'];
            $player->reservedSeatNumber = $row['ReservedSeatNumber'] == null ? null : (int) $row['ReservedSeatNumber'];
            $player->waitStartDateTime = $row["WaitStartDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["WaitStartDateTime"]);
            $player->buyIn = $row["BuyIn"] == null ? null : (int) $row["BuyIn"];
            return $player;
        }
        return null;
    }

    /**
     * Create a new player - returns a DTO.
     * @global type $defaultAvatarUrl
     * @param string $playerName
     * @return Player
     */
    public static function createPlayer($playerName) {
        global $defaultAvatarUrl;
        $statusString = Context::GetStatusDTString();
        $nextPlayerId = getNextSequence('Player', 'Id');
        if ($playerName == 'Guest') {
            $playerName = 'Guest' . $nextPlayerId;
        }
        $imageUrl = $defaultAvatarUrl;

        executeSQL("INSERT INTO Player (Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId,
            CurrentSeatNumber, BuyIn, WaitStartDateTime, LastUpdateDateTime)
            VALUES ($nextPlayerId, 0, '$playerName', '$imageUrl', null,
                null, null, null, '$statusString')", __FUNCTION__ . ": Error
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
     * @param type $statusString
     * @return Player
     */
    public static function createPracticePlayer($playerName, $seatNum) {
        global $defaultAvatarUrl;
        global $defaultTableMin;
        global $buyInMultiplier;

        $nextPlayerId = getNextSequence('Player', 'Id');
        $imageUrl = $defaultAvatarUrl;
        $buyIn = $defaultTableMin * $buyInMultiplier;
        $statusString = Context::GetStatusDTString();

        executeSQL("INSERT INTO Player (Id, IsVirtual, Name, ImageUrl, CurrentCasinoTableId,
            CurrentSeatNumber, BuyIn, LastUpdateDateTime, WaitStartDateTime)
            VALUES ($nextPlayerId, 1, '$playerName', '$imageUrl', null,
                $seatNum, $buyIn, '$statusString', null)", __FUNCTION__ . ": Error
                inserting Player generated id $nextPlayerId");
        $player = new Player($nextPlayerId, $playerName, $imageUrl, 1);
        $player->currentSeatNumber = $seatNum;
        $player->lastUpdateDateTime = Context::GetStatusDT();
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
    public static function getPlayerInstance($id, $pId, $isSessionId = false) {
        global $dateTimeFormat;
        if ($isSessionId) {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameSessionId = $id and PlayerId = $pId 
                    ORDER BY SeatNumber", __FUNCTION__ .
                    ": ERROR loading PlayerState with game session id $id
                and player id = $pId");
        } else {
            $result = executeSQL("SELECT *
                FROM PlayerState
                WHERE GameInstanceId = $id and PlayerId = $pId 
                    ORDER BY SeatNumber", __FUNCTION__ .
                    ": ERROR loading PlayerState with game instance id $id
                and player id = $pId");
        }
        // sorted by seat number
        if (mysql_num_rows($result) == 0) {
            return null;
        }
        $row = mysql_fetch_array($result);
        $playerStatus = new PlayerInstance();
        $playerStatus->playerId = $row["PlayerId"] == null ? null : (int) $row["PlayerId"];
        $playerStatus->gameInstanceId = $row["GameInstanceId"] == null ? null : (int) $row["GameInstanceId"];
        $playerStatus->isVirtual = $row["IsVirtual"] == null ? null : (int) $row["IsVirtual"];
        $playerStatus->gameSessionId = $row["GameSessionId"] == null ? null : (int) $row["GameSessionId"];
        $playerStatus->lastUpdateDateTime = $row["LastUpdateDateTime"] == null ? null : DateTime::createFromFormat($dateTimeFormat, $row["LastUpdateDateTime"]);
        $playerStatus->seatNumber = $row["SeatNumber"] == null ? null : (int) $row["SeatNumber"];
        $playerStatus->turnNumber = $row["TurnNumber"] == null ? null : (int) $row["TurnNumber"];
        $playerStatus->status = $row["Status"];
        $playerStatus->currentStake = $row["CurrentStake"] == null ? null : (int) $row["CurrentStake"];
        $playerStatus->lastPlayAmount = $row["LastPlayAmount"] == null ? null : (int) $row["LastPlayAmount"];
        $playerStatus->lastPlayInstanceNumber = $row["LastPlayInstanceNumber"] == null ? null : (int) $row["LastPlayInstanceNumber"];
        $playerStatus->numberTimeOuts = $row["NumberTimeOuts"] == null ? null : (int) $row["NumberTimeOuts"];

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
