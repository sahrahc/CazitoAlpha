<?php

/* * ************************************************************************************* */

/**
 * FIXME:
 * PlayerActiveItem = key/value
 */
class CheatingHelper {

    private static $log = null;

    public static function log() {
        if (is_null(self::$log))
            self::$log = Logger::getLogger(__CLASS__);
        return self::$log;
    }

    /**
     * Gets the list of all players cards and flags the cards that match the suit
     * @param id playerId
     * @param GameInstance $gInstStatus
     * @param string $itemType
     * @param DateTime $currentDT
     */
    public static function GetSuitForAllGameCards($pId, $gInstStatus, $itemType) {
        global $dateTimeFormat;
        global $cHeartMarkerTimeOut;
        global $cClubMarkerTimeOut;
        global $cDiamondMarkerTimeOut;
        global $pokerCardName;

        $cheaterCardDtos = null;
        $gameCards = CardHelper::getGameCardsForInstance($gInstStatus->id);

        $counter = 0;
        foreach ($gameCards->playerHands as $pH) {
            $suitValue = null;
            $cardName = $pokerCardName[$pH->pokerCard1Code];
            if (!is_null($itemType) && strpos($cardName, $itemType) !== false) {
                $suitValue = $itemType;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 1, null, $suitValue);

            // second card
            $suitValue2 = null;
            $cardName2 = $pokerCardName[$pH->pokerCard2Code];
            if (!is_null($itemType) && strpos($cardName2, $itemType) !== false) {
                $suitValue2 = $itemType;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 2, null, $suitValue2);
        }
        /* ------------------------------------------------------------------------------ */
        // create record with time out
        $itemType = null;
        switch ($itemType) {
            case ItemType::HEART_MARKER:
                $itemType = ItemType::HEART_MARKER;
                $timeOut = $cHeartMarkerTimeOut;
                break;
            case ItemType::CLUB_MARKER:
                $timeOut = $cClubMarkerTimeOut;
                break;
            case ItemType::DIAMOND_MARKER:
                $timeOut = $cDiamondMarkerTimeOut;
                break;
            default;
                throw new Exception("Error in Cheating Item, can only mark clubs, diamonds and hearts, not " . $itemType);
        }
        $lockEndDateTime = clone Context::GetStatusDT();
        $lockEndDateTime->add(new DateInterval($timeOut));

        $sessionId = $gInstStatus->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId, $itemType);
        $activeItem->lockEndDateTime = $lockEndDateTime;
        $activeItem->isActive = 0; // instantaneous
        $activeItem->isAvailable = 0; // lock out period
        $activeItem->endDateTime = $activeItem->startDateTime;
        $activeItem->RecordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $messagesOut[0] = new CheatOutcomeDto(CheatDtoType::CheatedCards, $cheaterCardDtos);
        $info = "$itemType was applied. This item is available again at " . $lockEndDateTime->format($dateTimeFormat) . '.';
        $messagesOut[1] = new CheatOutcomeDto(CheatDtoType::ItemLog, $info);
        self::_communicateCheatingOutcome($pId, $messagesOut);
    }

    /**
     * Replaces the playerCardNumber with a random ace.
     * @param GameInstance $gInstStatus
     * @param type $pId
     * @param type $pCardNum
     */
    public static function PushRandomAce($pId, $gInstStatus, $pCardNum, $itemType) {
        global $pokerCardName;

        $deck = EvalHelper::init_deck();
        $suits = array('s', 'h', 'd', 'd');
        $suitsBit = array(0x1000, 0x2000, 0x4000, 0x8000);
        //$ranks = array('2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A');
        $randIndex = rand(0, 3);
        $randSuit = $suits[$randIndex];
        $cardCode = 'A' . $randSuit;
        $cardName = $pokerCardName[$cardCode];
        $cardIndex = EvalHelper::findDeckIndex($randSuit, $suitsBit[$randIndex], $deck);
        CardHelper::updatePlayerCard($pId, $gInstStatus->id, $pCardNum, $cardIndex, $cardCode);
        /* ------------------------------------------------------------------------------ */
        // create record with time out

        $sessionId = $gInstStatus->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId, $itemType);
        $activeItem->lockEndDateTime = Context::GetStatusDT(); // no lock out period
        $activeItem->isActive = 0;
        $activeItem->isAvailable = 1;
        $activeItem->endDateTime = Context::GetStatusDT();
        $activeItem->gameInstanceId = $gInstStatus->id;
        $activeItem->RecordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        $dto = new CheaterCardDto($pId, $pCardNum, $cardName, $randSuit);
        $messagesOut[0] = new CheatOutcomeDto(CheatDtoType::CheatedHands, $dto);
        $info = "Replaced card number $pCardNum with $cardName. You may push an ace again at any time";
        $messagesOut[1] = new CheatOutcomeDto(CheatDtoType::ItemLog, $info);
        self::_communicateCheatingOutcome($pId, $messagesOut);
    }

    /**
     * Starts the process of marking cards which are seen by a player. The marking of cards
     * only needs to happen at the end of the game, but spans multiple games
     * @global type $cSocialSpotterTimeOut
     * @global type $cSocialSpotterDuration
     * @param type $pId
     * @param type $gInstStatus
     * @param type $currentDT 
     */
    public static function StartCardMarking($pId, $gSessionId, $currentDT, $itemType) {
        global $cSocialSpotterTimeOut;
        global $cSocialSpotterDuration;
        global $dateTimeFormat;

        // FIXME: should be based on $statusDT for all items
        $lockEndDateTime = clone $currentDT;
        $lockEndDateTime->add(new DateInterval($cSocialSpotterTimeOut));
        $endDateTime = clone $currentDT;
        $endDateTime->add(new DateInterval($cSocialSpotterDuration));

        $activeItem = new PlayerActiveItem($pId, $gSessionId, $itemType);
        $activeItem->lockEndDateTime = $lockEndDateTime; // no lock out period
        $activeItem->isActive = 1;
        $activeItem->isAvailable = 0;
        $activeItem->endDateTime = $endDateTime;
        $activeItem->RecordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $dateString = $currentDT->format($dateTimeFormat);
        $endString = $endDateTime->format($dateTimeFormat);
        $lockEndString = $lockEndDateTime->format($dateTimeFormat);
        $info = "$itemType was activated on $dateString. Cards you see at this table until $endString will be marked so you know the values in subsequent games. After $endString, this item wil be available again at $lockEndString.";
        self::_communicateCheatingOutcome($pId, new CheatOutcomeDto(CheatDtoType::ItemLog, $info));
    }

    /**
     *
     * @global type $cRiverShufflerTimeOut
     * @param type $pId
     * @param type $gameInstance
     * @param type $currentDT
     * @return string (array of 1)
     */
    public static function CheatLookRiverCard($pId, $gameInstance, $currentDT, $itemType) {
        global $cRiverShufflerTimeOut;
        global $dateTimeFormat;
        global $pokerCardName;

        $gInstId = $gameInstance->id;
        // get the river card; the following returns sorted
        $result = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId = -1 and PlayerCardNumber = 5 ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
        $row = mysql_fetch_array($result);
        //$curDeckPosition = (int)$row['DeckPosition'];

        $cardCode = $row['CardCode'];
        self::log()->Debug(__FUNCTION__ . " - River card for instance $gInstId is " . $cardCode);
        $cardNameListCode = null;
        if (!is_null($cardCode)) {
            $cardNameListCode[0] = $pokerCardName[$cardCode];
        }
        self::log()->Debug(__FUNCTION__ . " - River card name for instance $gInstId is " . json_encode($cardNameListCode));
        // FIXME: should be based on $statusDT for all items
        $lockEndDateTime = clone $currentDT;
        $lockEndDateTime->add(new DateInterval($cRiverShufflerTimeOut));
        $endDateTime = clone $lockEndDateTime;

        $sessionId = $gameInstance->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId, $itemType);
        $activeItem->lockEndDateTime = $lockEndDateTime; // no lock out period
        $activeItem->isActive = 1;
        $activeItem->isAvailable = 0;
        $activeItem->endDateTime = $endDateTime;
        $activeItem->gameInstanceId = $gameInstance->id;
        $activeItem->RecordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $messagesOut = array();
        if ($cardNameListCode != null) {
            array_push($messagesOut, new CheatOutcomeDto(CheatDtoType::CheatedNext, $cardNameListCode));
        }
        $dateString = $currentDT->format($dateTimeFormat);
        $lockEndString = $lockEndDateTime->format($dateTimeFormat);
        $info = "$itemType activated on $dateString. You may swap the river card for the current game for the next card in the deck. The river card may not the one you see on the 'Next' section if another player used an item. This item wil be available again at $lockEndString.";
        array_push($messagesOut, new CheatOutcomeDto(CheatDtoType::ItemLog, $info));
        self::_communicateCheatingOutcome($pId, $info);
    }

    /**
     *
     * @param type $pId 
     */
    public static function CheatSwapRiverCard($pId, $gInstStatus, $itemType) {
        global $dateTimeFormat;
        // validate the item is active
        $sessionId = $gInstStatus->gameSessionId;

        $activeItem = PlayerActiveItem::VerifyPlayerActiveItem($pId, $sessionId, $itemType);
        $gInstId = $gInstStatus->id;
        if (is_null($activeItem)) {
            self::log()->Warn(__FUNCTION__ . " $pId attempted to swap a river card when he shouldn't be able to");
        }
        // get the next unassigned GameCard -
        // FIXME: from the player's next list?
        $result = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId is null ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
        $row = mysql_fetch_array($result);
        $availDeckPosition = $row['DeckPosition'];
        $availCardCode = $row['CardCode'];

        $resultAvail = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId = -1 and PlayerCardNumber = 5 ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
        $rowAvail = mysql_fetch_array($resultAvail);
        $curDeckPosition = $rowAvail['DeckPosition'];
        $curCardCode = $rowAvail['CardCode'];

        // update the unassigned and the new game card
        executeSQL("UPDATE GameCard SET PlayerId = -1, PlayerCardNumber = 5 WHERE
            GameInstanceId = $gInstId AND DeckPosition = $availDeckPosition", __FUNCTION__ . ":
                Error updating DeckPosition $availDeckPosition instance $gInstId to be
                the next river card");
        executeSQL("UPDATE GameCard SET PlayerId = null, PlayerCardNumber = null WHERE
            GameInstanceId = $gInstId AND DeckPosition = $curDeckPosition", __FUNCTION__ . ":
                Error removing DeckPosition $curDeckPosition as river card for instance $gInstId");
        // communicate swap
        /* ------------------------------------------------------------------------------ */
        // record event; nothing else changes
        $newActiveItem = clone $activeItem;
        $newActiveItem->itemType = $itemType;
        $newActiveItem->RecordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $endString = $newActiveItem->lockEndDateTime->format($dateTimeFormat);
        $info = "Replaced $curCardCode with $availCardCode. You may use this option again after " .
                $endString;
        self::_communicateCheatingOutcome($pId, new CheatOutcomeDto(CheatDtoType::ItemLog, $info));
    }
    /**
     * 
     * @param type $playerId
     * @param CheatOutcomeDto[] $messages
     */
    public static function _communicateCheatingOutcome($playerId, $messages) {
        $ex = Context::GetQEx;
        QueueManager::SendToPlayer($ex, $playerId, $messages);        
    }

}

?>
