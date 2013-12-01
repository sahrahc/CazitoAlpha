<?php

session_start();
// username and password sent from form
$protoTester = $_POST['ProtoTester'];
$protoPassword = $_POST['ProtoPassword'];

// To protect MySQL injection (more detail about MySQL injection)
$protoTester = stripslashes($protoTester);
$protoPassword = stripslashes($protoPassword);

if (strtolower($protoTester) == 'john' && strtolower($protoPassword) == 'mofactor8244') {
    // Register $myusername, $mypassword and redirect to file "login_success.php"
    $_SESSION['UserName'] = $protoTester;
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
