<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function showCasinoTableValues($par, $casinoTableDtoEncoded) {
    echo "Encoded parameter: $par <br />";
    echo "Encoded return object: $casinoTableDtoEncoded<br />";
}

/* * *********************************************************************** */

function showGameInstanceSetupValues($par, $gameInstanceSetupDto) {
    echo "Encoded parameter: $par <br />";
    echo "Encoded return object: $gameInstanceSetupDto<br />";
}

/* * *********************************************************************** */

function showGameStatusDto($par, $gameStatusDto) {
    echo "Encoded parameter: $par <br /><br />";
    echo "Encoded return object: $gameStatusDto <br /> <br />";
}

/* * *********************************************************************** */

function showEventMessage($par, $message) {
    echo "Encoded parameter: $par <br />";
    echo "Encoded return object: $message<br />";
}

/* * *********************************************************************** */

function showPokerMove($par, $actionResultArray) {
    echo "Encoded parameter: $par <br />";
    echo "Encoded return object: $actionResultArray<br />";
}

/* * *********************************************************************** */

function showGameEventDto($gameEventDtoEncoded) {
    echo "Encoded return object: $gameEventDtoEncoded<br />";

    $gameEventDto = json_decode($gameEventDtoEncoded);

    echo 'The session Id is ' . $gameEventDto->gameSessionId . '<br />';
    echo 'The instance Id is ' . $gameEventDto->gameInstanceId . '<br />';
    if (is_null($gameEventDto->playerChangedState)) {
        die('There is player changed state. <br />');
    } else {
        $playerStatusDto = $gameEventDto->playerChangedState;
        echo 'The player id is ' . $playerStatusDto->playerId . ' <br />';
        echo "The player's stake is " . $playerStatusDto->currentStake . ' <br />';
        echo "The player's position is " . $playerStatusDto->turnNumber . ' <br />';
        echo "The player's status is " . $playerStatusDto->status . ' <br />';

        if (is_null($gameEventDto->playerChangedState->lastPlayerAction)) {
            echo 'There is no last player action. <br />';
        } else {
            echo 'The last player action is as follows: <br />';
            $playerActionDto = $gameEventDto->playerChangedState->lastPlayerAction;
            echo 'The game instance id is ' . $playerActionDto->gameInstanceId . ' <br />';
            echo 'The player id is ' . $playerActionDto->playerId . ' <br />';
            echo "The poker action type is " . $playerActionDto->pokerActionType . ' <br />';
            echo "The action time is " . $playerActionDto->actionTime . ' <br />';
            echo "The action value is " . $playerActionDto->actionValue . ' <br />';
        }
    }
    if (is_null($gameEventDto->communityCards)) {
        echo "There are no community cards. <br />";
    } else {
        echo 'The community cards are: <br/> <ul>';
        $counter = 1;
        echo " Card 1 Index: " . $gameEventDto->communityCards[0]->cardIndex . '<br />';
        foreach ($gameEventDto->communityCards as $card) {
            echo '<li> Card Number: ' . $counter++;
            echo '<li> Card Index: ' . $card->cardIndex;
            echo '<li> Card Label: ' . $card->cardName;
        }
        echo '</ul> <br />';
    }
}

?>
