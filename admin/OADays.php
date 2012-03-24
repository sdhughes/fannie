<?php
/********************************
 *
 * This page will list the past MADays
 * and allow you to set new ones.
 *
 * author : Steve
 * date   : nope
 *
 ********************************/

    //Make sure you can connect.
    require_once('../includes/mysqli_connect.php');
    global $db_master;

    //Set up the header values
    $page_title = 'Fannie - OAD Admin';
    $header = 'Owner Appreciation Day Admin';
    include_once('../includes/header.html');
   
    if (isset($_POST['submit']) && $_POST['submit'] == 'submit' ) {
	
	$date = $_POST['date1'];

	$query = "INSERT INTO MADays (MADate) VALUES ('$date');";
	mysqli_query($db_master, $query) or die('Insertion Error: ' . mysqli_error($db_master));

    $message = "<p class='directions'>>Record inserted successfully.</p>";

    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'delete' ) {


	$date = $_POST['date1'];
	$query = "DELETE FROM MADays WHERE DATE(MADate) = '$date';";
	mysqli_query($db_master, $query) or die('Deletion Error: ' . mysqli_error($db_master));
    
     $message = "<p class='directions'>OADate Deleted Successfully.</p> "; 
    } else { $message = "<p class='directions'>Welcome to the Owner Appreciation Day Admin page. Please Add or Delete dates as you like. :)</p> "; }

 echo $message;


?>

	<form action="<?php $_SERVER['PHP_SELF'] ?>" method='post'>
		<label>Enter new OA Date:</label><input type='text' name='date1' value=''></input>
		<input type='submit' name='submit' value='submit' />
		<input type='submit' name='submit' value='delete' />
	</form>
<?php

    //Work with the database to get the data
    mysqli_select_db($db_master,'is4c_op') or die('Database Select Error: ' . mysqli_error($db_master));
    $oldOADQuery = "SELECT MADate FROM MADays WHERE DATE(MADate) < DATE(CURDATE()) ORDER BY MADate";
    $results = mysqli_query($db_master, $oldOADQuery) or die('Query Error: ' . mysqli_error($db_master));



    //Print out the current OADs
    echo "<p>Past Owner Appreciation Days: </p>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($results)) {

	echo "<li>$row[0]</li>";

    }
    echo "</ul>";

    $newOADQuery = "SELECT MADate FROM MADays WHERE DATE(MADate) >= DATE(CURDATE())";
    $results = mysqli_query($db_master, $newOADQuery) or die('Query Error: ' . mysqli_error($db_master));

    //Print out the Future OADs
    echo "<p>Future Owner Appreciation Days:</p>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($results)) {

	echo "<li>$row[0]</li>";

    }
    echo "</ul>";


    include_once('../includes/footer.html');
?>
