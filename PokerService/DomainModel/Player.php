<?php

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
        $this->id = $id == null ? null : (int)$id;
        $this->name = $name;
        $this->imageUrl = $imageUrl;
        $this->isVirtual = $isVirtual == null ? null : (int)$isVirtual;
    }
    
    public function Update() {
        global $logPlayer;
        global $dateTimeFormat;
        
        $seatValue = $this->currentSeatNumber;
        $waitingStartDT = "null";
        // set seat
        if (is_null($this->currentSeatNumber)) {
            $seatValue = "null";
            $waitingStartDT = Context::GetStatusDT()->format($dateTimeFormat);
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
        // log updated player record
        $logPlayer->info("");
        }

    /**
     * Converts a reserved seat into a current seat.
     * TODO: move to coordinator
     * @param type $seatNum
     * @param type $pId
     */
    public function UpdatePlayerSeat($seatNum, $isReserved = false) {
        global $logPlayer;
        global $dateTimeFormat;
        
        $statusDT = Context::GetStatusDT()->format($dateTimeFormat);
        if ($isReserved) {
            $this->reservedSeatNumber = $seatNum;
            executeSQL("UPDATE Player SET ReservedSeatNumber = $seatNum,
                    CurrentSeatNumber = null, LastUpdateDateTime = '$statusDT' WHERE Id =
                    $this->id", __FUNCTION__ .
                    ": Error updating Player id $this->id to reserved seat number $seatNum");
            $logPlayer->info("");
            return;
        }
        $this->currentSeatNumber = $seatNum;
        executeSQL("UPDATE Player SET CurrentSeatNumber = $seatNum,
                    ReservedSeatNumber = null, LastUpdateDateTime = '$statusDT' WHERE Id =
                    $this->id", __FUNCTION__ .
                ": Error updating Player id $this->id to seat number $seatNum");
            $logPlayer->info("");
    }

}

?>
