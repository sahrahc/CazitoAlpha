<?php

/* Type: Object and partial response Dto.
 * Primary Table: none
 * Description: all the poker cards in a game, both community and player
 */

class PlayerHiddenCard {

    public $playerId;
    public $cardCode;
    public $cardPosition;

    function __construct($playerId, $cardCode, $cardPosition) {
        $this->playerId = $playerId;
        $this->cardCode = $cardCode;
        $this->cardPosition = $cardPosition;
    }

    /**
     * Add cards to the hidden list. Translates the card names to codes
     * @global type $pokerCardName
     * @param type $pId
     * @param type $cardNames
     * @return string array
     */
    public static function AddHiddenCards($pId, $cardNames) {
        global $pokerCardName;
        // start the cardposition with the number after the max
        $result = executeSQL('select max(CardPosition) MaxPos From PlayerHiddenCard', __FUNCTION__ . ": Error retriving max cardposition");
        $row = mysql_fetch_array($result);
        $maxPos = $row[0] == 0 ? 0 : (int)$row[0] + 1;
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
        /* REST
        $cardNames = self::GetHiddenCards($pId);
        $info = $itemType . " - Loaded cards on sleeve...";
        $messagesOut[0] = new CheatOutcomeDto(CheatDtoType::CheatedHidden, $cardNames);
        $messagesOut[1] = new CheatOutcomeDto(CheatDtoType::ItemLog, $info);
        CheatingHelper::_communicateCheatingOutcome($pId, $messagesOut);
         * 
         */
    }

    /**
     * Gets the list of hidden cards for a given player
     * @global type $pokerCardName
     * @param type $pId
     * @return string array
     */
    public static function GetHiddenCards($pId) {
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
        return $hiddenList;
    }

    public static function ResetSleeve($pId) {
        //$itemType = ItemType::LOAD_CARD_ON_SLEEVE;
        executeSQL("DELETE FROM PlayerHiddenCard WHERE PlayerId = $pId", __FUNCTION__ . "
            :Error deleting from PlayerHiddenCard where player is $pId");
    }

}
?>
