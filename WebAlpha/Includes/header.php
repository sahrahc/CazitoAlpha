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
					<!-- Can always buy currency -->
					<span id='availableCurrency'></span>
					<input type='submit' id='buyCurrencyButton' value='Buy currency' />
				</li>
				<!-- only shows up in Home page, not FrontStreet  -->
				<!--
				<li><input type="submit" id='registerButton' value="Register" 
						   onclick="$('#dialog-register').dialog('open');" /></li>
	-->
				<li><input type="submit" id='loginButton' value="Sign in" 
						   onclick="$('#dialog-login').dialog('open');" /></li>
			</ul>
		</nav>

	</header>