<?php

include_once(dirname(__FILE__) . '/../Helper/DataHelper.php');

/**
 * Cheating items
 * @global type $dbName
 */
function CreateItems() {
    global $dbName;
    $con = connectToStateDB();

    /* --------------------------------------------------------------------- */
    $tableName = "PlayerActiveItem";
    $sql = "CREATE TABLE $tableName
        (
            PlayerId int NOT NULL,
            GameSessionId int,
            GameInstanceId int,
            ItemType varchar(25),
            StartDateTime timestamp,
            EndDateTime timestamp,
            LockEndDateTime timestamp,
            IsActive tinyint,
            IsAvailable tinyint,
            NumberCards int
        )";
    executeDDL($tableName, $sql);

    $columnName = "PlayerId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    $columnName = "GameSessionId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    $columnName = "ItemType";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // Player
    //      Index - CurrentTableCasinoId
    $tableName = "PlayerHiddenCard";
    $sql = "CREATE TABLE $tableName
        (
            PlayerId int NOT NULL,
            CardCode char(2),
            CardPosition int
        )";
    executeDDL($tableName, $sql);
    
    $columnName = "PlayerId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    // GameSession
    $tableName = "PlayerVisibleCard";
    $sql = "CREATE TABLE $tableName
        (
            PlayerId int NOT NULL,
            GameSessionId int,
            CardCode char(2),
            ExpirationDateTime timestamp
        )";
    executeDDL($tableName, $sql);

    $columnName = "PlayerId";
    $indexName = $tableName . '_' . $columnName . '_Idx';
    $sql = "CREATE INDEX $indexName ON $tableName($columnName)";
    executeDDL($indexName, $sql);

    /* --------------------------------------------------------------------- */
    mysql_close($con);
}

?>