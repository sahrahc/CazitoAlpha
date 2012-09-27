<?php
/*
 * Functions to connect and initialize the database
 * $log comes from the calling code.
 */

function connectToStateDB() {
	global $dbName;
    // connect
    $con = mysql_connect('localhost', 'cazito5_jb', 'worms');
    if (!$con) {
        die('Could not connnect: ' . mysql_error());
    }
    mysql_select_db($dbName, $con);
    return $con;
}

function getNextAutoId($tableName) {
    $result = mysql_query("SHOW TABLE STATUS LIKE '$tableName'");
    $row = mysql_fetch_array($result);
    $nextId = $row['Auto_increment'];
    mysql_free_result($result);
    return $nextId+1;
}

function getNextSequence($tableName, $columnName) {
    $result = mysql_query("SELECT $columnName FROM $tableName ORDER BY $columnName DESC LIMIT 1");
    $row = mysql_fetch_array($result);
    $nextId = $row["$columnName"];
    mysql_free_result($result);
    return $nextId+1;
}

function executeSQL($sql, $msg){
    global $log;
    $result = mysql_query($sql);
    if (!$result) {
        $log->fatal($msg . ': ' . mysql_error());
        $log->warn($sql);
        die($msg);
    }
    return $result;
}

function executeDDL($objName, $sql) {
    global $log;
    // Execute query
    if (mysql_query($sql)) {
        echo "Database object $objName created <br />";
    } else {
        $msg = "Error creating $objName ";
        echo $msg . mysql_error() . '<br />';
        $log->fatal($msg . ': ' . mysql_error());
        $log->warn($sql);
    }
    echo '<br />';    
}

?>