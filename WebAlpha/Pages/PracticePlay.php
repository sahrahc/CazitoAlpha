<?php
/*
// check if logged in
if (!isset($_SESSION['ProtoTester'])) {
	header("location:Login.php");
} */
include '../Includes/fileInclude.html';
include '../Includes/header.php';

include '../Includes/onlineFriends.html';
include '../Includes/pokerPlay.html';
include '../Includes/messaging.html';
?>
<script src="../js/core/game-actions.js" type="text/javascript"></script>
<script src="../js/core/queue.js" type="text/javascript"></script>
<script src="../js/core/game-status.js" type="text/javascript"></script>
<script src="../js/core/game-canvas.js" type="text/javascript"></script>
<script src="../js/pages/practice-play.js" type="text/javascript"></script>
<?php include '../Includes/footer.html'; ?>
