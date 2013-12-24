<?php

include_once(dirname(__FILE__) . '/../Helper/DataHelper.php');

/*
 * Tables for data that persist across calls. This data as is to be stored
 * in memory. See specs for logging and removal from memory.
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
            Name varchar(100),
			Code varchar(50),
            Description varchar(2000),
            TableMinimum int not null,
            NumberSeats int,
            LastUpdateDateTime timestamp,
            CurrentGameSessionId int,
            SessionStartDateTime timestamp null,
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
            Name varchar(100),
            ImageUrl varchar(100),
            IsVirtual tinyint, 
            LastUpdateDateTime timestamp null,
            CurrentCasinoTableId int,
            CurrentSeatNumber int,
            BuyIn int,
            WaitStartDateTime timestamp null,
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
            RequestingPlayerId int,
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
            Status varchar(25),
            StartDateTime timestamp,
            LastUpdateDateTime timestamp,
	    NumberPlayers int,
            DealerPlayerId int,
            FirstPlayerId int,
            NextPlayerId int,
            CurrentPotSize int,
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
            GameSessionId int,
            GameInstanceId int,
            PRIMARY KEY (GameSessionId, GameInstanceId, PlayerId),
            IsPractice tinyint,
            LastUpdateDateTime timestamp,
            SeatNumber int,
            TurnNumber int,
            Status varchar(25),
            CurrentStake int,
            LastPlayAmount int,
            LastPlayInstanceNumber int,
            NumberTimeOuts int,
            Card1Code char(2),
            Card2Code char(2),
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
            DeckPosition int,
            PRIMARY KEY (GameInstanceId, DeckPosition),
            CardCode varchar(25),
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
    // ExpectedPokerMove
    //      Index: GameInstanceId and PlayerId
    // auto-increment because 
    $tableName = "ExpectedPokerMove";
    $sql = "CREATE TABLE $tableName
        (
            GameInstanceId int NOT NULL,
            PlayerId int NOT NULL,
            ExpirationDate timestamp,
            CallAmount int,
            CheckAmount int,
            RaiseAmount int
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