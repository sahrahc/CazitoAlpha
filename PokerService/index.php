<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        // put your code here
include('c:\cazito\NetBeansProjects\Libraries\log4php\Logger.php');
Logger::configure('log4php.xml');
$log = Logger::getLogger("ServiceLogger");
$log->warn("My second message.");
?>
    </body>
</html>
