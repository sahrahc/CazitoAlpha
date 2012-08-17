<?php
session_start();
// username and password sent from form
$myusername = $_POST['myusername'];
$mypassword = $_POST['mypassword'];

// To protect MySQL injection (more detail about MySQL injection)
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
//$myusername = mysql_real_escape_string($myusername);
//$mypassword = mysql_real_escape_string($mypassword);

if (strtolower($myusername) == 'john' && strtolower($mypassword) == 'mofactor8244') {
    // Register $myusername, $mypassword and redirect to file "login_success.php"
    $_SESSION['myusername'] = $myusername;
    $_SESSION['mypassword'] = $mypassword;
    //header("location:login_success.php?phpSESsid='" . session_id() . "'");
    header("location:PlayGame.php");
    //exit();
} else {
    echo "Wrong Username or Password";
}
?>
