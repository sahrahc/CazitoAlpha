<?php
// check if logged in
if (!isset($_SESSION['ProtoTester'])) {
	header("location:Login.html");
}

?>
