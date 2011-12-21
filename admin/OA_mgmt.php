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
    $page_title = 'Fannie - Admin Module';
    $header = 'Owner Appreciation Management';
    include_once('../includes/header.html');
  
  
    $date = $_POST['date1'];
    $type = $_POST['OA_type'];

   
    if (isset($_POST['submit']) && $_POST['submit'] == 'submit' ) {
	

        if ($type == 'day') {
            $query = "INSERT INTO is4c_op.MADays (MADate) VALUES ('$date');";
        } else {
            $query = "INSERT INTO is4c_op.OAMonths (month) VALUES ('" . substr($date,0,10) . "');";
        }

        mysqli_query($db_master, $query) or die('Insertion Error: ' . mysqli_error($db_master));
        $message = "<p class='directions'>Record inserted successfully.</p>";

    } elseif (isset($_POST['submit']) && $_POST['submit'] == 'delete' ) {


        if ($type == 'day') {
            $query = "DELETE FROM is4c_op.MADays WHERE DATE(MADate) = '$date';";
        } else {
            $query = "DELETE FROM is4c_op.OAMonths WHERE DATE(MADate) = '$date';";
        }
        $deleteR = mysqli_query($db_master, $query) or die('Deletion Error: ' . mysqli_error($db_master));
        
        if ($deleteR) $message = "<p class='directions'>OADate Deleted Successfully.</p> "; 
        else $message = "<p>Error deleting date: $date</p>";
    } else { 
        
        $message = "<p class='directions'>Welcome to the Owner Appreciation Day Admin page. Please Add or Delete dates as you like. :)</p> "; 
    
    }

    echo $message;


    ?>

	<form action="<?php $_SERVER['PHP_SELF'] ?>" method='post'>
		<label>Enter/Delete OA Date:</label><input type='text' name='date1' value=''></input>
        <label>day</label><input type="radio" name="OA_type" value="day" />
        <label>month</label><input type="radio" name="OA_type" value="month" />
		<input type='submit' name='submit' value='submit' />
		<input type='submit' name='submit' value='delete' />
	</form>
<?php

    //declare common vars
    $currDate = date("Y-m-d");
    $futures = 0;
    
    mysqli_select_db($db_master,'is4c_op') or die('Database Select Error: ' . mysqli_error($db_master));

    //Pull the dates
    $OADQuery = "SELECT MADate FROM MADays ORDER BY MADate ASC";
    $results = mysqli_query($db_master, $OADQuery) or die('Query Error: ' . mysqli_error($db_master));

    //Print out the current OADs
    echo "<table><tr><td>";
    echo "<p>Past Owner Appreciation Days: </p>";

    echo "<ul>";

    while ($row = mysqli_fetch_array($results)) {

        if (($row[0] > $currDate) && ( $futures == 0 )) { echo "</ul></td><td><p>Future Days:</p><ul>"; $futures = 1; }
        echo "<li>$row[0]</li>";

    }
    if ($futures == 0) echo "</ul></td><td><p>Future Days:</p><ul><li>None entered yet</li>";

    
    echo "</ul></td></tr><tr><td>";

    $futures = 0;

    $monthQ = "SELECT id, month FROM is4c_op.OAMonths ORDER BY month ASC";
    $results = mysqli_query($db_master, $monthQ) or die('Query Error: ' . mysqli_error($db_master));
    
    //Now Print out the OA Months
    echo "<p>Past OA Months:</p>";
    echo "<ul>";
    while ($row = mysqli_fetch_array($results)) {
    
        if (($row[1] > $currDate) && ($futures == 0)) { echo "</ul></td><td><p>Future Months:</p><ul>"; $futures = 1;}
    
        echo "<li>" . substr($row[1],0,7) . "</li>";

    }
    if ($futures == 0) echo "</ul></td><td><p>Future Months:</p><ul>";
    echo "</ul></td></tr></table>";

    include_once('../includes/footer.html');
?>
