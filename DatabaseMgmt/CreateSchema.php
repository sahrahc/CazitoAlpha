<?php

include_once(dirname(__FILE__) . '/../../Libraries/Helper/DataHelper.php');

/* Create the MySQL database
 * Schema currently has five tables that track transient data:
 *  1) CasinoTable - a casino table is opened as users log in and current
 *     tables fill up. Technically there is no limit to the number
 *     of virtual tables that can be opened. Future: improve
 *     matching of users to tables
 *  2) Player - the structure is for active players as kept in memory -
 *     will split into master data vs. historical activity data later.
 *  3) GameState (combines GameSession and GameInstance) - the status of
 *     an actively played game instance. The structure
 *     is as to maintained in memory; will split into historical later.
 *  4) PracticeSession - the status of practice game session/instance
 *  5) PlayerState - the status of a player actively engaged in a game. Subset
 *     of Player because excludes players just joining a table not yet engaged
 *     in a game. Structure is as maintained in memory.
 *  6) PracticePlayerState - same as PlayerState but for PracticeSession
 *  7) GameCard - the cards dealt for a game.
 */

function CreateSchema() {
    global $dbName;
    $con = connectToStateDB();

    /* --------------------------------------------------------------------- */
    // CasinoTable
    //      Index - CurrentGameSessionId, TableName
    //
    // TODO: come up with cool casino table names.
    // TODO: Move to in-memory structures with persistent storage as a second
    // step (timing needs to be carefully defined).
    $tableName = "CasinoTable";
    $sql = "CREATE TABLE $tableName
        (
            Id int NOT NULL,
            Name varchar(25),
            TableMinimum int,
            NumberSeats int,
            LastUpdateDateTime timestamp,
            CurrentGameSessionId int,
            SessionStartDateTime timestamp,
            PRIMARY KEY (Id)
        )";
    executeDDL($tableName, $sql);
    // add index on GameSessionId and table name
    $columnName = "CurrentGameSessionId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    $columnName = "Name";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // Player
    //      Index - CurrentTableCasinoId
    $tableName = "Player";
    $sql = "CREATE TABLE $tableName
        (
            Id int NOT NULL,
            IsVirtual tinyint, 
            Name varchar(25),
            ImageUrl varchar(25),
            LastUpdateDateTime timestamp,
            CurrentCasinoTableId int,
            CurrentSeatNumber int,
            BuyIn int,
            WaitStartDateTime timestamp,
            ReservedSeatNumber int,
            PRIMARY KEY (Id)
        )";
    executeDDL($tableName, $sql);
    // add index on casino table id
    $columnName = "CurrentCasinoTableId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // GameSession
    $tableName = "GameSession";
    $sql = "CREATE TABLE $tableName
        (
            Id int NOT NULL,
            StartDateTime timestamp,
            TableMinimum int,
            NumberSeats int,
            IsPractice tinyint,
            IsCheatingAllowed tinyint,
            PRIMARY KEY (Id)
        )";
    executeDDL($tableName, $sql);

    /* --------------------------------------------------------------------- */
    // GameInstance
    //      Index: GameSessionId
    // LastInstancePlayNumber tracks the number of moves for the entire game.
    // FirstPlayerSeatNumber is needed to track end of round. 
    $tableName = "GameInstance";
    $sql = "CREATE TABLE $tableName
        (
            Id int NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (Id),
            GameSessionId int,
            IsPractice tinyint,
            StartDateTime timestamp,
            LastUpdateDateTime timestamp,
			NumberPlayers int,
            DealerPlayerId int,
            FirstPlayerId int,
            NextPlayerId int,
            PotSize int,
            LastBetSize int,
            NumberCommunityCardsShown int,
            LastInstancePlayNumber int,
            WinningPlayerId int
        )";
    executeDDL($tableName, $sql);
    // add index on GameSessionId
    $columnName = "GameSessionId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // PlayerState
    //      Index: GameSessionId, GameInstanceId
    // TurnNumber cannot have gaps.
    // Blind bet is a separate field because it needs to be tracked separately from the stake
    // until moved to the pot.
    $tableName = "PlayerState";
    $sql = "CREATE TABLE $tableName
        (
            PlayerId int,
            IsVirtual int,
            GameSessionId int,
            GameInstanceId int,
            PRIMARY KEY (GameInstanceId, PlayerId),
            LastUpdateDateTime timestamp,
            SeatNumber int,
            TurnNumber int,
            Status varchar(25),
            BlindBet int, 
            Stake int,
            LastPlayAmount int,
            PlayerPlayNumber int,
            NumberTimeOuts int,
            Card1Code varchar(25),
            Card2Code varchar(25),
            HandType varchar(25),
            HandInfo int,
            HandCategory int,
            HandRankWithinCategory int
        )";
    executeDDL($tableName, $sql);

    // two indexes, by gamesession id or instance id
    $columnName = "GameSessionId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);
/*
    $columnName = "GameInstanceId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);
*/
    /* --------------------------------------------------------------------- */
    // GameCard
    //      Index: GameInstanceId
    $tableName = "GameCard";
    $sql = "CREATE TABLE $tableName
        (
            GameInstanceId int,
            CardCode varchar(25),
            DeckPosition int,
            PlayerId int,
            PlayerCardNumber int,
            CardIndex int
        )";
    // delete card index after moving to new evaluator
    executeDDL($tableName, $sql);
    // create index on game instace id
    $columnName = "GameInstanceId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // PlayerAction
    //      Index: GameInstanceId
    $tableName = "PlayerAction";
    $sql = "CREATE TABLE $tableName
        (
            GameInstanceId int,
            PlayerId int,
            PokerActionType varchar(10),
            ActionTime timestamp,
            ActionValue int
        )";
    executeDDL($tableName, $sql);
    // create index on game instace id
    $columnName = "GameInstanceId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // NextPokerMove
    //      Index: GameInstanceId and PlayerId
    // auto-increment because 
    $tableName = "NextPokerMove";
    $sql = "CREATE TABLE $tableName
        (
            Id int NOT NULL AUTO_INCREMENT,
            PRIMARY KEY (Id),
            GameInstanceId int,
            IsPractice tinyint,
            PlayerId int,
            TurnNumber int,
            ExpirationDate timestamp,
            IsEndGameNext tinyint,
            CallAmount int,
            CheckAmount int,
            RaiseAmount int,
			IsDeleted tinyint
        )";
    executeDDL($tableName, $sql);
    // create index on game instace id
    $columnName1 = "GameInstanceId";
    $columnName2 = "PlayerId";
    $indexName = $tableName . '_InstanceId_PlayerId_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName1, $columnName2)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    mysql_close($con);
}

?>