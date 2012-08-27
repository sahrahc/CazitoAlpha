<?php
// check if logged in
session_start();
if (!isset($_SESSION['myusername'])) {
    header("location:Login.php");
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Texas Hold Em Social Game</title>
        <link rel="stylesheet" type="text/css" href="CSS/reset.css" />
        <link rel="stylesheet" type="text/css" href="CSS/playGame-skin.css" />
        <link rel="stylesheet" type="text/css" href="CSS/cheatingItems.css" />
        <link rel="Stylesheet" type="text/css" href="CSS/boardGame.css"/>
        <link rel="Stylesheet" type="text/css" href="CSS/popup.css"/>
        <link rel="Stylesheet" type="text/css" href="../../Libraries/jQuery/css/ui-lightness/jquery-ui-1.8.22.custom.css"/>
        <script src="../../Libraries/jQuery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/js/jquery-ui-1.8.22.custom.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/jquery.cookies.2.2.0.min.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="../../Libraries/jcarousel/skins/tango/cheatingItems-skin.css" />
        <script src="../../Libraries/jcarousel/lib/jquery.jcarousel.min.js" type="text/javascript"></script>
        <script src="http://cdn.sockjs.org/sockjs-0.3.min.js" type="text/javascript"></script>
        <script src="../../Libraries/Messaging/Stomp.js" type="text/javascript"></script>
    </head>
    <body >
        <!-- dialogs -->
        <div id="dialog-modal" title="Enter your player name">
            <p>Enter your player name, so we can match your past play or leave blank to enter as guest.</p>
            <form id="PlayerNameForm">
                <input type="text" id="playerNameText" value="Guest" />
            </form>
        </div>

        <div id="dialog-modal-follow-up" title="Select your game">
            <p>Start a practice game against three other players.</p>
            <br />
            <p>To play against other people, enter the table number you want to join. If the table does not exist or you leave this field blank, a new table will be started.</p>
            <form>
                <input type="text" id="tableIdText" value=""/>
            </form>

        </div>

        <div id="windowFrame" >
            <!-- top section and hidden data -->
            <div id="topFrame">
                <ul>
                    <li id="casinoTableId"></li>
                    <li id="gameSessionId"></li>
                    <li id="isPractice"></li>
                    <li id="gameSessionStart"></li>
                    <li id="gameInstanceId"></li>
                    <li id="gameStatus"></li>
                    <li id="currentDealerId"></li>
                    <li id="userPlayerId"></li>
                    <li id="nextPlayerId"></li>
                    <li id="nextCommunityCardPosition"></li>
                </ul>
                <h1 id="casinoTableHeader" >
                    Casino Table
                </h1>
            </div>
            <!-- cheaters tools and information on the left -->
            <div id="cheatersFrame">
                <!-- carousel has the list of cheating items the user can view or select -->
                <div id="cheatersList" >
                    <ul id="mycarousel" class="jcarousel-list jcarousel-skin-tango">
                        <li onmouseover="showDescription(1)"
                            onclick="showAction(1)">
                            <img class="cheatImg" src="../../../images/cheatItem1.png" alt="Ace Pusher" /></li>
                        <li onmouseover="showDescription(2)"
                            onclick="showAction(2)">
                            <img class="cheatImg" src="../../../images/cheatItem2.png" alt="Heart Marker" /></li>
                        <li onmouseover="showDescription(3)"
                            onclick="showAction(3)">
                            <img class="cheatImg" src="../../../images/cheatItem3.png" alt="Card Pusher" /></li>
                        <li onmouseover="showDescription(4)"
                            onclick="showAction(4)">
                            <img class="cheatImg" src="../../../images/cheatItem4.png" alt="Club Thumb" /></li>
                        <li onmouseover="showDescription(5)"
                            onclick="showAction(5)">
                            <img class="cheatImg" src="../../../images/cheatItem5.png" alt="Diamond Detector" /></li>
                        <li onmouseover="showDescription(6)"
                            onclick="showAction(6)">
                            <img class="cheatImg" src="../../../images/cheatItem6.png" alt="River Shuffler" /></li>
                        <li onmouseover="showDescription(7)"
                            onclick="showAction(7)">
                            <img class="cheatImg" src="../../../images/cheatItem7.png" alt="Peeker" /></li>
                        <li onmouseover="showDescription(8)"
                            onclick="showAction(8)">
                            <img class="cheatImg" src="../../../images/cheatItem8.png" alt="Table Tucker" /></li>
                        <li onmouseover="showDescription(9)"
                            onclick="showAction(9)">
                            <img class="cheatImg" src="../../../images/cheatItem9.png" alt="Social Spotters" /></li>
                        <li onmouseover="showDescription(10)"
                            onclick="showAction(10)">
                            <img class="cheatImg" src="../../../images/cheatItem10.png" alt="Snake Oil Markers" /></li>
                        <li onmouseover="showDescription(11)"
                            onclick="showAction(11)">
                            <img class="cheatImg" src="../../../images/cheatItem11.png" alt="Liver Crazy Marker" /></li>
                        <li onmouseover="showDescription(12)"
                            onclick="showAction(12)">
                            <img class="cheatImg" src="../../../images/cheatItem12.png" alt="Face Melter" /></li>
                        <li onmouseover="showDescription(13)"
                            onclick="showAction(13)">
                            <img class="cheatImg" src="../../../images/cheatItem13.png" alt="Riverbend Redo" /></li>
                    </ul>
                </div>
                <!-- details for how to cheat including hidden cards and descriptive and actionable info -->
                <div id="cheaterDetails">
                    <div class="hiddenCard" id="sleeve">Sleeve:</div>
                    <!-- the item actiosn are in a div to allow vertical centering -->
                    <div id="cheatingItemActionList">
                        <div class="cheatingItemAction" id="tabs-1-act" onclick="hideAction(1)">
                            <p>Sly McGuffin's Ace Pusher (5,000)</p>
                            <input id="tabs-1-submit" type="submit" onclick="cheatAcePusher()" value="Apply">
                        </div>
                        <div class="cheatingItemAction"  id="tabs-2-act" onclick="hideAction(2)">
                            <p>Miss Molly McSneaky's Heart Marker (10,000)</p>
                            <input id ="tabs-2-submit" type="submit" onclick="cheatHeartMarker()" value="Apply">
                        </div>
                        <div class="cheatingItemAction"  id="tabs-3-act" onclick="hideAction(3)">
                            <p>Old Man Chalmers Reliable Card Pusher (50,000)</p>
                            <input id="tabs-3-submit" type="submit" onclick="cheatUseCardOnSleeve()" value="Use">
                        </div>
                        <div class="cheatingItemAction"  id="tabs-4-act" onclick="hideAction(4)">
                            <p>Young Quick Draw Charlie's Club Thumb (15,000)</p>
                            <input id ="tabs-4-submit" type="submit" onclick="cheatClubsMarker()" value="Apply" />
                        </div>
                        <div class="cheatingItemAction"  id="tabs-5-act" onclick="hideAction(5)">
                            <p>Dotty's Diamond Detector (20,000)</p>
                            <input id ="tabs-5-select" type="submit" onclick="cheatDiamondMarker()" value="Apply"/>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-6-act" onclick="hideAction(6)">
                            <p>Shelvin's Shuffler (100,000)</p>
                            <input id ="tabs-6-look" type="submit" onclick="cheatLookRiverCard()" value="Look"/>
                            <input id ="tabs-6-swap" type="submit" onclick="cheatSwapRiverCard()" value="Swap"/>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-7-act" onclick="hideAction(7)">
                            <p>Peter Peester's Poker Peeker (150,000)</p>
                            <input id ="tabs-7-select" type="submit" onclick="cheatPeekOpponent()" value="Apply"/>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-8-act" onclick="hideAction(8)">
                            <p>Tommy's Table Tucker (200,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-9-act" onclick="hideAction(9)">
                            <p>Sally's Social Spotters (250,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-10-act" onclick="hideAction(10)">
                            <p>Young Doc McSneaky Jr's Snake Oil Markers (750,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-11-act" onclick="hideAction(11)">
                            <p>Old Doc McSneaky Snake Liver Crazy Maker (1,000,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-12-act" onclick="hideAction(12)">
                            <p>Old Fashioned Face Melter (1,250,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                        <div class="cheatingItemAction"  id="tabs-13-act" onclick="hideAction(12)">
                            <p>Riverbend Redo (1,500,000)</p>
                            <p><strong>Coming Soon</strong></p>
                        </div>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-1" onclick="hideDescription(1)">
                        <p><strong>Sly McGuffin's Ace Pusher (5,000)</strong></p>
                        <p>Can drop an ace into a player's hand, random suit, removing an old card into a hidden sleeve. Warning: TWO ACES MAY APPEAR IN THE SAME HAND!</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-2" onclick="hideDescription(2)">
                        <p><strong>Miss Molly McSneaky's Heart Marker (10,0000)</strong></p>
                        <p>The heart Marker has a symbol on the corner of all hearts in play. Can be used to determine what suite people have. Usable every 5 minutes.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-3" onclick="hideDescription(3)">
                        <p><strong>Old Man Chalmers Reliable Card Pusher (50,000)</strong></p>
                        <p>Players can load one card into their sleeve (their choice) for every seven levels of player. Must be done before sitting down at the table, players must leave the table to reload the Card Pusher.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-4" onclick="hideDescription(4)">
                        <p><strong>Young Quick Draw Charlie's Club Thumb (15,000)</strong></p>
                        <p>Knows which cards are Clubs. Can be used once every minute.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-5" onclick="hideDescription(5)">
                        <p><strong>Dotty's Diamond Detector (20,000)</strong></p>
                        <p>Knows which cards are Diamonds. Can be used once every 3 minutes.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-6" onclick="hideDescription(6)">
                        <p><strong>Shelvin's Shuffler (100,000)</strong></p>
                        <p>Can look at the river card, and swap the river card with the next card in the deck once every 10 minutes.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-7" onclick="hideDescription(7)">
                        <p><strong>Peter Peester's Poker Peeker (150,000)</strong></p>
                        <p>Puffs a gentle draft of air to reveal an opponent's card to a spotter behind your opponent. Usable once every 15 minutes.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-8" onclick="hideDescription(8)">
                        <p><strong>Tommy's Table Tucker (200,000) Coming Soon</strong></p>
                        <p>Tommy's Table Tucker lets the player slide cards into and out of the groove under the table. One card every 10 levels. WARNING: CARD DUPLICATES CAN APPEAR.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-9" onclick="hideDescription(9)">
                        <p><strong>Sally's Social Spotters (250,000) Coming Soon</strong></p>
                        <p>Each card that has been seen by the player gets marked. It is usable once an hour, and lasts for 45 minutes.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-10" onclick="hideDescription(10)">
                        <p><strong>Young Doc McSneaky Jr's Snake Oil Markers (750,000) Coming Soon</strong></p>
                        <p>Marks all the cards with dots that are nearly imperceptible, showing what all the cards are in the game. Players can substitute this marked deck with the dealer once every 30 minutes until leaving the table.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-11" onclick="hideDescription(11)">
                        <p><strong>Old Doc McSneaky Snake Liver Crazy Maker (1,000,000) Coming Soon</strong></p>
                        <p>Randomizes someone else's Snake Oil Markers on 50% of the cards. Usable once every hour and it makes Snake Oil Markers impossible to detect. NOTE: CAN BE USED ON SELF!</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-12" onclick="hideDescription(12)">
                        <p><strong>Old Fashioned Face Melter (1,250,000) Coming Soon</strong></p>
                        <p>Can be used to push a face card down in the deck when cards are being dealt and replace them with other cards that are not face cards. Can choose to prevent other players from getting face cards, and keep stacked into player's own hand.</p>
                    </div>
                    <div class="cheatingItemDesc" id="tabs-13" onclick="hideDescription(13)">
                        <p><strong>Riverbend Redo (1,500,000) Coming Soon</strong></p>
                        <p>Will know what the card is that will be on the river, and also can push the next card down to the top of the stack, once every 10 minutes.</p>
                    </div>
                    <div class="hiddenCard" id="nextCard" >Next card:</div>
                </div>
            </div>

            <!-- the game board also includes buttons to start game, leave the session and other players tools -->
            <div id="boardContainer">
                <div id="boardTable">
                    <p id="centerMessageId"></p>
                    <ul class="hiddenPlayerStatus">
                        <li id="player0Id"></li>
                        <li id="player0Card1"></li>
                        <li id="player0Card2"></li>
                        <li id="player1Id"></li>
                        <li id="player1Card1"></li>
                        <li id="player1Card2"></li>
                        <li id="player2Id"></li>
                        <li id="player2Card1"></li>
                        <li id="player2Card2"></li>
                        <li id="player3Id"></li>
                        <li id="player3Card1"></li>
                        <li id="player3Card2"></li>
                    </ul>
                    <div id ="player0Info" class="playerInfo playerInfoNormal">
                        <p class="playerName" id="player0Name">Empty Seat</p>
                        <img class="avatarImg" id="player0Image" src="../../../images/Avatar_user0.jpeg" />
                        <p class="playerStake" id="player0Stake" >0</p>
                        <p class="playerStatus" id ="player0Status" >Waiting</p>
                    </div>
                    <label class="dealerButton" id="player0DealerButton">D</label>
                    <img class="playerCard player0Card1" id="player0Card1Image" src="../../../images/PokerCard_back_small.png" />
                    <img class="playerCard player0Card2" id="player0Card2Image" src="../../../images/PokerCard_back_small.png" />
                    <!-- default values for markers, only one marker per card -->
                    <img class="cardSmallMarker player0Card1SmallMarker" id="player0Card1Marker" />
                    <img class="cardSmallMarker player0Card2SmallMarker" id="player0Card2Marker" />
                    <div id="player1Info" class="playerInfo playerInfoNormal">
                        <p class="playerName" id="player1Name">Empty Seat</p>
                        <img class="avatarImg" id="player1Image" src="../../../images/Avatar_user0.jpeg" />
                        <p class="playerStake" id="player1Stake" ></p>
                        <p class="playerStatus" id ="player1Status" ></p>
                    </div>
                    <label class="dealerButton" id="player1DealerButton">D</label>
                    <img class="playerCard player1Card1" id="player1Card1Image" src="../../../images/PokerCard_back_small.png" />
                    <img class="playerCard player1Card2" id="player1Card2Image" src="../../../images/PokerCard_back_small.png" />
                    <!-- default values for markers, only one marker per card -->
                    <img class="cardSmallMarker player1Card1SmallMarker" id="player1Card1Marker" />
                    <img class="cardSmallMarker player1Card2SmallMarker" id="player1Card2Marker" />
                    <div id ="player2Info" class="playerInfo playerInfoNormal">
                        <p class="playerName" id="player2Name">Empty Seat</p>
                        <img class="avatarImg" id="player2Image" src="../../../images/Avatar_user0.jpeg" />
                        <p class="playerStake" id="player2Stake" ></p>
                        <p class="playerStatus" id ="player2Status" ></p>
                    </div>
                    <label class="dealerButton" id="player2DealerButton">D</label>
                    <img class="playerCard player2Card1" id="player2Card1Image" src="../../../images/PokerCard_back_small.png" />
                    <img class="playerCard player2Card2" id="player2Card2Image" src="../../../images/PokerCard_back_small.png" />
                    <!-- default values for markers, only one marker per card -->
                    <img class="cardSmallMarker player2Card1SmallMarker" id="player2Card1Marker" />
                    <img class="cardSmallMarker player2Card2SmallMarker" id="player2Card2Marker" />
                    <div id="player3Info" class="playerInfo playerInfoNormal">
                        <p class="playerName" id="player3Name">Empty Seat</p>
                        <img class="avatarImg" id="player3Image" src="../../../images/Avatar_user0.jpeg" />
                        <p class="playerStake" id="player3Stake" ></p>
                        <p class="playerStatus" id ="player3Status" ></p>
                    </div>
                    <label class="dealerButton" id="player3DealerButton">D</label>
                    <img class="playerCard player3Card1" id="player3Card1Image" src="../../../images/PokerCard_back_small.png" />
                    <img class="playerCard player3Card2" id="player3Card2Image" src="../../../images/PokerCard_back_small.png" />
                    <!-- default values for markers, only one marker per card -->
                    <img class="cardSmallMarker player3Card1SmallMarker" id="player3Card1Marker" />
                    <img class="cardSmallMarker player3Card2SmallMarker" id="player3Card2Marker" />

                    <!-- community cards -->
                    <img id="communityCard0" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                    <img id="communityCard1" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                    <img id="communityCard2" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                    <img id="communityCard3" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                    <img id="communityCard4" class="communityCard" src="../../../images/PokerCard_back_small.png" />

                    <!--  user buttons; the default position is user at seat 0 -->
                    <label id="userCallAmount" style="position:absolute;display:none" ></label>
					<label id="userRaiseAmount" style="position:absolute;display:none" ></label>
					<input type="submit" id="userRaiseButton" class="userButton player0Raise" onclick="clickRaise()"
                           value="Raise" />
                    <input type="submit" id="userCheckButton" class="userButton player0Check" onclick="clickCheck()"
                           value="Check" />
                    <input type="submit" id="userCallButton" class="userButton player0Call" onclick="clickCall()"
                           value="Call" />
                    <input type="submit" id="userFoldButton" class="userButton player0Fold" onclick="clickFold()" value="Fold" />
                    <label id="seatNumber"></label>
                    <input type="submit" id="takeSeatButton" onclick="takeSeat()" value="Click to take this seat" />
                </div>
                <div id="gameBackground"></div>
                <canvas id="playGameCanvasId" height="400" width="642">Your browser does not support the canvas element.</canvas>
                <div id="userChoices">
                    <div id="userChoicesInput" >
                        <input type="submit" id="startGameButton" onclick="startGame()" disabled=disabled value="Start Game" />
                        <input type="submit" id="leaveSaloonButton" onclick="leaveSaloon()" value="Leave Saloon" />
                    </div>
                    <label id ="waitingMessageId"></label>
                </div>
            </div>

            <div id="messagingFrame">
                <p>Group Messaging</p>
                <div id="grpMsg" class="msg">
                    <p id="grpMsgText" class="msgText">message 1 <br/> message 2</p>
                    <form id="grpMsgForm" class="msgForm">
                        <input type="text" id="grpMsgInput" class="msgInput">
                        </input>
                        <input type="submit" id="grpMsgSend" class="msgSend" value="Send"></input>
                    </form>
                </div>
                <p>Individual Messaging</p>
                <div id="indvMsg" class="msg">
                    <p id="indvMsgText" class="msgText">message 1 <br/> message 2</p>
                    <form id="indvMsgForm" class="msgForm">
                        <input type="text" id="indvMsgInput" class="msgInput">
                        </input>
                        <input type="submit" id="indvMsgSend" class="msgSend" value="Send"></input>
                    </form>
                </div>
            </div>
            <div id="footer">
                Copyright © cazito.com</div>
        </div>
        <script src="JS/PlayGame.js" type="text/javascript"></script>
        <script src="JS/CheatingItem.js"></script>
        <script src="JS/tableMgmt.js"></script>
    </body>
</html>