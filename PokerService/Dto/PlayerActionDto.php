<?php
/* Type: Request DTO.
 * Source: None
 * Description: The poker move selected by a player.
 */

class PlayerActionDto {

    public $gameInstanceId;
    public $playerId;
    public $pokerActionType;
    public $actionTime;
    public $actionValue;

    function __construct($gInstId, $pId, $actionType, $time, $value) {
        $this->gameInstanceId = $gInstId;
        $this->playerId = $pId;
        $this->pokerActionType = $actionType;
        $this->actionTime = $time;
        $this->actionValue = $value;
    }
}
?>
