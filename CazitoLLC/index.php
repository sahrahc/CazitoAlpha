<?php
// check if logged in
session_start();
if (!isset($_SESSION['myusername'])) {
    header("location:Login.php");
}
?>
<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Cazito LLC</title>
    </head>
    <body>
        <h1>Cazito LLC</h1>
        <h2>Use cases</h2>
        <a href="UseCasesTableMgmt.php">Table Management Use Cases (2012-08-18)</a><br />
        <a href="UseCasesSafeGamePlay.php">Safe Saloon Game Play Use Cases (2012-08-18)</a><br />
        <a href="UseCasesCheating.php">Cheating Use Cases (2012-08-25)</a><br />
        <h2><a href="Design.php">Design Documents</a></h2>
        <p>Design documents are a work in progress and reflect the current design or short-term target design of the application </p>
        <!-- <h2><a href="ProjectStatus.php">Project Status</a></h2>
         <p>View the latest project plans and the weekly status reports.</p> -->
        <h2>Project Status</h2>
        <p> Coming Soon </p>
        <h2>Links to Alpha Site</h2>
        <p> Coming Soon </p>
    </body>
</html>