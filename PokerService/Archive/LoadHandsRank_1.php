<?php
include("EvalHelper.php");
/* Need to run once to load HR in shared memory
 */
ini_set("memory_limit","1200M");
ini_set('max_execution_time', 600); // 10 minutes

InitTheEvaluator();

$HR_shm_key = 0xff3;
$HR_size = 32487834;
// read the HR from shared memory
// Create 100 byte shared memory block with system id of 0xff3
$HRSerialized = serialize($HR);
echo "HR shared memory key : $HR_shm_key <br />"; 
$HR_shm_id = shmop_open($HR_shm_key, "c", 0644, strlen($HRSerialized));

// Lets write a test string into shared memory
// length is 38052057.
$shm_bytes_written = shmop_write($HR_shm_id, $HRSerialized, 0);
echo "The size of bytes written for HR: " . $shm_bytes_written . "<br />";
echo "The size of HR in memory is: " . shmop_size($HR_shm_id) . "<br />";

?>
