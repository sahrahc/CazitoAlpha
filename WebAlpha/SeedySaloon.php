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
        <link rel="stylesheet" type="text/css" href="CSS/seedySaloon.css" />
        <link rel="Stylesheet" type="text/css" href="../../Libraries/jQuery/css/ui-lightness/jquery-ui-1.8.22.custom.css"/>
        <script src="../../Libraries/jQuery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/js/jquery.tools.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/js/jquery-ui-1.8.22.custom.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/js/jquery.cookies.2.2.0.min.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="../../Libraries/jcarousel/skins/tango/cardItems-skin.css" />
        <script src="../../Libraries/jcarousel/lib/jquery.jcarousel.min.js" type="text/javascript"></script>
    </head>
    <body>
        <!-- dialogs -->
        <div id="loginDialog" title="Enter your player name">
            <p>Enter your player name, so we can match your past play or leave blank to enter as guest.</p>
            <form id="PlayerNameForm">
                <input type="text" id="playerNameText" value="Guest" />
            </form>
        </div>

        <div id="top">
            <div id="casinoTableHeader"> <p>
                    Welcome to the Dead Man's Texas Hold Em!
                </p></div>
            <div id="nav">
                <input type="submit" onclick="logout()" value="Logout" />
                </div>
        </div>
        <div id="lobbyId">
            <!-- cheaters tools and information on the left -->
            <div id="cardSelectorDialog" title="Click on a card to add to your sleeve">
                <p>Development note: You may select multiple cards, however only the first two will be available when you play a game. Cannot un-select cards, please cancel and try again instead.</p>
                <div id="cardSelector">
                    <div id="heartsList">
                        <ul id="heartsCarousel" class="jcarousel-skin-card">
                            <li id='hearts_A'
                                onmouseover="dimCard('hearts_A')"
                                onmouseout="unDimCard('hearts_A')"
                                onclick="addToSleeve('hearts_A')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_A_small.png" alt="Ace of Hearts" /></li>
                            <li id='hearts_2'
                                onmouseover="dimCard('hearts_2')"
                                onmouseout="unDimCard('hearts_2')"
                                onclick="addToSleeve('hearts_2')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_2_small.png" alt="2 of Hearts" /></li>
                            <li id='hearts_3'
                                onmouseover="dimCard('hearts_3')"
                                onmouseout="unDimCard('hearts_3')"
                                onclick="addToSleeve('hearts_3')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_3_small.png" alt="3 of Hearts" /></li>
                            <li id='hearts_4'
                                onmouseover="dimCard('hearts_4')"
                                onmouseout="unDimCard('hearts_4')"
                                onclick="addToSleeve('hearts_4')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_4_small.png" alt="4 of Hearts" /></li>
                            <li id='hearts_5'
                                onmouseover="dimCard('hearts_5')"
                                onmouseout="unDimCard('hearts_5')"
                                onclick="addToSleeve('hearts_5')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_5_small.png" alt="5 of Hearts" /></li>
                            <li id='hearts_6'
                                onmouseover="dimCard('hearts_6')"
                                onmouseout="unDimCard('hearts_6')"
                                onclick="addToSleeve('hearts_6')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_6_small.png" alt="6 of Hearts" /></li>
                            <li id='hearts_7'
                                onmouseover="dimCard('hearts_7')"
                                onmouseout="unDimCard('hearts_7')"
                                onclick="addToSleeve('hearts_7')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_7_small.png" alt="7 of Hearts" /></li>
                            <li id='hearts_8'
                                onmouseover="dimCard('hearts_8')"
                                onmouseout="unDimCard('hearts_8')"
                                onclick="addToSleeve('hearts_8')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_8_small.png" alt="8 of Hearts" /></li>
                            <li id='hearts_9'
                                onmouseover="dimCard('hearts_9')"
                                onmouseout="unDimCard('hearts_9')"
                                onclick="addToSleeve('hearts_9')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_9_small.png" alt="9 of Hearts" /></li>
                            <li id='hearts_10'
                                onmouseover="dimCard('hearts_10')"
                                onmouseout="unDimCard('hearts_10')"
                                onclick="addToSleeve('hearts_10')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_10_small.png" alt="10 of Hearts" /></li>
                            <li id='hearts_J'
                                onmouseover="dimCard('hearts_J')"
                                onmouseout="unDimCard('hearts_J')"
                                onclick="addToSleeve('hearts_J')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_J_small.png" alt="Jack of Hearts" /></li>
                            <li id='hearts_Q'
                                onmouseover="dimCard('hearts_Q')"
                                onmouseout="unDimCard('hearts_Q')"
                                onclick="addToSleeve('hearts_Q')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_Q_small.png" alt="Queen of Hearts" /></li>
                            <li id='hearts_K'
                                onmouseover="dimCard('hearts_K')"
                                onmouseout="unDimCard('hearts_K')"
                                onclick="addToSleeve('hearts_K')">
                                <img class="cImg" src="../../../images/PokerCard_hearts_K_small.png" alt="King of Hearts" /></li>
                        </ul>
                    </div>
                    <div id="diamondsList">
                        <ul id="diamondsCarousel" class="jcarousel-skin-card">
                            <li id='diamonds_A'
                                onmouseover="dimCard('diamonds_A')"
                                onmouseout="unDimCard('diamonds_A')"
                                onclick="addToSleeve('diamonds_A')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_A_small.png" alt="Ace of diamonds" /></li>
                            <li id='diamonds_2'
                                onmouseover="dimCard('diamonds_2')"
                                onmouseout="unDimCard('diamonds_2')"
                                onclick="addToSleeve('diamonds_2')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_2_small.png" alt="2 of diamonds" /></li>
                            <li id='diamonds_3'
                                onmouseover="dimCard('diamonds_3')"
                                onmouseout="unDimCard('diamonds_3')"
                                onclick="addToSleeve('diamonds_3')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_3_small.png" alt="3 of diamonds" /></li>
                            <li id='diamonds_4'
                                onmouseover="dimCard('diamonds_4')"
                                onmouseout="unDimCard('diamonds_4')"
                                onclick="addToSleeve('diamonds_4')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_4_small.png" alt="4 of diamonds" /></li>
                            <li id='diamonds_5'
                                onmouseover="dimCard('diamonds_5')"
                                onmouseout="unDimCard('diamonds_5')"
                                onclick="addToSleeve('diamonds_5')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_5_small.png" alt="5 of diamonds" /></li>
                            <li id='diamonds_6'
                                onmouseover="dimCard('diamonds_6')"
                                onmouseout="unDimCard('diamonds_6')"
                                onclick="addToSleeve('diamonds_6')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_6_small.png" alt="6 of diamonds" /></li>
                            <li id='diamonds_7'
                                onmouseover="dimCard('diamonds_7')"
                                onmouseout="unDimCard('diamonds_7')"
                                onclick="addToSleeve('diamonds_7')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_7_small.png" alt="7 of diamonds" /></li>
                            <li id='diamonds_8'
                                onmouseover="dimCard('diamonds_8')"
                                onmouseout="unDimCard('diamonds_8')"
                                onclick="addToSleeve('diamonds_8')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_8_small.png" alt="8 of diamonds" /></li>
                            <li id='diamonds_9'
                                onmouseover="dimCard('diamonds_9')"
                                onmouseout="unDimCard('diamonds_9')"
                                onclick="addToSleeve('diamonds_9')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_9_small.png" alt="9 of diamonds" /></li>
                            <li id='diamonds_10'
                                onmouseover="dimCard('diamonds_10')"
                                onmouseout="unDimCard('diamonds_10')"
                                onclick="addToSleeve('diamonds_10')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_10_small.png" alt="10 of diamonds" /></li>
                            <li id='diamonds_J'
                                onmouseover="dimCard('diamonds_J')"
                                onmouseout="unDimCard('diamonds_J')"
                                onclick="addToSleeve('diamonds_J')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_J_small.png" alt="Jack of diamonds" /></li>
                            <li id='diamonds_Q'
                                onmouseover="dimCard('diamonds_Q')"
                                onmouseout="unDimCard('diamonds_Q')"
                                onclick="addToSleeve('diamonds_Q')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_Q_small.png" alt="Queen of diamonds" /></li>
                            <li id='diamonds_K'
                                onmouseover="dimCard('diamonds_K')"
                                onmouseout="unDimCard('diamonds_K')"
                                onclick="addToSleeve('diamonds_K')">
                                <img class="cImg" src="../../../images/PokerCard_diamonds_K_small.png" alt="King of diamonds" /></li>
                        </ul>
                    </div>
                    <div id="clubsList">
                        <ul id="clubsCarousel" class="jcarousel-skin-card">
                            <li id='clubs_A'
                                onmouseover="dimCard('clubs_A')"
                                onmouseout="unDimCard('clubs_A')"
                                onclick="addToSleeve('clubs_A')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_A_small.png" alt="Ace of clubs" /></li>
                            <li id='clubs_2'
                                onmouseover="dimCard('clubs_2')"
                                onmouseout="unDimCard('clubs_2')"
                                onclick="addToSleeve('clubs_2')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_2_small.png" alt="2 of clubs" /></li>
                            <li id='clubs_3'
                                onmouseover="dimCard('clubs_3')"
                                onmouseout="unDimCard('clubs_3')"
                                onclick="addToSleeve('clubs_3')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_3_small.png" alt="3 of clubs" /></li>
                            <li id='clubs_4'
                                onmouseover="dimCard('clubs_4')"
                                onmouseout="unDimCard('clubs_4')"
                                onclick="addToSleeve('clubs_4')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_4_small.png" alt="4 of clubs" /></li>
                            <li id='clubs_5'
                                onmouseover="dimCard('clubs_5')"
                                onmouseout="unDimCard('clubs_5')"
                                onclick="addToSleeve('clubs_5')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_5_small.png" alt="5 of clubs" /></li>
                            <li id='clubs_6'
                                onmouseover="dimCard('clubs_6')"
                                onmouseout="unDimCard('clubs_6')"
                                onclick="addToSleeve('clubs_6')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_6_small.png" alt="6 of clubs" /></li>
                            <li id='clubs_7'
                                onmouseover="dimCard('clubs_7')"
                                onmouseout="unDimCard('clubs_7')"
                                onclick="addToSleeve('clubs_7')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_7_small.png" alt="7 of clubs" /></li>
                            <li id='clubs_8'
                                onmouseover="dimCard('clubs_8')"
                                onmouseout="unDimCard('clubs_8')"
                                onclick="addToSleeve('clubs_8')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_8_small.png" alt="8 of clubs" /></li>
                            <li id='clubs_9'
                                onmouseover="dimCard('clubs_9')"
                                onmouseout="unDimCard('clubs_9')"
                                onclick="addToSleeve('clubs_9')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_9_small.png" alt="9 of clubs" /></li>
                            <li id='clubs_10'
                                onmouseover="dimCard('clubs_10')"
                                onmouseout="unDimCard('clubs_10')"
                                onclick="addToSleeve('clubs_10')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_10_small.png" alt="10 of clubs" /></li>
                            <li id='clubs_J'
                                onmouseover="dimCard('clubs_J')"
                                onmouseout="unDimCard('clubs_J')"
                                onclick="addToSleeve('clubs_J')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_J_small.png" alt="Jack of clubs" /></li>
                            <li id='clubs_Q'
                                onmouseover="dimCard('clubs_Q')"
                                onmouseout="unDimCard('clubs_Q')"
                                onclick="addToSleeve('clubs_Q')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_Q_small.png" alt="Queen of clubs" /></li>
                            <li id='clubs_K'
                                onmouseover="dimCard('clubs_K')"
                                onmouseout="unDimCard('clubs_K')"
                                onclick="addToSleeve('clubs_K')">
                                <img class="cImg" src="../../../images/PokerCard_clubs_K_small.png" alt="King of clubs" /></li>
                        </ul>
                    </div>
                    <div id="spadesList">
                        <ul id="spadesCarousel" class="jcarousel-skin-card">
                            <li id='spades_A'
                                onmouseover="dimCard('spades_A')"
                                onmouseout="unDimCard('spades_A')"
                                onclick="addToSleeve('spades_A')">
                                <img class="cImg" src="../../../images/PokerCard_spades_A_small.png" alt="Ace of spades" /></li>
                            <li id='spades_2'
                                onmouseover="dimCard('spades_2')"
                                onmouseout="unDimCard('spades_2')"
                                onclick="addToSleeve('spades_2')">
                                <img class="cImg" src="../../../images/PokerCard_spades_2_small.png" alt="2 of spades" /></li>
                            <li id='spades_3'
                                onmouseover="dimCard('spades_3')"
                                onmouseout="unDimCard('spades_3')"
                                onclick="addToSleeve('spades_3')">
                                <img class="cImg" src="../../../images/PokerCard_spades_3_small.png" alt="3 of spades" /></li>
                            <li id='spades_4'
                                onmouseover="dimCard('spades_4')"
                                onmouseout="unDimCard('spades_4')"
                                onclick="addToSleeve('spades_4')">
                                <img class="cImg" src="../../../images/PokerCard_spades_4_small.png" alt="4 of spades" /></li>
                            <li id='spades_5'
                                onmouseover="dimCard('spades_5')"
                                onmouseout="unDimCard('spades_5')"
                                onclick="addToSleeve('spades_5')">
                                <img class="cImg" src="../../../images/PokerCard_spades_5_small.png" alt="5 of spades" /></li>
                            <li id='spades_6'
                                onmouseover="dimCard('spades_6')"
                                onmouseout="unDimCard('spades_6')"
                                onclick="addToSleeve('spades_6')">
                                <img class="cImg" src="../../../images/PokerCard_spades_6_small.png" alt="6 of spades" /></li>
                            <li id='spades_7'
                                onmouseover="dimCard('spades_7')"
                                onmouseout="unDimCard('spades_7')"
                                onclick="addToSleeve('spades_7')">
                                <img class="cImg" src="../../../images/PokerCard_spades_7_small.png" alt="7 of spades" /></li>
                            <li id='spades_8'
                                onmouseover="dimCard('spades_8')"
                                onmouseout="unDimCard('spades_8')"
                                onclick="addToSleeve('spades_8')">
                                <img class="cImg" src="../../../images/PokerCard_spades_8_small.png" alt="8 of spades" /></li>
                            <li id='spades_9'
                                onmouseover="dimCard('spades_9')"
                                onmouseout="unDimCard('spades_9')"
                                onclick="addToSleeve('spades_9')">
                                <img class="cImg" src="../../../images/PokerCard_spades_9_small.png" alt="9 of spades" /></li>
                            <li id='spades_10'
                                onmouseover="dimCard('spades_10')"
                                onmouseout="unDimCard('spades_10')"
                                onclick="addToSleeve('spades_10')">
                                <img class="cImg" src="../../../images/PokerCard_spades_10_small.png" alt="10 of spades" /></li>
                            <li id='spades_J'
                                onmouseover="dimCard('spades_J')"
                                onmouseout="unDimCard('spades_J')"
                                onclick="addToSleeve('spades_J')">
                                <img class="cImg" src="../../../images/PokerCard_spades_J_small.png" alt="Jack of spades" /></li>
                            <li id='spades_Q'
                                onmouseover="dimCard('spades_Q')"
                                onmouseout="unDimCard('spades_Q')"
                                onclick="addToSleeve('spades_Q')">
                                <img class="cImg" src="../../../images/PokerCard_spades_Q_small.png" alt="Queen of spades" /></li>
                            <li id='spades_K'
                                onmouseover="dimCard('spades_K')"
                                onmouseout="unDimCard('spades_K')"
                                onclick="addToSleeve('spades_K')">
                                <img class="cImg" src="../../../images/PokerCard_spades_K_small.png" alt="King of spades" /></li>
                        </ul>
                    </div>
                </div>
                <div id="selectedCards" >Selected:
                </div>
            </div>
            <p id="cheatingOptionsHeader">Select your power up options below:</p>
            <div class="preCheatDiv" >
                <p><img class="preCheatImg normal"
                        onmouseover="this.className='preCheatImg fade'"
                        onmouseout="this.className='preCheatImg normal'" src="../../../images/cheatItem3.png" alt="Load up cards into your sleeve" onClick="showCardSelector()" title="Old Man Chalmers Reliable Card Pusher" />
                <div class="hiddenCard" id="sleeve">Sleeve:</div>
            </div>

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
        <script src="JS/SeedySaloon.js" type="text/javascript"></script>
    </body>
</html>
