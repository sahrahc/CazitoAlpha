<?php

session_start();
// username and password sent from form
// To protect MySQL injection (more detail about MySQL injection)
$protoTester = filter_input(INPUT_POST, 'ProtoTester', FILTER_SANITIZE_STRING);
$protoPassword = filter_input(INPUT_POST, 'ProtoPassword', FILTER_SANITIZE_SPECIAL_CHARS);

if (strtolower($protoTester) == 'alpha' && strtolower($protoPassword) == 'ganzania') {
    // Register $myusername, $mypassword and redirect to file "login_success.php"
    $_SESSION['ProtoTester'] = $protoTester;
    //$_SESSION['Password'] = $mypassword;
    //header("location:login_success.php?phpSESsid='" . session_id() . "'");
    if (!isset($_SESSION['srcLocation'])) {
        header("location:../Pages/Home.php");
    } else {
        header($_SESSION['srcLocation']);
    }
    //exit();
} else {
    echo "Wrong Username or Password";
}
?>
