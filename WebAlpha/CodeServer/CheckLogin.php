<?php

session_start();
// username and password sent from form
$myusername = $_POST['UserName'];
$mypassword = $_POST['Password'];

// To protect MySQL injection (more detail about MySQL injection)
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
//$myusername = mysql_real_escape_string($myusername);
//$mypassword = mysql_real_escape_string($mypassword);

if (strtolower($myusername) == 'john' && strtolower($mypassword) == 'mofactor8244') {
    // Register $myusername, $mypassword and redirect to file "login_success.php"
    $_SESSION['UserName'] = $myusername;
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
