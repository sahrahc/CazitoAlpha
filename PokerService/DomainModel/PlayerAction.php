<?php

/* An instance of a player making a move, including blind bets.
 */

class PlayerAction {

    public $gameInstanceId;
    public $playerId;
    public $pokerActionType;
    public $actionTime;
    public $actionValue;

    function __construct($gInstId, $pId, $actionType, $time, $value) {
        $this->gameInstanceId = $gInstId;
        $this->playerId = $pId;
        $this->pokerActionType = $actionType;
        $this->actionTime = Context::GetStatusDT();
        $this->actionValue = $value;
    }


    /**
     * Validates the move is valid. Returns the move id (so it can be deleted if successfully
     * @global type $log
     */
    public function ValidateMove() {
        global $log;
        $expectedMove = ExpectedPokerMove::GetExpectedMoveForInstance($this->gameInstanceId);
        $exceptionMsg = null;

        // variables to keep objects easier to access
        $action = $this->action;
        $gameInstanceId = $this->gameInstanceId;

        if (is_null($expectedMove)) {
            $msg = __FUNCTION__ . ": No moves expected for game instance $gameInstanceId but 
                   player id $this->playerId $this->pokerActionType";
            $log->warn($msg);
            $exceptionMsg = $msg;
            //throw new Exception($exceptionMsg);
        }
        if ($action->playerId != $expectedMove->playerId) {
            $msg = __FUNCTION__ . ": Wrong player attempting move for instance
                    $this->gameInstanceId actual player id $this->playerId but expected
                    player id $expectedMove->playerId";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($this->pokerActionType == PokerActionType::CALLED AND
                $this->callAmount != $expectedMove->callAmount) {

            $msg = __FUNCTION__ . ": Call amount is wrong for instance $this->gameInstanceId
                    by $this->playerId actual amount $this->callAmount expected
                    $expectedMove->callAmount";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($action->pokerActionType == PokerActionType::CHECKED AND
                is_null($expectedMove->checkAmount)) {

            $msg = __FUNCTION__ . ": Check is not allowed for instance $action->gameInstanceId
                    but attempted by $action->playerId";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($action->pokerActionType == PokerActionType::RAISED AND
                $this->raiseAmount != $expectedMove->raiseAmount) {

            $msg = __FUNCTION__ . ": Raise amount is wrong for instance $this->gameInstanceId
                    by $this->playerId actual amount $this->raiseAmount but expected
                    $expectedMove->raiseAmount";
            $log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if (!is_null($exceptionMsg)) {
            //throw new Exception($exceptionMsg);
        }
        $this->id = $expectedMove->id;
    }

    /*     * ****************************************************************************** */
    
    /**
     * Converts the player action into a poker move
     * @return \ExpectedPokerMove
     */
    public function ConvertToPokerMove() {
        $entity = new ExpectedPokerMove();
        $entity->action = $this;
        $entity->gameInstanceId = $this->gameInstanceId;
        $entity->playerId = $this->playerId;
        $entity->statusDateTime = $this->actionTime;
        $entity->pokerActionType = $this->pokerActionType;
        switch ($this->pokerActionType) {
            case PokerActionType::CALLED:
                $entity->callAmount = $this->actionValue;
                break;
            case PokerActionType::CHECKED:
                $entity->raiseAmount = $this->actionValue;
                break;
            case PokerActionType::RAISED:
                $entity->checkAmount = $this->actionValue;
                break;
        }
        
        return $entity;
    }
}

?>
