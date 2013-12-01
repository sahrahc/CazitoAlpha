<?php

include("..\PokerPlayerData.php");
/*
 * Test that encode and decode work properly for every objects
 * The objects are from the operation tests (e.g., sendPlayerActionTest)
 */

echo "*******************************************************************<br />";
echo "Testing addUserToGamingSession: return object<br />";
$gameSessionDto = new CasinoTableDto();
$gameSessionDto->gameSessionId = 123;
$gameSessionDto->gameSessionId = 456;
global $dateTimeFormat;
$gameSessionDto->sessionStart = date($dateTimeFormat);
$gameSessionDto->playerDtos = array(
    new PokerPlayer(999, 'First Player', 0, 'Avatar_user1.jpeg'),
    new PokerPlayer(1111, 'Second Player', 1, 'Avatar_user2.jpeg'),
    new PokerPlayer(2222, 'Third Player', 2, 'Avatar_user3.jpeg'),
    new PokerPlayer(3333, 'Fourth Player', 3, 'Avatar_user4.jpeg'));

$encodedGameSessionDto = json_encode($gameSessionDto);
echo "Json version: $encodedGameSessionDto <br />";
$decodedGameInstance = json_decode($encodedGameSessionDto);

echo "The game session id should be $gameSessionDto->gameSessionId 
        and is: . $decodedGameInstance->gameSessionId . <br />";
echo "The game instance id should be $gameSessionDto->gameInstanceId 
        and is: $decodedGameInstance->gameInstanceId <br />";
echo "The game start date time is $gameSessionDto->sessionStart 
        and should be $decodedGameInstance->sessionStart <br />"; 
echo 'There should be ' . count($gameSessionDto->playerDtos) .
        ' players in this session and there are ' . count($decodedGameInstance->playerDtos)
        . '<br /> <br />'; 

for ($i = 0; $i< count($gameSessionDto->playerDtos); $i++) {
    echo 'Information for player # ' . $i . ' is: <br />'; 
    echo '    Player id should be : ' . $gameSessionDto->playerDtos[$i]->playerId .
            ' and is ' . $decodedGameInstance->playerDtos[$i]->playerId . '<br />';
    echo '    Player name should be: ' . $gameSessionDto->playerDtos[$i]->playerName .
            ' and is ' . $decodedGameInstance->playerDtos[$i]->playerName . '<br />';
    echo '    Player image should be : ' . $gameSessionDto->playerDtos[$i]->playerImageUrl .
            ' and is ' . $decodedGameInstance->playerDtos[$i]->playerImageUrl . '<br />';
    echo '    Position in table should be: ' . $gameSessionDto->playerDtos[$i]->playerPosition .
            ' and is ' . $decodedGameInstance->playerDtos[$i]->playerPosition . '<br /> <br />';
}
?>
