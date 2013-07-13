<?php
// check if logged in
session_start();
if (!isset($_SESSION['UserName'])) {
    header("location:Login.php");
}
include 'Includes/HeaderTop.html';
include 'Includes/HeaderMiddle.html';
include 'Includes/HeaderNavNoPlay.html';
?>
<div class="main">
    <div class='left-frame'>
        <p>Online friends here</p>
        </p>
    </div>
    <div>
        <?php include 'Includes/PokerPlay.html' ?>
    </div>
    <div class='right-frame'>
        <?php include 'Includes/Messaging.html' ?>
    </div>
</div>
<script src="../js/poker-play.js" type="text/javascript"></script>
<?php include 'Includes/Footer.html'; ?>
