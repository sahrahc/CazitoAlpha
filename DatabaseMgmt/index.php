<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        
        //$dbName = 'cazito5_sprint9';
        include_once(dirname(__FILE__) . '/../PokerService/Config.php');
        
        include('DropSchema.php');
        include('DropItems.php');
        include('CreateSchema.php');
        include('CreateItems.php');
        CreateSchema();
        CreateItems();

        ?>
    </body>
</html>
