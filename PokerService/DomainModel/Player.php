<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

//include_once(dirname(__FILE__) . '/../Components/EventMessageProducer.php');
include_once(dirname(__FILE__) . '/../Components/QueueManager.php');

/* * ************************************************************************************** */

/**
 * A player entity between the time he logs in and before he starts playing
 * in games.
 */
class Player {

    public $id;
    public $name;
    public $imageUrl;
    public $isVirtual;
    public $lastUpdateDateTime;
    public $currentCasinoTableId;
    public $currentSeatNumber;
    public $reservedSeatNumber;
    public $waitStartDateTime;
    public $buyIn;

    /*
     * Use constructor when first logged in
     */
    function __construct($id, $name, $imageUrl, $isVirtual) {
        $this->playerId = $id;
        $this->playerName = $name;
        $this->playerImageUrl = $imageUrl;
        $this->isVirtual = $isVirtual;
    }
    
    public function Update() {
        $seatValue = $this->currentSeatNumber;
        $waitingStartDT = "null";
        // set seat
        if (is_null($this->currentSeatNumber)) {
            $seatValue = "null";
            $waitingStartDT = Context::GetStatusDT();
        }
        $reservedSeat = $this->reservedSeatNumber;
        if (is_null($this->reservedSeatNumber)) {
            $reservedSeat = "null";
        }
        executeSQL("UPDATE Player SET 
            CurrentCasinoTableId = $this->currentCasinoTableId,
            CurrentSeatNumber = $seatValue, 
            ReservedSeatNumber = $reservedSeat,
            WaitStartDateTime = '$waitingStartDT',
            BuyIn = $this->buyIn, 
            LastUpdateDateTime = '$waitingStartDT'
            WHERE Id = $this->id", __FUNCTION__ . "
                : Error updating Player player id $this->id");
        self::$log->warn(__FUNCTION__ . ": Updated casino id for player id
                            $this->id when getting player");
    }

    /**
     * Converts a reserved seat into a current seat.
     * TODO: move to coordinator
     * @param type $seatNum
     * @param type $pId
     */
    public function UpdatePlayerSeat($seatNum, $isReserved = false) {
        $statusDT = Context::GetStatusDT();
        if ($isReserved) {
            $this->reservedSeatNumber = $seatNum;
            executeSQL("UPDATE Player SET ReservedSeatNumber = $seatNum,
                    CurrentSeatNumber = null, LastUpdateDateTime = '$statusDT' WHERE Id =
                    $this->id", __FUNCTION__ .
                    ": Error updating Player id $this->id to reserved seat number $seatNum");
            return;
        }
        $this->currentSeatNumber = $seatNum;
        executeSQL("UPDATE Player SET CurrentSeatNumber = $seatNum,
                    ReservedSeatNumber = null, LastUpdateDateTime = '$statusDT' WHERE Id =
                    $this->id", __FUNCTION__ .
                ": Error updating Player id $this->id to seat number $seatNum");
    }

}

?>
