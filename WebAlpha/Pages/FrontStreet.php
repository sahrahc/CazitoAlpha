<?php
session_start();
include '../Includes/betaLock.php';

include '../Includes/fileInclude.html';
include '../Includes/header.php';

include '../Includes/onlineFriends.html';
?>
<div id="region-front-street" class="region">
	<!-- cheaters tools and information on the left -->
	<?php include '../Includes/cheatingCardSelector.html'; ?>
	<h6>Select your power up options below:</h6>
	<p><img class="preCheatImg normal"
			onmouseover="this.className = 'preCheatImg fade';"
			onmouseout="this.className = 'preCheatImg normal';" 
			src="../../../images/cheatItem3.png" alt="Load up cards into your sleeve" 
			onClick="showCardSelector();" title="Old Man Chalmers Reliable Card Pusher" />
	<div id="sleeve">Sleeve:</div>
	<input type='submit' value='Start Play' onclick="startSeedyPlay();"></td>        
</div>
<?php include '../Includes/messaging.html' ?>
<script src="../js/core/cheating-cards.js" type="text/javascript"></script>
<script src="../js/core/cheating-status.js" type="text/javascript"></script>
<script src="../js/pages/front-street.js" type="text/javascript"></script>
<?php include '../Includes/footer.html'; ?>
