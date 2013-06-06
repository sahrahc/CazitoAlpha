<?php

/*
 * Utilities to manage session data for the duration of a user action
 */
// Include Libraries
include_once(dirname(__FILE__) . '/../../libraries/helper/WebServiceDecoder.php');
include_once(dirname(__FILE__) . '/../../libraries/log4php/Logger.php');

class Context {

    /*
     * Basic session variables are db and queue connection,
     * queue exchange and channel
     */

    public static function Init() {
        global $dateTimeFormat;
        $_SESSION['StatusDT'] = date($dateTimeFormat);
        $_SESSION['DbConn'] = connectToStateDB();
        $qConn = QueueManager::GetQueueConnection();
        $_SESSION['QConn'] = $qConn;
        $ch = QueueManager::GetChannel($qConn);
        $_SESSION['QCh'] = $ch;
        $_SESSION['QEx'] = QueueManager::GetPlayerExchange($ch);
    }

    public static function SetStatusDT() {
        global $dateTimeFormat;
        $_SESSION['StatusDT'] = date($dateTimeFormat);
    }
    public static function GetStatusDT() {
        if (isset($_SESSION['StatusDT'])) {
            return $_SESSION['StatusDT'];
        }
        return null;
    }

    public static function GetDbConn() {
        if (isset($_SESSION['DbConn'])) {
            return $_SESSION['DbConn'];
        }
        return null;
    }

    public static function GetQConn() {
        if (isset($_SESSION['QConn'])) {
            return $_SESSION['QConn'];
        }
        return null;
    }

    public static function GetQCh() {
        if (isset($_SESSION['QCh'])) {
            return $_SESSION['QCh'];
        }
        return null;
    }

    public static function GetQEx() {
        if (isset($_SESSION['QEx'])) {
            return $_SESSION['QEx'];
        }
        return null;
    }

    public static function Disconnect() {
        mysql_close();
        if (isset($_SESSION['QConn'])) {
            $qConn = $_SESSION['QConn'];
            QueueManager::DisconnectQueue($qConn);
        }
    }

    /*     * ******************************************************************* */
    /*     * ** optional *** */
}
?>
