<?php

/* GET THE session ID and player Id from the previous test! */

include('..\PokerPlayerService.php');
include('showObject.php');

echo '******************************************************<br />';
echo 'TEST: endGame with session from previous test! <br />';
// FIXME: may add a text box for the user to enter session number
$gameInstanceId = 43;
$playerId = 1;
// not json encoded
$par = array("gameInstanceId"=>$gameInstanceId, 
    "playerId"=>$playerId);
echo "Encoded parameter (show only): " . json_encode($par) . " <br />";
$gameResultDto = endGame($par);
showGameResult($gameResultDto);

function showGameResult($gameResultDto) {
    
    echo 'The status date time is ' . $gameResultDto->statusDateTime . '<br />';
    echo 'The winning player id is ' . $gameResultDto->winningPlayerId . '<br />';
    if (is_null($gameResultDto->playerHands)){
        echo 'ERROR - There is no player card information <br />';
    }
    else {
        foreach ($gameResultDto->playerHands as $pCard) {
            echo 'The player cards are: <br/> <ul>';
            if (is_null($pCard->pokerCard1)){
                echo '<li>ERROR - There is no player card information for first card. <br />';
            }
            else {
                echo "<li>First Card: <ul>";
                echo '<li> The player id is ' . $pCard->playerId;
                echo '<li> The instance id is ' . $pCard->gameInstanceId;
                echo '<li>The first card index is ' . $pCard->pokerCard1->cardIndex . ' <br />';
                echo '<li>The first card label is ' . $pCard->pokerCard1->cardName . ' <br />';
                echo "</ul>";
            }
            if (is_null($pCard->pokerCard2)){
                echo '<li>ERROR - There is no player card information for second card. <br />';
            }
            else {
                echo "<li>Second Card: <ul>";
                echo '<li>The second card index is ' . $pCard->pokerCard2->cardIndex . ' <br />';
                echo '<li>The second card label is ' . $pCard->pokerCard2->cardName . ' <br />';
                echo "</ul>";
            }
            echo '<li>The poker hand type is ' . $pCard->pokerHandType . ' <br />';
            echo '<li>User has winning hand?' . $pCard->isWinningHand . ' <br />';
            echo '</ul> <br />';    
        }	
    }
}

?>
