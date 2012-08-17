<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Cazito LLC</title>
    </head>
    <body>
        <?php
        // check if logged in
        session_start();
        if (!isset($_SESSION['myusername'])) {
            header("location:Login.php");
        }
        ?>
        <h1>Cazito LLC</h1>
        <h2>Project Status</h2>
        
        <table border="1">
            <tr>
                <td colspan="4">Sprint 4</td>
            </tr>
            <tr>
                <td width="20%">Scheduled Start Date: </td>
                <td width="30%">Monday 7/16/2012</td>
                <td width="20%">Scheduled End Date: </td>
                <td width="30%">Sunday 7/22/2012</td>
            </tr>
            <tr>
                <td>Deployment Date: </td>
                <td colspan="3">Thursday 7/27/2012</td>
            </tr>
            <tr>
                <td colspan="4">
                <?php include ("Status/Sprint4.html");
                ?>
                </td>
            </tr>
            <tr>
                <td>Adjustments: </td>
                <td colspan="3"></td>
        </table>
    </body>
</html>
