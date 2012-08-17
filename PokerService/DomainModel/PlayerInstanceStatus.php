<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

class PlayerInstanceStatus {

    public $playerId;
    public $gameInstanceId;
    public $lastUpdateDateTime;
    public $status;
    public $stake;
    public $lastPlayAmount;
    public $playerPlayNumber;
    public $numberTimeOuts;
    public $playerInstanceSetup;
    public $log;

    public function __construct() {
        $this->log = Logger::getLogger(__CLASS__);
        }

    public function saveStatus($statusDT) {
        $blindBet = $this->playerInstanceSetup->blindBet;
        if (is_null($blindBet)) {
            $blindBet = "null";
        }
        $lastPlayAmount = $this->lastPlayAmount;
        if (is_null($lastPlayAmount)) {
            $lastPlayAmount = "null";
        }
        executeSQL("UPDATE PlayerState SET LastUpdateDateTime = '$statusDT',
                Status = '$this->status',
                Stake = $this->stake, 
                LastPlayAmount = $lastPlayAmount,
                PlayerPlayNumber = $this->playerPlayNumber,
                BlindBet = $blindBet
                WHERE PlayerId = $this->playerId and GameInstanceId = $this->gameInstanceId
                ", __FUNCTION__ . ": Error updating player state player id $this->playerId");
    }

}

?>
