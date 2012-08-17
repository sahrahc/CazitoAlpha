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
        <!-- TODO: support dynamic sizing to fit different browsers -->
        <meta charset="UTF-8">
        <title>Texas Hold Em Social Game</title>
        <link rel="stylesheet" type="text/css" href="CSS/PlayGame.css" />
        <link type="text/css" href="../../Libraries/jQuery/css/ui-lightness/jquery-ui-1.8.22.custom.css" rel="Stylesheet" />
        <script src="../../Libraries/jQuery/js/jquery-1.7.2.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/js/jquery-ui-1.8.22.custom.min.js" type="text/javascript"></script>
        <script src="../../Libraries/jQuery/jquery.cookies.2.2.0.min.js" type="text/javascript"></script>
        <script src="http://cdn.sockjs.org/sockjs-0.3.min.js" type="text/javascript"></script>
        <script src="../../Libraries/Messaging/Stomp.js" type="text/javascript"></script>
        <script type="text/javascript">

            $(function() {
                $( "#dialog:ui-dialog" ).dialog( "destroy" );
                $( "#dialog-modal" ).dialog({
                    autoOpen:false,
                    modal:true,
                    buttons:  {
                        Submit: function() {
                            $(this).dialog("close");
                            $( "#dialog-modal-follow-up" ).dialog("open");
                        }
                    }
                });
                $( "#dialog-modal-follow-up" ).dialog({
                    autoOpen:false,
                    modal:true,
                    buttons:  {
                        "Practice Game": function() {
                            startPracticeSession();
                            $(this).dialog("close");
                        },
                        "Join a Table": function() {
                            addUserToCasinoTable();
                            $(this).dialog("close");
                        }
                    }
                });
            });
        </script>
    </head>
    <body >
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
        <div id="top">
            <h1 id="casinoTableHeader" style="margin-bottom: 0;">
                Casino Table
            </h1>
            <ul style="display:none">
                <li id="casinoTableId"></li>
                <li id="gameSessionId"></li>
                <li id="isPractice"></li>
                <li id="gameSessionNumber"></li>
                <li id="gameSessionStart"></li>
                <li id="gameInstanceId"></li>
                <li id="gameStatus"></li>
                <li id="currentDealerId"></li>
                <li id="userPlayerId"></li>
                <li id="nextPlayerId"></li>
                <li id="nextCommunityCardPosition"></li>
            </ul>
        </div>
        <div id="container" style="position:relative;width: 800px">
            <div id="boardTable" style="position:relative; height:400px; width:642px;z-index:2">
                <label id="centerMessageId" style="position:absolute"></label>
                <div id="player0" class="playerInfo" >
                    <table id ="player0Table" class="playerTable" style="position:absolute;" >
                        <tr>
                            <th class="playerInfoType" >
                                <img id="player0Image" src="../../../images/Avatar_user0.jpeg" style="height:20px;float:left;">
                            </th>
                            <th class="playerInfoValue" id="player0Name" style="float:left">Empty Seat
                            </th>
                        </tr>
                        <tr>
                            <td class="playerInfoType" >Stake:</td>
                            <td class="playerInfoValue" id="player0Stake" ></td>
                        </tr>
                        <tr>
                            <td class="playerInfoType">Status:</td>
                            <td class="playerInfoValue" id ="player0Status" ></td>
                        </tr>
                    </table>
                    <label class="dealerButton" style="position:absolute;display:none" >D</label>
                    <input type="text" id="player0Message" class="actionMessage" style="position:absolute" />
                    <img class="communityCard" id="player0Card1Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <img class="communityCard" id="player0Card2Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <ul class="playerStatus" style="display:none">
                        <li id="player0Id"></li>
                        <li id="player0Card1"></li>
                        <li id="player0Card2"></li>
                    </ul>
                </div>
                <div id="player1" class="playerInfo">
                    <table id="player1Table" class="playerTable" style="position:absolute;" >
                        <tr>
                            <th class="playerInfoType" >
                                <img id="player1Image" src="../../../images/Avatar_user0.jpeg" style="height:20px;float:left;">
                            </th>
                            <th class="playerInfoValue" id="player1Name" style="float:left">Empty Seat
                            </th>
                        </tr>
                        <tr>
                            <td class="playerInfoType" >Stake:</td>
                            <td class="playerInfoValue" id="player1Stake" ></td>
                        </tr>
                        <tr>
                            <td class="playerInfoType">Status:</td>
                            <td class="playerInfoValue" id ="player1Status" ></td>
                        </tr>
                    </table>
                    <label class="dealerButton" style="position:absolute;display:none" >D</label>
                    <input type="text" id="player1Message" class="actionMessage" style="position:absolute" />
                    <img class="communityCard" id="player1Card1Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <img class="communityCard" id="player1Card2Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <ul class="playerStatus" style="display:none">
                        <li id="player1Id"></li>
                        <li id="player1Card1"></li>
                        <li id="player1Card2"></li>
                    </ul>
                </div>
                <div id="player2" class="playerInfo" >
                    <table id ="player2Table" class="playerTable" style="position:absolute;">
                        <tr>
                            <th>
                                <img id="player2Image" src="../../../images/Avatar_user0.jpeg" style="height:20px;float:left;">
                            </th>
                            <th class="playerInfoValue" id="player2Name" style="float:left">Empty Seat
                            </th>
                        </tr>
                        <tr>
                            <td class="playerInfoType" >Stake:</td>
                            <td class="playerInfoValue" id="player2Stake" ></td>
                        </tr>
                        <tr>
                            <td class="playerInfoType">Status:</td>
                            <td class="playerInfoValue" id ="player2Status" ></td>
                        </tr>
                    </table>
                    <label class="dealerButton" style="position:absolute;display:none" >D</label>
                    <input type="text" id="player2Message" class="actionMessage" style="position:absolute" />
                    <img class="communityCard" id="player2Card1Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <img class="communityCard" id="player2Card2Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <ul class="playerStatus" style="display:none">
                        <li id="player2Id"></li>
                        <li id="player2Card1"></li>
                        <li id="player2Card2"></li>
                    </ul>
                </div>
                <div id="player3" class="playerInfo" >
                    <table id="player3Table" class="playerTable" style="position:absolute;">
                        <tr>
                            <th>
                                <img id="player3Image" src="../../../images/Avatar_user0.jpeg" style="height:20px;float:left;">
                            </th>
                            <th class="playerInfoValue" id="player3Name" style="float:left">Empty Seat
                            </th>
                        </tr>
                        <tr>
                            <td class="playerInfoType" >Stake:</td>
                            <td class="playerInfoValue" id="player3Stake" ></td>
                        </tr>
                        <tr>
                            <td class="playerInfoType">Status:</td>
                            <td class="playerInfoValue" id ="player3Status" ></td>
                        </tr>
                    </table>
                    <label class="dealerButton" style="position:absolute;display:none" >D</label>
                    <input type="text" id="player3Message" class="actionMessage" style="position:absolute" />
                    <img class="communityCard" id="player3Card1Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <img class="communityCard" id="player3Card2Image" src="../../../images/PokerCard_back_small.png" style="position:absolute;" />
                    <ul class="playerStatus" style="display:none">
                        <li id="player3Id"></li>
                        <li id="player3Card1"></li>
                        <li id="player3Card2"></li>
                    </ul>
                </div>
                <img id="communityCard0" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                <img id="communityCard1" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                <img id="communityCard2" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                <img id="communityCard3" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                <img id="communityCard4" class="communityCard" src="../../../images/PokerCard_back_small.png" />
                <!--  FIXME: player chips are dynamically created -->
                <!--  pot size, FIXME: make this into a table for display -->
                <label id="userCallAmount" style="position:absolute;display:none" >0</label>
                <label id="userRaiseAmount" style="position:absolute;display:none" >0</label>
                <input type="submit" id="userRaiseButton" onclick="clickRaise()"
                       value="Raise" style="display:none"/>
                <input type="submit" id="userCheckButton" onclick="clickCheck()"
                       value="Check" style="display:none"/>
                <input type="submit" id="userCallButton" onclick="clickCall()"
                       value="Call" style="display:none"/>
                <input type="submit" id="userFoldButton" onclick="clickFold()" value="Fold" style="display:none" />
                <label id="seatNumber" style="display:none"></label>
                <input type="submit" id="takeSeatButton" onclick="takeSeat()" value="Click to take this seat" style="display:none" />
            </div>
            <div id="gameBackground" style="position:absolute;z-index:0"></div>
            <canvas id="playGameCanvasId" height="400" width="642" style="position:absolute;z-index:1">Your browser does not support the canvas element.</canvas>
            <div id="feed">
            </div>
            <div id="history">
            </div>
            <div  id="tempButtons" style="clear: both; align:left">
                <input type="submit" id="startGameButton" onclick="startGame()" disabled=disabled value="Start Game" />
                <input type="submit" id="leaveSaloonButton" onclick="leaveSaloon()" value="Leave Saloon" />
                <label id ="WaitingMessageId"></label>
            </div>
            <div id="footer">
                Copyright © cazito.com</div>
        </div>
        <script src="JS/PlayGame.js" type="text/javascript"></script>
    </body>
</html>