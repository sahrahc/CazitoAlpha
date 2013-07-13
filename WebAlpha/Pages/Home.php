<?php

// check if logged in
session_start();
if (!isset($_SESSION['UserName'])) {
    header("location:Login.php");
}
include '../Includes/fileInclude.html';
include '../Includes/header.php';
?>
<section id="home">
    <table>
        <tr>
            <!-- none of these id's should be required -->
            <td><input type='submit' id='startPractice' value="Practice Game" onclick="popupLogin();"></td>
            <td><input type='submit' id='setupTable' value='Select or Set Up Your Table' onclick="popupTableSetup();"></td>
        </tr>
        <tr>
            <td><input type='submit' id='startSafePlay' value='Start Safe Play' onclick="startSafePlay();"></td>
            <td><input type='submit' id='startSeedyPlay' value='Start Seedy Play' onclick="startSeedyPlay();"></td>        
        </tr>
        <tr>
            <td><input type='submit' id='howToGuide' value='How To Guide' onclick="popupHowTo();"></td>        
        </tr>
    </table>
</section>
<div id="dialog-daily" title="Message of the day">
    <p>This is where the message of the day goes</p>
</div>
<!-- login dialog on header -->
<div id="dialog-table-setup" title="Select your table">
        <p> Please enter a table name and code if joining an existing table or a table name and size if creating a new table. </p>
    <form id ="tables">
        Table Name: <input type='text' name='tableName' />
        Table Code: <input type="text" name="tableCode" />
        <select id="tableSizeId" size="7">
            <option value="table1000" selected>1,000 Table
            <option value="table5000">5,000 Table
            <option value="table10000">10,000 Table
            <option value="table50000">50,000 Table
            <option value="table100000">100,000 Table
            <option value="table500000">500,000 Table
            <option value="table1000000">1,000,000 Table
        </select>
   <input type='submit' value='CreateNew' onclick="createNewTable();"
          <input type='submit' id='Find' value='Find' onclick="findCasinoTable();"
          <br />
    </form>
</div>
<div id="dialog-how-to" title='How To Play'>
    <h5>Setting up a table</h5>
    <p>Enter a table name and size. </p>
</div>
<div id="dialog-new-table-message" title="Table Set Up">
    <p>Your table has been successfully created. Please invite your friends to join!</p>
</div>
<div id="dialog-found-table-message" title="Table Validated">
    <p>X people are on the table</p>
    <p>X people are on the waiting list</p>
</div>
<script src="../js/pages/home.js"></script>
<?php include '../Includes/footer.html'; ?>
