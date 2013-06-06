<?php

/*
 * TableCoordinator: business logic for actions that require multiple business
 * objects to act in sequence. This logic does not fit the object model 
 * paradigm.
 * Every action that requires communication to all players fit this category.
 */
/* * ************************************************************************************* */
// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

/* * ************************************************************************************* */

class TableCoordinator {

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /**
     * User may already be added.
     * @global type $defaultTableMin
     * @global type $buyInMultiplier
     * @param type $seatNum
     * @param type $playerId
     * @param type $playerDtos
     * @return Player
     */
    public static function AddUserToTable($playerId, $casinoTable, $players) {
        global $defaultTableMin;
        global $buyInMultiplier;

// exception will be thrown if user not found.
        $player = EntityHelper::getPlayer($playerId);

// scenario #1 user must have accidentally closed browser, nothing to do
        if ($casinoTable->id === $player->casinoTableId) {
            return $player;
        }

// scenario #2 If user in another table, eject so seat vacated and minimize wait for time out
        if ($casinoTable->id !== $player->casinoTableId) {
            self::$log->debug(__FUNCTION__ . ": player $player->playerId at new casino table id
                        $casinoTable->id previous " . $player->casinoTableId);
            $otherTable = EntityHelper::getCasinoTable($player->casinoTableId);
            if (!is_null($otherTable)) {
                $vacatedSeat = TableCoordinator::RemoveUserFromTable($otherTable, $playerId);
                TableCoordinator::ReserveAndOfferSeat($otherTable, $vacatedSeat);
            }
        }
// scenario #3, casino table for user is null
        $seatNum = $casinoTable->FindAvailableSeat($players);

// update player's casino table
        $player->casinoTableId = $casinoTable->id;
        $player->lastUpdateDateTime = Context::GetStatusDT();
        $player->currentSeatNumber = $seatNum;
        $player->reservedSeatNumber = null;
        $player->waitStartDateTime = Context::GetStatusDT();
        $player->buyIn = $casinoTable->tableMinimum * $buyInMultiplier;

        $player->Update();
        $playerDto = new PlayerDto($player);

// Communicating that user joined to the other players already at table
// notice user is skipped because response sent as REST
        $casinoTable->CommunicateUserJoined($player, $players, false);

        return $playerDto;
    }

    public static function RemoveUserFromTable($casinoTable, $playerId) {
        $gameInstance = EntityHelper::getSessionLastInstance($casinoTable->currentGameSessionId);
        $leavingPlayerStatus = EntityHelper::getPlayerInstance($gameInstance->id, $playerId);
        $leavingPlayer = EntityHelper::getPlayer($playerId);

        $vacatedSeat = null;

        if (!is_null($leavingPlayer)) {
            $playerTableId = $leavingPlayer->currentCasinoTableId;
            // returned vacated seat if user on table
            if ($casinoTable->id === $playerTableId) {
                $vacatedSeat = $leavingPlayer->currentSeatNumber;
                if (is_null($vacatedSeat)) {
                    $vacatedSeat = $leavingPlayer->reservedSeatNumber;
                }
            }
        }
        $leavingPlayerStatus->status = PlayerStatusType::LEFT;
        $leavingPlayerStatus->UpdatePlayerLeftStatus();

        // communicate 
        $players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
        $casinoTable->CommunicateUserLeft($leavingPlayer, $players);

        // clean up - purge queue and reset sleeves
        QueueManager::PurgeQueue($playerId);
        CheatingHelper::ResetSleeve($playerId);

        return $vacatedSeat;
    }

    /**
     * Reserve a seat for a user
     * Validation: verify seat being offered is taken already and user does not have another seat taken or reserved.
     * @param int $seatNum
     * @param int $waitingPlayerId
     * @return bool
     */
    public static function ReserveAndOfferSeat($casinoTable, $seatNum) {
        $statusDT = Context::GetStatusDT();
        $waitingPlayer = $casinoTable->FindNextWaitingPlayer();
        if (is_null($waitingPlayer)) {
// nobody waiting for seat
            return null;
        }
        $occupantPlayerId = $casinoTable->IsSeatTakenOrReservedBy($seatNum);
        if (!is_null($occupantPlayerId) && $waitingPlayer->id != $occupantPlayerId) {
            throw new Exception("Player $occupantPlayerId already has seat $seatNum
                    reserved so player id $waitingPlayer->id cannot take it");
        }

        $players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);

// if player not on casino table, log error
        if ($casinoTable->id !== $waitingPlayer->currentCasinoTableId) {
            throw new Exception("Player $waitingPlayer->id cannot reserve any 
                seats because player is not at table $casinoTable->id");
        }

// TODO: must consolidate this with IsSeatTakenOrReservedBy
        $currentSeat = $waitingPlayer->currentSeatNumber;
        $reservedSeat = $waitingPlayer->reservedSeatNumber;
        if ($currentSeat != null && $currentSeat != $seatNum) {
            throw new Exception("Player $waitingPlayer->id already has seat $currentSeat and cannot
                    take $seatNum");
        }
        if ($reservedSeat != null && $reservedSeat != $seatNum) {
            throw new Exception("Player $waitingPlayer->id already has seat $reservedSeat reserved and
                    cannot take $seatNum");
        }
        $waitingPlayer->reservedSeatNumber = $seatNum;

// TODO: move to CasinoTable
        try {
            executeSQL("UPDATE Player SET ReservedSeatNumber = $seatNum,
					LastUpdateDateTime = '$statusDT' WHERE ID =
                    $waitingPlayer->id", __FUNCTION__ . "
                    : Error updating Player id $waitingPlayer->id to reserved seat number $seatNum");
        } catch (Exception $e) {
            $waitingPlayer->reservedSeatNumber = null;
            return false;
        }

        $casinoTable->CommunicateSeatOffered($waitingPlayer->id, $seatNum);
        return true;
    }

    public static function SeatUserOnTable($gameSessionId, $seatNum, $pId) {
        $casinoTable = EntityHelper::getCasinoTableForSession($gameSessionId);
        $players = EntityHelper::GetPlayersForCasinoTable($casinoTable->id);
        if (is_null($seatNum)) {
            throw new Exception("Missing parameter - Player $pId cannot reserve empty seat
                    at table $casinoTable->id");
        }
        $seatingPlayer = EntityHelper::getPlayer($pId);

        // verify seat is reserved
        if ($seatingPlayer->currentCasinoTableId != $casinoTable->id) {
            throw new Exception("Player $pId cannot reserve any seats because player is
                    not at table $casinoTable->id");
        }

        $occupantPlayerId = $casinoTable->IsSeatTakenOrReservedBy($seatNum);
        if ($pId != $occupantPlayerId) {
            throw new Exception("Player $occupantPlayerId already has seat $seatNum
                    reserved so player id $pId cannot take it");
        }

        $currentSeat = $seatingPlayer->currentSeatNumber;
        $reservedSeat = $seatingPlayer->reservedSeatNumber;

        if ($currentSeat != null && $currentSeat != $seatNum) {
            throw new Exception("The player $pId already has seat $currentSeat and cannot
                    take $seatNum");
        }
// note that the player may take a seat even if he did not reserve it.
        if ($reservedSeat != null && $reservedSeat != $seatNum) {
            throw new Exception("The player $pId already has reserved seat $reservedSeat
                    and cannot take $seatNum");
        }
        $seatingPlayer->UpdatePlayerSeat($seatNum);

        $casinoTable->CommunicateSeatTaken($seatingPlayer, $players);
    }

}

?>
