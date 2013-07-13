<?php

/*
 * Utilities to manage session data for the duration of a user action
 */

class Context {
    /*
     * Basic session variables are db and queue connection,
     * queue exchange and channel
     */

    public static function Init() {
        //global $dateTimeFormat;
        // new DateTime("now"); not working?
        //$_SESSION['StatusDT'] = DateTime::createFromFormat($dateTimeFormat, date($dateTimeFormat));
        $_SESSION['StatusDT'] = new DateTime();
        $_SESSION['DbConn'] = connectToStateDB();
        $qConn = QueueManager::GetConnection();
        $_SESSION['QConn'] = $qConn;
        $ch = QueueManager::GetChannel($qConn);
        $_SESSION['QCh'] = $ch;
        // makes sure it's created
        $_SESSION['QExP'] = QueueManager::GetPlayerExchange($ch);
        $_SESSION['QExS'] = QueueManager::GetSessionExchange($ch);
        $_SESSION['QExC'] = QueueManager::GetChatExchange($ch);
    }

    public static function SetStatusDT() {
        //global $dateTimeFormat;
        $_SESSION['StatusDT'] = new DateTime();
    }

    public static function GetStatusDT() {
        if (isset($_SESSION['StatusDT'])) {
            return clone $_SESSION['StatusDT'];
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
        /* if (isset($_SESSION['QConn'])) {
          return $_SESSION['QConn'];
          }
          return null; */
        return QueueManager::GetConnection();
    }

    public static function GetQCh() {
        /* if (isset($_SESSION['QCh'])) {
          return $_SESSION['QCh'];
          }
          return null; */
        $qConn = QueueManager::GetConnection();
        return QueueManager::GetChannel($qConn);
    }

    public static function GetExchangePlayer() {
        if (isset($_SESSION['QExP'])) {
            return $_SESSION['QExP'];
        }
        return null;
    }

    public static function GetExchangeChat() {
        if (isset($_SESSION['QExC'])) {
            return $_SESSION['QExC'];
        }
        return null;
    }

    public static function GetExchangeSession() {
        $qConn = QueueManager::GetConnection();
        $qCh = QueueManager::GetChannel($qConn);
        return QueueManager::GetSessionExchange($qCh);
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
