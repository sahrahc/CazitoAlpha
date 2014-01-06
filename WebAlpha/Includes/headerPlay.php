</head>
<body>
    <!-- all modal dialogs at the top dialog -->
    <!-- login is required for beta -->
    <header id="main">
		<a  class="logo" href="http://www.cazito.net/about-us">
			<img src="" alt="Logo" />
		</a>
		<h1>Cazito.com Heading</h1>
		<!-- search here -->
		<div class="greetings">
			<p>
				<span id='hello'>
					Hello
				</span>
				<span id='waiting-list'>
					There are x waiting to join this table.
				</span>
			</p>
		</div>
		<nav class="primary">
			<ul>
				<li><input type="submit" id='logoutButton' value="Logout" 
							onclick="logout();" /></li>
				<li>
					<span id='availableCurrency'></span>
					<input type='submit' id='buyCurrencyButton' value='Buy currency' />
				</li>
				<li><input class="active-playing" type='submit' id='inviteToTable'
						   value='Invite someone to this table' /></li>
				<li><input class="active-playing" type="submit" id="leaveSaloonButton"
						   onclick="leaveSaloon();" value="Leave Table" /></li>
				<li><input class="active-playing" type="submit" id="startGameButton" 
						   onclick="startGame();" value="Start New Game"
						   disabled=disabled /></li>
			</ul>
		</nav>

	</header>