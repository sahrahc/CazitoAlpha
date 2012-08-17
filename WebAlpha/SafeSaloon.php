<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
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
        <title>Welcome to the Cazito saloon</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="CSS/SafeSaloon.css" />
        <script src="../../Libraries/jQuery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/jquery.cookies.2.2.0.min.js" type="text/javascript"></script>
    </head>
    <body>

        <div id="top">
            <h1 id="casinoTableHeader" style="margin-bottom: 0;">
                Casino Table
            </h1>
            <ul style="display:none">
                <li id="userPlayerId"></li>
            </ul>
        </div>
        <div id="lobbyId">
            <form id ="tables">
                <label style="color:white;font-size:large;font-weight:bold">
                    Select your table:
                </label><br />
                <select id="tableSizeId" size="7">
                    <option value="table1000" selected>1,000 Table
                    <option value="table5000">5,000 Table
                    <option value="table10000">10,000 Table
                    <option value="table50000">50,000 Table
                    <option value="table100000">100,000 Table
                    <option value="table500000">500,000 Table
                    <option value="table1000000">1,000,000 Table
                </select>
                <br />
                <input type="submit" value="Enter" onclick="return enterTable()" />
            </form>
        </div>
        <script src="JS/SafeSaloon.js" type="text/javascript"></script>
    </body>
</html>
