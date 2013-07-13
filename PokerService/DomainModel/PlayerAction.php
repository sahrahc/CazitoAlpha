<?php

/* An instance of a player making a move, including blind bets.
 */

class PlayerAction {

    public $gameInstanceId;
    public $playerId;
    public $pokerActionType;
    public $actionTime;
    public $actionValue;
    private $log;


    function __construct($gInstId, $pId, $actionType, $time, $value) {
        $this->gameInstanceId = $gInstId;
        $this->playerId = $pId;
        $this->pokerActionType = $actionType;
        $this->actionTime = $time;
        $this->actionValue = $value;
        $this->log = Logger::getLogger(__CLASS__);
    }


    /**
     * Validates the move is valid. Returns the move id (so it can be deleted if successfully
     * @global type $log
     */
    public function IsMoveValid() {
        $expectedMove = ExpectedPokerMove::GetExpectedMoveForInstance($this->gameInstanceId);
        $exceptionMsg = null;

        if (!is_null($expectedMove) && $expectedMove->status == PlayerStatusType::LEFT) {
            $msg = __FUNCTION__ . ": Move may be valid by $this->playerId for  
                    $this->gameInstanceId. Expected move by $expectedMove->playerId
                   obsolete because user left.";
            $this->log->warn($msg);
            // $exceptionMsg is null;
            //throw new Exception($exceptionMsg);
            return null;
        }
        if ($this->playerId != $expectedMove->playerId) {
            $msg = __FUNCTION__ . ": Wrong player attempting move for instance
                    $this->gameInstanceId actual player id $this->playerId but expected
                    player id $expectedMove->playerId";
            $this->log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($this->pokerActionType == PokerActionType::CALLED AND
                $this->actionValue != $expectedMove->callAmount) {

            $msg = __FUNCTION__ . ": Call amount is wrong for instance $this->gameInstanceId
                    by $this->playerId actual amount $this->actionValue expected
                    $expectedMove->callAmount";
            $this->log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($this->pokerActionType == PokerActionType::CHECKED AND
                is_null($expectedMove->isCheckAllowed)) {

            $msg = __FUNCTION__ . ": Check is not allowed for instance $this->gameInstanceId
                    but attempted by $this->playerId";
            $this->log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if ($this->pokerActionType == PokerActionType::RAISED AND
                $this->actionValue != $expectedMove->raiseAmount) {

            $msg = __FUNCTION__ . ": Raise amount is wrong for instance $this->gameInstanceId
                    by $this->playerId actual amount $this->actionValue but expected
                    $expectedMove->raiseAmount";
            $this->log->warn($msg);
            $exceptionMsg = is_null($exceptionMsg) ? $msg : $exceptionMsg;
        }
        if (!is_null($exceptionMsg)) {
            return false;
            //throw new Exception($exceptionMsg);
        }
        return true;
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
            case PokerActionType::RAISED:
                $entity->raiseAmount = $this->actionValue;
                break;
            case PokerActionType::CHECKED:
                $entity->isCheckAllowed = 1;
                break;
        }
        
        return $entity;
    }
}

?>
