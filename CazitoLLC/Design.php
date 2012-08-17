<?php
// check if logged in
session_start();
if (!isset($_SESSION['myusername'])) {
    header("location:Login.php");
}
?>
<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Cazito LLC</title>
    </head>
    <body>
        <h1>Cazito LLC</h1>
        <h2>Design Diagrams v0.1 - July 27, 2012</h2>
          <!--<table style="border-width: 1px; border-style:groove;">-->
        <p>These are partial draft designs for poker playing service and event messaging. Not started yet are casino table management, inventory management, rewards earning and use services.</p>
        <br/>
        <h2>Models</h2>
        <table border="1">
            <tr>
                <th>Diagram</th>
                <th>Description</th>
            </tr>
            <tr>
                <td><a href="Images/Model_v0.1_DTOs.png">DTO's v0.1</a></td>
                <td>Data transfer objects (DTO's) used by The browser communicates with the back-end via the PokerPlayerService and EventMessageService API's, web services. The service operations are described below, this diagram only shows the data transfer objects.</td>
            </tr>
            <tr>
                <td><a href="Images/Model_v0.1_Domain.png">Domain Model v0.1</a></td>
                <td>Objects and data structures are based on the domain model.</td>
            </tr>
            <tr>
                <td><a href="Images/Model_v0.1_PokerActionDomainAndDto.png">Poker Action Domain and Dto Model</a></td>
                <td>A poker action move has a complex model because it results in four different types of results:  a change in the player state, identification of the next player and allowed moved, community cards being dealt or the end of the game. Business logic for users who time out or leave also adds to the complexity.</td>
            </tr>
            <tr>
                <td><a href="Images/Model_v0.1_HelperClasses.png">Helper Classes v0.1</a>
                </td>
                <td>Helper classes encapsulate the complexity of non-domain code such as retrieving data from the data store or API's for internal and external applications.</td>
            </tr>
        </table>
        <br />
        <h3>Web Service Operations</h3>
        <table border="1">
            <tr>       <th>PokerPlayerService Operation</th>
                <th>Description</th>
            </tr>
            <tr>
                <td>startPracticeSession</td>
                <td>Start a practice session and game against three other players whose actions are randomly generated. If the player name is not found in the system, the player is created.</td>
            </tr>
            <tr>
                <td>addUserToCasinoTable</td>
                <td>Add a user to a casino table. Creates the user and table if they do not exist.</td>
            </tr>
            <tr>
                <td>startGame</td>
                <td>Start a new game on a practice or live session.</td>
            </tr>
            <tr>
                <td>sendPlayerAction</td>
                <td>Used by the browser to send a user's action to the back-end.</td>
            </tr>
            <tr>
                <td>takeSeat NOT IMPLEMENTED</td>
                <td>Used by the browser to notify that a user accepted a seat. Only used if there is a message that a seat became available. A user who joins a table with an active game will be offered a seat after the game ends. If there is no active game, the user will be offered the next available seat.</td>
            </tr>
            <tr>
                <td>leaveTable NOT IMPLEMENTED</td>
                <td>Used by the browser to notify that a user left the table. This action may be detected if the user leaves in order to join another table or start a practice game.</td>
            </tr>
        </table>
    </body>
</html>
