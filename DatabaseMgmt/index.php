<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        $dbName = 'cazito5_sprint8';

        include('DropSchema.php');
        include('DropItems.php');
        include('CreateSchema.php');
        include('CreateItems.php');
        CreateSchema();
        CreateItems();

        ?>
    </body>
</html>
