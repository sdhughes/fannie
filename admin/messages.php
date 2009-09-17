<?php # messages.php - This will let your average user modify the greeting, farewell, and receipt footers for all lanes.
$page_title = 'Fannie - Admin Module';
$header = 'Message Manager';
include ('../includes/header.html');

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_master, 'is4c_op');

if (isset($_POST['submitted'])) {
    
    foreach ($_POST['id'] AS $id => $msg) {
        $query = "UPDATE messages SET message = '" . escape_data($msg) . "' WHERE id='$id'";
        $result = mysqli_query($db_master, $query);
    }
    
}

echo '<form action="messages.php" method="POST">';

$query = "SELECT * FROM messages ORDER BY id ASC";
$result = mysqli_query($db_master, $query);
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    if ($row['id'] == 'receiptFooter1') {echo "<p><b>Receipt Footer:</b></p>\n";}
    elseif ($row['id'] == 'farewellMsg1') {echo "<p><b>Farewell Message:</b></p>\n";}
    elseif ($row['id'] == 'welcomeMsg1') {echo "<p><b>Welcome Message:</b></p>\n";}
    echo "<input type=\"text\" name=\"id[{$row['id']}]\" value=\"{$row['message']}\" size=\"47\" maxlength=\"47\" /><br />\n";
}

echo '<br /><button name="submit" type="submit">Save</button>
<input type="hidden" name="submitted" value="TRUE" />
</form>';

include ('../includes/footer.html');
?>