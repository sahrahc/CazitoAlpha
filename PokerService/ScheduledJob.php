<?php

include_once('CoordinatorService.php');
// trying different values...

// the following two happen every minute
CleanUpAbandonedPlays();

// every three seconds, 19 times = 57   th' second
for ($i=0;$i<19;$i++) {
    sleep(3);
    ConsumeTableQueue();
    ProcessExpiredPokerMoves();
    ProcessTimedCheatingItems();
  //  updateTimedItems();
}
?>
