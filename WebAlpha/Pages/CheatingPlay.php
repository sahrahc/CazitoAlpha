<?php
// check if logged in
session_start();
if (!isset($_SESSION['UserName'])) {
    header("location:Login.php");
}
include 'Includes/HeaderTop.php';
?>
<link rel="stylesheet" type="text/css" href="CSS/seedySaloon.css" />
<link rel="stylesheet" type="text/css" href="CSS/cheatingItems.css" />
<link rel="stylesheet" type="text/css" href="CSS/safeSaloon.css" />
<link rel="stylesheet" type="text/css" href="../../Libraries/jcarousel/skins/tango/cheatingItems-skin.css" />
<link rel="stylesheet" type="text/css" href="../../Libraries/jcarousel/skins/tango/cardItems-skin.css" />
<?php include 'Includes/HeaderBottom.php'; ?>
<div class="main">
    <div class='left-frame'>
        <!-- cheaters tools and information on the left -->
        <?php include 'Includes/CheaterCarousel.html'; ?>
        <?php include 'Includes/CheatingItemDetail.html' ?>
    </div>
    <div>
        <?php include 'Includes/PokerTable.html' ?>
    </div>
    <div class='right-frame'>
        <?php include 'Includes/Messaging.php' ?>
    </div>
</div>
<div class='log'></div>
<script src="../js/poker-play.js" type="text/javascript"></script>
<script src="js/CheatingPlay.js"></script>
<?php include 'Includes/Footer.html'; ?>
