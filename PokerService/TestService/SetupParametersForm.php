<?php
// check if logged in
session_start();
if (!isset($_SESSION['myusername'])) {
    $_SESSION['srcLocation'] = 'LoadHiddenCardForm.php';
    header("location:Login.php");
}
?>
<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <h1>This form allows you to load or remove all the cards within a suit for a given player</h1>
        <form action="LoadHiddenCardAction.php" method="post">
            Player Name: <input type="text" name="playerName" /><br />
            Game Session Id: <input type="text" name="gameSessionId" /><br />
            <div style="margin:10px 10px">
            Suit:<br />
            <select name="suitType" size="4">
                <option value="hearts">Hearts
                <option value="clubs">Clubs
                <option value="diamonds">Diamonds
                <option value="spades">Spades
            </select><br />
            </div>
            <div style="margin:10px 10px">
            Action Type:<br />
            <select name="actionType" size="2">
                <option value="add">Add
                <option value="remove">Remove
            </select><br />
            </div>
            <input type="submit" name="submit"/>
        </form>
    </body>
</html>
