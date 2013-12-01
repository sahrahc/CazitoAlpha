<?php

/* GET THE session ID and player Id from the previous test! */

include('..\PokerPlayerService.php');
include('showObject.php');

echo '******************************************************<br />';
echo 'TEST: getOtherPlayerAction with session from previous test! <br />';
// FIXME: may add a text box for the user to enter session number
$gameInstanceId = 43;
$currentPlayerId = 1;
$par = json_encode(array("gameInstanceId"=>$gameInstanceId,
        "requestingPlayerId"=>$currentPlayerId));
echo "Encoded parameter: $par <br />";
$gameEventDtoEncoded = getGameStatus($par);
showGameEventDto($gameEventDtoEncoded);

?>
