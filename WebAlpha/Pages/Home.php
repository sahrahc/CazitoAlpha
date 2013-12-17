<?php
session_start();
include '../Includes/betaLock.php';

include '../Includes/fileInclude.html';
include '../Includes/header.php';
// login and register only on home
?>

<div id = "dialog-login" class = "dialog-modal" title = "Log In">
	<p>Enter your player name (no password for beta)</p>
	<form>
		Player Name: <input type = "text" id = "playerNameLogin">
		<!--Password: <input type = "password" name = "password"> -->
	</form>
</div>
<div id = "dialog-register" class = "dialog-modal" title = "Register">
	<p>Enter your information:</p>
	<form>
		Player Name: <input type = "text" id = "playerNameRegister">
		Password: <input type = "password" id = "password">
	</form>
</div>

<?php
if (isset($_SESSION['userPlayerId'])) {
	include '../Includes/onlineFriends.html';
}
?>
<div id="region-choices" class="region">
    <table>
        <tr>
            <td><input type='submit' id='startPractice' value="Practice Game" 
					   onclick="startPracticePlay();"></td>
            <td><input type='submit' id='setupTable' value='Select or Set Up Your Table' 
					   onclick="popupTableSetup();"></td>
        </tr>
        <tr>
            <td><input type='submit' id='startSafePlay' value='Start Safe Play' 
					   onclick="startSafePlay();"></td>
            <td><input type='submit' id='startSeedyPlay' value='Start Seedy Play' 
					   onclick="startSeedyPlay();"></td>        
        </tr>
        <tr>
            <td><input type='submit' id='howToGuide' value='How To Guide' 
					   onclick="popupHowTo();"></td>        
        </tr>
    </table>
</div>
<div id="dialog-daily" class="dialog-modal" title="Message of the day">
    <p>This is where the message of the day goes</p>
</div>
<div id="dialog-table-setup" class="dialog-modal "title="Select your table">
	<p> Please enter a table name and code if joining an existing table or a table name and size if creating a new table. </p>
    <form id ="tables">
        Table Name: <input type='text' id='tableName' />
        Table Code: <input type="text" id="tableCode" />
        <select id="tableSizeId" size="7">
            <option value="table1000" selected>1,000
            <option value="table5000">5,000
            <option value="table10000">10,000
            <option value="table50000">50,000
            <option value="table100000">100,000
            <option value="table500000">500,000
            <option value="table1000000">1,000,000
        </select>
		<br />
    </form>
</div>
<div id="dialog-how-to" class="dialog-modal" title='How To Play'>
    <h5>Setting up a table</h5>
    <p>Enter a table name and size. </p>
</div>
<div id="dialog-table-setup-message" class="dialog-modal" title="Table Set Up"d>
    <p>Your table has been successfully created. You may invite your friends to join!</p>
</div>
<?php
if (isset($_SESSION['userPlayerId'])) {
	include '../Includes/messaging.html';
}
?>
<script src="../js/pages/home.js"></script>
<?php include '../Includes/footer.html'; ?>
