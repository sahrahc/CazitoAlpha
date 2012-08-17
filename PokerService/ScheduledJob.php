<?php

include_once('TimerService.php');

// the following two happen every minute
cleanUp();
ejectInactivePlayer();
// trying different values...
checkExpiration();

// every three seconds, 19 times = 57   th' second
for ($i=0;$i<19;$i++) {
    sleep(3);
    checkExpiration();
}
?>
