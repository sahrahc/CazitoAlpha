<?php
include_once('../Includes/Env.php');
header("Access-Control-Allow-Origin: http://" . $site . ":55674");
header("Access-Control-Allow-Credentials: true");
session_start();
include '../Includes/betaLock.php';

include '../Includes/fileInclude.html';
include '../Includes/headerPlay.php';

// there should be tabs to switch messaging and cheating 
//include '../Includes/onlineFriends.html';
include '../Includes/cheatingCardSelector.html';
include '../Includes/cheatingCatalog.html';
include '../Includes/pokerPlay.html';
include '../Includes/messaging.html'; 
?>
<div class="logFrame" id="logEvent">Log Event</div>
<script src="../js/core/queue.js" type="text/javascript"></script>
<script src="../js/core/game-status.js" type="text/javascript"></script>
<script src="../js/core/cheating-cards.js" type="text/javascript"></script>
<script src="../js/core/cheating-status.js" type="text/javascript"></script>
<script src="../js/core/game-canvas.js" type="text/javascript"></script>
<script src="../js/core/game-actions.js" type="text/javascript"></script>
<script src="../js/pages/cheating-play.js"></script>
<?php include '../Includes/footer.html'; ?>
