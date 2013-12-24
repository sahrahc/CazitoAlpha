<?php
echo "time 1 " . time() . "<br/>";
sleep(1);
echo "time 2 " . time() . "<br/>";
sleep(1);
echo "time 3 " . time() . "<br/>";
sleep(1);
echo "time 4 " . time() . "<br/>";
sleep(1);
phpinfo();
/** testing null vs undefined
class test {
	public $f1;
	public $f3;
}

$test = new test();
$test->f1  = 'set f1';
$t1 = isset($test->f2) ? $test->f2 : 'not set f2';
echo "set property: " . $test->f1 . "<br/>";
echo "set property: " . $t1 . "<br/>";
echo "set property: " . $test->f3 . "<br/>";
*/
/* testing objects
 * 
require_once('TestService/TestComponents.php');
require_once('TestService/ValidateGameStatus.php');

// gameStatusDto 
$expectedDto = InitGameStatusDto(1);

	$expectedDto->playerStatusDtos[0] = InitPlayerStatusDto(
			0, 'Test0', 0, 10);
	$expectedDto->playerStatusDtos[1] = InitPlayerStatusDto(
			1, 'Test1', 1, 20);

$playerStatusDtos[0] = clone $expectedDto->playerStatusDtos[0];
$playerStatusDtos[1] = $expectedDto->playerStatusDtos[1];

 */

?>
