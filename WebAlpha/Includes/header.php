<!-- navigation and modal dialogs -->
</head>
<body>
    <!-- all modal dialogs at the top dialog -->
    <!-- login is required for beta -->
    <div id="dialog-login" title="Log In">
        <p>Enter your player name (no password for beta)</p>
        <form id="PlayerNameForm">
            Player Name: <input type="text" id="playerName">
            <!--Password: <input type="password" name="password">-->
            <input type="submit" name="Submit">
        </form>
    </div>
    <header id="main">
        <div id='header-central' >
            <a href="http://www.cazito.net/about-us">
                <img id="logo" src="" alt="Creative casino games" />
            </a>
            <p>Creative casino games</p>
            <!-- search here -->
            <nav class="primary">
                <ul>
                <!-- if logged-in, logout and show/buy currency -->
                <?php if (isset($_SESSION['UserName'])) : ?>
                <li><input type="submit" onclick="logout();" value="Logout" /></li>
                <li>
                    <span id='availableCurrency'></span>
                    <input type='submit' id='buyCoin' value='Buy currency' />
                </li>
                <!-- else not logged in, show register, sign-in -->
                <!-- < ?php elseif() : >  -->
                <?php else : ?>
                <li><input type="submit" value="Register" /></li>
                <li><input type="submit" value="Sign in" /></li>
                <!-- user is playing, these should be disabled by default and enabled by jQuery -->
                <?php endif;                    if (isset($_SESSION['userPlayerId'])) : ?>
                <li><input class="active-playing" type='submit' id='inviteToTable' value='Invite someone to this table' /></li>
                <li><input class="active-playing" type="submit" id="leaveSaloonButton"  onclick="leaveSaloon();" value="Leave Saloon" /></li>
                <li><input class="active-playing" type="submit" id="startGameButton" onclick="startGame();" disabled=disabled value="Start New Game" /></li>
                <?php endif; ?>
            </ul>
        </nav>

            
        </div>
        <div class="greetings">
            <ul>
            <li id='hello'>
                Hello
            </li>
            <li id='waiting-list'>
                There are x waiting to join this table.
            </li>
        </div>
    </header>
