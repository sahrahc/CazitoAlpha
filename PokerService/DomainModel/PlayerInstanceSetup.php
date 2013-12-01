<?php

/**
 * The initial configuration of a player in a game instance. The setup data is separated from the status data that changes after every move for readability and optimization.
 */
class PlayerInstanceSetup {
    public $playerId;
    public $isVirtual;
    public $playerName;
    public $playerImageUrl;
    public $gameSessionId;
    public $gameInstanceId;
    public $seatNumber;
    public $turnNumber;
    public $blindBet;

}
?>
