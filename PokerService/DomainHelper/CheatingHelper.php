<?php

// Configure logging
include_once(dirname(__FILE__) . '/../../../libraries/log4php/Logger.php');
Logger::configure(dirname(__FILE__) . '/../log4php.xml');

// Include Application Scripts
require_once(dirname(__FILE__) . '/../Metadata.php');

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
     * @param GameInstanceStatus $gInstStatus
     * @param string $suit
     * @param DateTime $currentDT
     * @return CheaterCardDto array
     */
    public static function getSuitForAllGameCards($pId, $gInstStatus, $suit, $currentDT) {
        global $dateTimeFormat;
        global $cHeartMarkerTimeOut;
        global $cClubMarkerTimeOut;
        global $cDiamondMarkerTimeOut;
        global $pokerCardName;

        $cheaterCardDtos = null;
        $gameCards = $gInstStatus->getInstanceGameCards();

        $counter = 0;
        foreach ($gameCards->playerHands as $pH) {
            $suitValue = null;
            $cardName = $pokerCardName[$pH->pokerCard1->cardCode];
            if (!is_null($suit) && strpos($cardName, $suit) !== false) {
                $suitValue = $suit;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 1,
                            null, $suitValue);

            // second card
            $suitValue = null;
            $cardName = $pokerCardName[$pH->pokerCard2->cardCode];
            if (!is_null($suit) && strpos($cardName, $suit) !== false) {
                $suitValue = $suit;
            }
            $cheaterCardDtos[$counter++] = new CheaterCardDto($pH->playerId, 2,
                            null, $suitValue);
        }
        /* ------------------------------------------------------------------------------ */
        // create record with time out
        $itemType = null;
        switch ($suit) {
            case 'hearts':
                $itemType = ItemType::HEART_MARKER;
                $timeOut = $cHeartMarkerTimeOut;
                break;
            case 'clubs':
                $itemType = ItemType::CLUB_MARKER;
                $timeOut = $cClubMarkerTimeOut;
                break;
            case 'diamonds':
                $itemType = ItemType::DIAMOND_MARKER;
                $timeOut = $cDiamondMarkerTimeOut;
                break;
            default;
                throw new Exception("Error in Cheating Item, can only mark clubs, diamonds and hearts, not " . $suit);
        }
        $lockEndDateTime = clone $currentDT;
        $lockEndDateTime->add(new DateInterval($timeOut));

        $sessionId = $gInstStatus->gameInstanceSetup->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId, $itemType, $currentDate);
        $activeItem->lockEndDateTime = $lockEndDateTime;
        $activeItem->isActive = 0; // instantaneous
        $activeItem->isAvailable = 0; // lock out period
        $activeItem->endDateTime = $currentDT;
        $activeItem->recordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $log = 'Marked ' . $suit . '. This item is available again at ' . $lockEndDateTime->format($dateTimeFormat) . '.';
        $msg = new CheaterMessage($log, CheatLogType::ItemLog, CheatDtoType::CARDS, $cheaterCardDtos);
        //return $cheaterCardDtos;
        return $msg;
        
    }

    /**
     * Replaces the playerCardNumber with a random ace.
     * @param GameInstanceStatus $gInstStatus
     * @param type $pId
     * @param type $pCardNum
     * @return CheaterCardDto
     */
    public static function pushRandomAce($pId, $gInstStatus, $pCardNum, $currentDT) {
        global $pokerCardName;

        $pokerCards = CardHelper::getPlayerCard($pId, $gInstStatus->id, $pCardNum);
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
        $itemType = ItemType::ACE_PUSHER;
        
        $sessionId = $gInstStatus->gameInstanceSetup->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId, $itemType, $currentDate);
        $activeItem->lockEndDateTime = $currentDT; // no lock out period
        $activeItem->isActive = 0;
        $activeItem->isAvailable = 1;
        $activeItem->endDateTime = $currentDT;
        $activeItem->gameInstanceId = $gInstStatus->id;
        $activeItem->recordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        $log = "Replaced card number $pCardNum with $cardName. You may push an ace again at any time";
        $dto = new CheaterCardDto($pId, $pCardNum, $cardName, $randSuit);
        $msg = new CheaterMessage($log, CheatLogType::ItemLog, CheatDtoType::HANDS, $dto);
        //return new CheaterCardDto($pId, $pCardNum, $cardName, $randSuit);
        return $msg;
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
    public static function startCardMarking($pId, $gSessionId, $currentDT) {
        global $cSocialSpotterTimeOut;
        global $cSocialSpotterDuration;
        global $dateTimeFormat;

        $itemType = ItemType::SOCIAL_SPOTTER;

        // FIXME: should be based on $statusDT for all items
        $lockEndDateTime = clone $currentDT;
        $lockEndDateTime->add(new DateInterval($cSocialSpotterTimeOut));
        $endDateTime = clone $currentDT;
        $endDateTime->add(new DateInterval($cSocialSpotterDuration));

        $activeItem = new PlayerActiveItem($pId, $gSessionId,
                        $itemType, $currentDT);
        $activeItem->lockEndDateTime = $lockEndDateTime; // no lock out period
        $activeItem->isActive = 1;
        $activeItem->isAvailable = 0;
        $activeItem->endDateTime = $endDateTime;
        $activeItem->recordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $dateString = $currentDT->format($dateTimeFormat);
        $endString = $endDateTime->format($dateTimeFormat);
        $lockEndString = $lockEndDateTime->format($dateTimeFormat);
        $log = "Activated Sally's Social Spotters on $dateString. Cards you see at this tale until $endString will be marked so you know the values in subsequent games. After $endString, this item wil be available again at $lockEndString.";
        $msg = new CheaterMessage($log, CheatLogType::ItemLog, null, null);
        return $msg;
    }

    /**
     * Gets the list of player Id's who have an un-ended social spotter.
     * @param GameInstance $gInstStatus
     * @return int array
     */
    public static function getPlayersActivelyMarking($gSessionId) {
        /* returns a list of player ids */
        /* possible for an item to expire in the future but set to inactive before that time */
        $leftStatus = PlayerStatusType::LEFT;
        $itemType = ItemType::SOCIAL_SPOTTER;
        $result = executeSQL("SELECT i.PlayerId FROM PlayerActiveItem i 
                INNER JOIN PlayerState ps on i.PlayerId = ps.PlayerId 
                AND i.GameSessionId = ps.GameSessionId
                WHERE ps.status != '$leftStatus' AND i.GameSessionId =
            $gSessionId AND ItemType = '$itemType' AND EndDateTime > now() AND IsActive = 1
                ", __FUNCTION__ . "
                : Error selecting active items for session $gSessionId");
        $playerIdList = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            $playerIdList[$counter++] = $row['PlayerId'];
        }
        return $playerIdList;
    }

    public static function getPlayerActiveItem($pId, $gSessionId, $itemType) {
        $result = executeSQL("SELECT * FROM PlayerActiveItem WHERE GameSessionId =
            $gSessionId AND PlayerId = $pId AND ItemType = '$itemType' AND EndDateTime > now()
                AND IsActive = 1", __FUNCTION__ . "
                : Error selecting active item for player $pId and session $gSessionId and
                type $itemType");
        $row = mysql_fetch_array($result);
        $startDateTime = new DateTime($row['StartDateTime']);
        $activeItem = new PlayerActiveItem($pId, $gSessionId, $itemType, $startDateTime);
        $activeItem->endDateTime = new DateTime($row['EndDateTime']);
        $activeItem->lockEndDateTime = new DateTime($row['LockEndDateTime']);
        $activeItem->isActive = $row['IsActive'];
        $activeItem->isAvailable = $row['IsAvailable'];
        $activeItem->numberCards = $row['NumberCards'];
        return $activeItem;
    }

    /**
     * To be called at the end of a game, if it is a cheating game, although it could be called
     * earlier.
     * Adds all the game cards to a visible list which is specific to a player for all players
     * who have an active Sally Spotter item
     * Must validate if the user has this item activated 
     * @param type $gInstStatus 
     */
    public static function markGameCards($gInstStatus) {
        // FIXME: not all the community cards were seen if the game was restarted before the prevoius finished.
        // first get list of players in instance that have active Social Spotters
        $gameSessionId = $gInstStatus->gameInstanceSetup->gameSessionId;
        $playerIdList = self::getPlayersActivelyMarking($gameSessionId);
        $instanceCardList = CardHelper::getCardCodesForInstance($gInstStatus->id, false);

        // insert record on Player Visible Card for each such player for each instance card
        if ($playerIdList == null) {
            return null;
        }
        foreach ($playerIdList as $playerId) {
            $playerCardCodes = self::getVisibleCardCodes($playerId, $gameSessionId);
            if (is_null($playerCardCodes)) {
                $newCards = $instanceCardList;
            } else {
                $newCards = array_diff($instanceCardList, $playerCardCodes);
            }
            // insert
            $counter = count($playerCardCodes);
            foreach ($newCards as $cardCode) {
                executeSQL("INSERT INTO PlayerVisibleCard (PlayerId, CardCode, GameSessionId)
                    VALUES ($playerId, '$cardCode', $gameSessionId)", __FUNCTION__ . ": Error
                        inserting into PlayerVisibleCard player $playerId");
                $counter++;
            }
        }
    }

    /**
     * To be called at the start of a game if it is a cheating game.
     * This function returns the list of other players' cards that the user knows the value of
     * Asynchronous - NEEDS TO COMMUNICATE
     * @global type $pokerCardName
     * @param type $gInstStatus
     * @return type 
     */
    public static function revealMarkedCards($gInstStatus) {
        global $pokerCardName;
        // first get list of players in instance that have active Social Spotters
        $gameSessionId = $gInstStatus->gameInstanceSetup->gameSessionId;
        $playerIdList = self::getPlayersActivelyMarking($gameSessionId);
        if ($playerIdList == null) {
            return;
        }
        // get list of all instance cards
        $gameCards = $gInstStatus->getInstanceGameCards($gInstStatus->id);
        $playerHands = $gameCards->playerHands;
        foreach ($playerIdList as $playerId) {
            $playerCardCodes = self::getVisibleCardCodes($playerId, $gameSessionId);
            if (is_null($playerCardCodes)) {
                continue;
            }
            // go through everyone's hands
            $cheaterListDto = null;
            $counter = 0;
            // match card with PlayerVisibleCard and add to return list if match
            foreach ($playerHands as $pH) {
                $code1 = $pH->pokerCard1->cardCode;
                if (in_array($code1, $playerCardCodes)) {
                    $cheaterListDto[$counter++] = new CheaterCardDto($pH->playerId, 1, $pokerCardName[$code1], null);
                }
                $code2 = $pH->pokerCard2->cardCode;
                if (in_array($code2, $playerCardCodes)) {
                    $cheaterListDto[$counter++] = new CheaterCardDto($pH->playerId, 2, $pokerCardName[$code2], null);
                }
            }
            // no need to check whether the player should receive message, getPlayersActivelyMarking does it
            $encodedDto = is_null($cheaterListDto) ? 'null' : json_encode($cheaterListDto);
            if (!is_null($cheaterListDto)) {
                $eventType = CheatDtoType::CARDS;
                self::log()->Debug(__FUNCTION__ . " - Matched list: " . $encodedDto);
            }
            $count = is_null($cheaterListDto) ? 0 : count($cheaterListDto);
            $log = "Looked for marked cards (Shelvin Shuffler's) found " . $count;
            $msg = new CheaterMessage($log, CheatLogType::ItemLog, $eventType, $cheaterListDto);
            return $msg;
        }
    }

    /**
     *
     * @global type $cRiverShufflerTimeOut
     * @param type $pId
     * @param type $gameInstance
     * @param type $currentDT
     * @return string (array of 1)
     */
    public static function cheatLookRiverCard($pId, $gameInstance, $currentDT) {
        global $cRiverShufflerTimeOut;
        global $dateTimeFormat;
        global $pokerCardName;

        $itemType = ItemType::RIVER_SHUFFLER;
        $gInstId = $gameInstance->id;
        // get the river card; the following returns sorted
        $result = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId = -1 and PlayerCardNumber = 5 ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
        $row = mysql_fetch_array($result);
        $curDeckPosition = $row['DeckPosition'];

        $cardCode = $row['CardCode'];
        self::$log()->Debug(__FUNCTION__ . " - River card for instance $gInstId is " . $cardCode);
        $cardNameListCode = null;
        if (!is_null($cardCode)) {
            $cardNameListCode[0] = $pokerCardName[$cardCode];
        }
        self::log()->Debug(__FUNCTION__ . " - River card name for instance $gInstId is " . json_encode($cardNameListCode));
        // FIXME: should be based on $statusDT for all items
        $lockEndDateTime = clone $currentDT;
        $lockEndDateTime->add(new DateInterval($cRiverShufflerTimeOut));
        $endDateTime = clone $lockEndDateTime;

        $sessionId = $gameInstance->gameInstanceSetup->gameSessionId;
        $activeItem = new PlayerActiveItem($pId, $sessionId,
                        $itemType, $currentDT);
        $activeItem->lockEndDateTime = $lockEndDateTime; // no lock out period
        $activeItem->isActive = 1;
        $activeItem->isAvailable = 0;
        $activeItem->endDateTime = $endDateTime;
        $activeItem->gameInstanceId = $gameInstance->id;
        $activeItem->recordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $dateString = $currentDT->format($dateTimeFormat);
        $lockEndString = $lockEndDateTime->format($dateTimeFormat);
        $log = "Activated Shelvin's Shuffler on $dateString. You may swap the river card for the current game for the next card in the deck. The river card may not the one you see on the 'Next' section if another player used an item. This item wil be available again at $lockEndString.";
        if ($cardNameListCode != null) {
            $eventType = CheatDtoType::NEXT;
            $eventData = $cardNameListCode;
        }
        $msg = new CheaterMessage($log, CheatLogType::ItemLog, $eventType, $eventData);
        /* ------------------------------------------------------------------------------ */
        return $cardNameListCode;
    }

    /**
     *
     * @param type $pId 
     */
    public static function cheatSwapRiverCard($pId, $gInstStatus) {
        global $dateTimeFormat;
        // validate the item is active
        $itemType = ItemType::RIVER_SHUFFLER;
        $sessionId = $gInstStatus->gameInstanceSetup->gameSessionId;

        $activeItem = self::getPlayerActiveItem($pId, $sessionId, $itemType);
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

        $result = executeSQL("SELECT CardCode, DeckPosition FROM GameCard where GameInstanceId
            = $gInstId AND PlayerId = -1 and PlayerCardNumber = 5 ORDER BY DeckPosition LIMIT 1", __FUNCTION__ . ":
                Error selecting first unassigned game card for instance $gInstId");
        $row = mysql_fetch_array($result);
        $curDeckPosition = $row['DeckPosition'];
        $curCardCode = $row['CardCode'];

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
        $newActiveItem->itemType = ItemType::RIVER_SHUFFLER_USE;
        $newActiveItem->recordItemUse();

        /* response is immediate, no need to check whether the message
         * is sent to players who left session
         */
        /* ------------------------------------------------------------------------------ */
        $endString = $newActiveItem->lockEndDateTime->format($dateTimeFormat);
        $log = "Replaced $curCardCode with $availCardCode. You may use this option again after " .
                $endString;
        $msg = new CheaterMessage($log, CheatLogType::ItemLog, null, null);
        return $msg;
    }

    /**
     * Add cards to the hidden list. Translates the card names to codes
     * @global type $pokerCardName
     * @param type $pId
     * @param type $cardNames
     * @return string array
     */
    public static function addHiddenCards($pId, $cardNames) {
        global $pokerCardName;
        // start the cardposition with the number after the max
        $result = executeSQL('select max(CardPosition) MaxPos From PlayerHiddenCard', __FUNCTION__ . ": Error retriving max cardposition");
        $row = mysql_fetch_array($result);
        $maxPos = $row[0] == 0 ? 0 : $row[0] + 1;
        for ($i = 0; $i < count($cardNames); $i++) {
            $cardCode = array_search($cardNames[$i], $pokerCardName);
            if ($cardCode != false) {
                $cardPosition = $i + $maxPos;
                executeSQL("INSERT INTO PlayerHiddenCard(PlayerId, CardCode, CardPosition)
                VALUES ($pId, '$cardCode', $cardPosition)", __FUNCTION__ . "
                    : Error inserting $cardCode on PlayerHiddenCard for $pId");
            }
        }
        /* ------------------------------------------------------------------------------ */
        return self::getHiddenCards($pId);
    }

    /**
     * Gets the list of hidden cards for a given player
     * @global type $pokerCardName
     * @param type $pId
     * @return string array
     */
    public static function getHiddenCards($pId) {
        global $pokerCardName;

        $result = executeSQL("SELECT * FROM PlayerHiddenCard WHERE PlayerId = $pId
                ORDER BY CardPosition", __FUNCTION__ . "
                : Error insert into player hidden card for player $pId");
        $counter = 0;
        $hiddenList = null;
        while ($row = mysql_fetch_array($result)) {
            $cardName = $pokerCardName[$row['CardCode']];
            $hiddenList[$counter++] = $cardName;
        }
        //return $hiddenList;
        $msg = new CheaterMessage(null, null, CheatDtoType::HIDDEN, $hiddenlist);
    }

    /**
     *
     * @param type $pId
     * @return type 
     */
    public static function getVisibleCardCodes($pId, $gSessionId) {
        $result = executeSQL("SELECT CardCode FROM PlayerVisibleCard WHERE PlayerId = $pId
                AND GameSessionId = $gSessionId", __FUNCTION__ . ":
                Error selecting player visible card for player $pId and session $gSessionId");
        $hiddenList = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            $hiddenList[$counter++] = $row['CardCode'];
        }
        return $hiddenList;
    }

    public static function resetSleeve($pId) {
        $itemType = ItemType::LOAD_CARD_ON_SLEEVE;
        executeSQL("DELETE FROM PlayerHiddenCard WHERE PlayerId = $pId", __FUNCTION__ . "
            :Error deleting from PlayerHiddenCard where player is $pId");
        //if ($sendMessage) {
        //    $this->communicateCheatingInfo($pId, null, CheatDtoType::HIDDEN, $itemType);
        //}
    }

    public static function resetVisible($pId) {
        $itemType = ItemType::POKER_PEEKER;
        executeSQL("DELETE FROM PlayerVisibleCard WHERE PlayerId = $pId", __FUNCTION__ . "
            :Error deleting from PlayerVisibleCard where player is $pId");
        //if ($sendMessage) {
        //    $this->communicateCheatingInfo($pId, null, CheatDtoType::NEXT, $itemType);
        //}
    }

    public static function addVisibleCardCodes($pId, $gameSessionId, $cardCodes) {
        global $dateTimeFormat;
        $endDateTime = new DateTime();
        $endDateTime->add(new DateInterval('PT10M'));
        $endString = $endDateTime->format($dateTimeFormat);
        //$csv = implode(",", $cardCodes);
        for ($i = 0; $i < count($cardCodes); $i++) {
            $code = $cardCodes[$i];
            $query = "INSERT INTO PlayerVisibleCard (PlayerId, CardCode, GameSessionId,
            ExpirationDateTime) VALUES ($pId, '$code', $gameSessionId, '$endString')";
            executeSQL($query, __FUNCTION__ . ": Error inserting into PlayerVisibleCard where player is $pId
                and session is $gameSessionId");
        }
    }

    public static function removeVisibleCardCodes($pId, $gameSessionId, $cardCodes) {
        //$csv = implode(",", $cardCodes);
        $csv = "";
        for ($i = 0; $i < count($cardCodes); $i++) {
            $csv = $csv . "'" . $cardCodes[$i] . "',";
        }
        $csv = $csv . "''";
        $query = "DELETE FROM PlayerVisibleCard WHERE PlayerId = $pId AND CardCode in ($csv)
            AND GameSessionId = $gameSessionId";
        executeSQL($query, __FUNCTION__ . ": Error deleting from PlayerVisibleCard where player is $pId");
        echo $query;
    }

    /**
     * Traverses the list of active items and sets status flags such as isActive and isAvailable
     * Any items that are changed generate an queued event for the change only
     * Asynchronous - NEEDS TO COMMUNICATE
     */
    public static function updateEndedItems() {
        global $dateTimeFormat;
        $statusDateTime = date($dateTimeFormat);
        /* if end datetime reached: set the IsActive to 0 */
        $result = executeSQL("SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN updPlayerState ps ON i.PlayerId = ps.PlayerId 
                WHERE IsActive = 1 AND
            EndDateTime <= '$statusDateTime'", __FUNCTION__ . ": Error selecting ended
                active items");
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            // send communication
            $playerId = $row["PlayerId"];
            $itemType = $row["ItemType"];
            $lockEndDate = $row["LockEndDateTime"];
            $sessionId = $row["GameSessionId"];
            // communicate only if the user is in the same session and not left
            $playerSessionId = $row["PlayerSessionId"];
            $status = $row["Status"];
            if ($playerSessionId == $sessionId && $status != PlayerStatusType::LEFT) {
                $log = "Item $itemType has ended. You may use this again after $lockEndDate";
                $msgList[$counter]->msg = new CheaterMessage($log, CheatLogType::ItemEnd, null, null);
                $msgList[$counter]->playerid = $playerId;
                $msgList[$counter]->gameSessionId = $playerSessionId;
                $counter +=1;
            }
            executeSQL("UPDATE PlayerActiveItem SET IsActive = 0 WHERE IsActive = 1 AND
                PlayerId = $playerId AND ItemType = '$itemType' AND GameSessionId = $sessionID"
                    , __FUNCTION__ . ": Error updating item to inactive for player $playerId
                    session $gSessionId and item $itemType");
        }
        return $msgList;
    }
    /* if locked end reached: delete record          */
    public static function updateUnlockedItems() {
        // FIXME: should record on log
        executeSQL("SELECT i.*, ps.GameSessionId AS PlayerSessionId, 
                ps.GameInstanceId AS PlayerInstanceId, ps.status AS Status 
                FROM PlayerActiveItem i 
                LEFT JOIN PlayerState ps ON i.PlayerId = ps.PlayerId 
                WHERE LockEndDateTime <= '$statusDateTime'", __FUNCTION__ . "
                    : Error selecting items past the locked date");
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            // send communication
            $playerId = $row["PlayerId"];
            $itemType = $row["ItemType"];
            $lockEndDate = $row["LockEndDateTime"];
            $sessionId = $row["GameSessionId"];
            // communicate only if the user is in the same session and not left
            $playerSessionId = $row["PlayerSessionId"];
            $status = $row["Status"];
            if ($playerSessionId == $sessionId && $status != PlayerStatusType::LEFT) {
                $msgList[$counter]->msg = new CheaterMessage(null, null, CheatLogType::ItemUnlock, $itemType);
                $msgList[$counter]->playerId = $playerId;
                $msgList[$counter]->gameSessionId = $playerSessionId;
                //$this->communicateCheatingInfo($playerId, $sessionId, CheatLogType::ItemUnlock, $itemType);
            }
            executeSQL("DELETE FROM PlayerActiveItem WHERE PlayerId = $playerId AND
                 $itemType = '$itemType' and GameSessionId = $sessionId", __FUNCTION__ . ": Error deleting (unlocking) item for player $playerId AND
                 $itemType = '$itemType' and GameSessionId = $sessionId");
        }
        return $msgList;
    }

}

?>
