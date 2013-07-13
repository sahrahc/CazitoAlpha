<?php

/* Type: 
 * Primary Table: none
 * De
 * cription: all the poker cards in a game, both community and player
 */

class PlayerVisibleCard {

    public $playerId;
    public $cardCode;
    public $expirationDateTime;
    
    function __construct($playerId, $cardCode, $expDT) {
        $this->playerId = $playerId;
        $this->cardCode = $cardCode;
        $this->expirationDateTime = $expDT;
    }

    /**
     * To be called at the end of a game, if it is a cheating game, although it could be called
     * earlier.
     * Adds all the game cards to a visible list which is specific to a player for all players
     * who have an active Sally Spotter item
     * Must validate if the user has this item activated 
     * @param type $gInstStatus 
     */
    public static function AddVisibleCards($gInstStatus) {
        // FIXME: not all the community cards were seen if the game was restarted before the prevoius finished.
        // first get list of players in instance that have active Social Spotters
        $gameSessionId = $gInstStatus->gameSessionId;
        $itemType = ItemType::SOCIAL_SPOTTER;
        $playerIdList = PlayerACtiveItem::GetPlayersWithItemType($gameSessionId, $itemType);
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
            // no need to send info or result, that is done at the beginning of a game
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
    public static function RevealMarkedCards($gInstStatus, $itemType) {
        global $pokerCardName;
        // first get list of players in instance that have active Social Spotters
        $gameSessionId = $gInstStatus->gameSessionId;
        $playerIdList = PlayerActiveItem::GetPlayersWithItemType($gameSessionId, $itemType);
        if ($playerIdList == null) {
            return;
        }
        // get list of all instance cards
        $gameCards = CardHelper::getGameCardsForInstance($gInstStatus->id);
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
            $messagesOut = array();
            if (!is_null($cheaterListDto)) {
                $eventType = CheatDtoType::CheatedCards;
                array_push($messagesOut, new CheatOutcomeDto($eventType, $cheaterListDto));
                self::log()->Debug(__FUNCTION__ . " - Matched list: " . $encodedDto);
            }
            $count = is_null($cheaterListDto) ? 0 : count($cheaterListDto);
            $info = "$itemType - looked for marked cards fand found $count";
            array_push($messagesOut, new CheatOutcomeDto($itemType, $info));
            return $messagesOut;
        }
    }

    public static function ResetVisible($pId) {
        executeSQL("DELETE FROM PlayerVisibleCard WHERE PlayerId = $pId", __FUNCTION__ . "
            :Error deleting from PlayerVisibleCard where player is $pId");
    }

    /**
     * Made public for testing purposes only
     * @global type $dateTimeFormat
     * @param type $pId
     * @param type $gameSessionId
     * @param type $cardCodes
     */
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

    /**
     * Public for testing only
     * @param type $pId
     * @return type 
     */
    public static function getVisibleCardCodes($pId, $gSessionId) {
        $result = executeSQL("SELECT CardCode FROM PlayerVisibleCard WHERE PlayerId = $pId
                AND GameSessionId = $gSessionId", __FUNCTION__ . ":
                Error selecting player visible card for player $pId and session $gSessionId");
        $visibleList = null;
        $counter = 0;
        while ($row = mysql_fetch_array($result)) {
            $visibleList[$counter++] = $row['CardCode'];
        }
        return $visibleList;
    }

    /**
     * Public for testing only
     * @param type $pId
     * @param type $gameSessionId
     * @param type $cardCodes
     */
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

}
?>
