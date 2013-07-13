<?php
// check if logged in
session_start();
if (!isset($_SESSION['UserName'])) {
    header("location:Login.php");
}
include 'Includes/fileInclude.html';
include 'Includes/header.php';
?>
<div class = 'main'>
    <div class='left-frame'>
        <p>Online friends here</p>
        </p>
    </div>
    <div>
        <!-- cheaters tools and information on the left -->
        <div id="dialog-card-selector" title="Click on a card to add to your sleeve">
            <p>Development note: You may select multiple cards, however only the first two will be available when you play a game. Cannot un-select cards, please cancel and try again instead.</p>
            <?php include 'Includes/cheatingSleeve.html'; ?>
            <div id="selectedCards" >Selected:
            </div>
        </div>
        <p id="cheatingOptionsHeader">Select your power up options below:</p>
        <div class="preCheatDiv" >
            <p><img class="preCheatImg normal"
                    onmouseover="this.className = 'preCheatImg fade';"
                    onmouseout="this.className = 'preCheatImg normal';" src="../../../images/cheatItem3.png" alt="Load up cards into your sleeve" onClick="showCardSelector();" title="Old Man Chalmers Reliable Card Pusher" />
            <div class="sleeve" id="sleeve">Sleeve:</div>
        </div>
    </div>
    <div class='right-frame'>
        <?php include 'Includes/Messaging.php' ?>
    </div>

</div>
<script src="js/CheatingSleeve.js" type="text/javascript"></script>
<?php include '../Includes/footer.html'; ?>
