<?php
include_once('../Includes/Env.php');
header("Access-Control-Allow-Origin: http://" . $site . ":55674");
header("Access-Control-Allow-Credentials: true");
session_start();
include '../Includes/betaLock.php';

include '../Includes/fileInclude.html';
include '../Includes/headerPlay.php';

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
