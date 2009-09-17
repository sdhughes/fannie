<?php # Phone List Generation - phoneList.php
// A script to generate a phone bank list from the membership database.

if (isset($_POST['submitted']) && $_POST['submit'] == 'submit') {
	
	if (!isset($_POST['date1'])) {
		$date1 = '2008-01-01';
	} else {
		$date1 = $_POST['date1'];
	}
	
	if (!isset($_POST['date2'])) {
		$date2 = date('Y-m-d');
	} else {
		$date2 = $_POST['date2'];
	}
	
	require_once('../includes/mysqli_connect.php');
	mysqli_select_db($db_slave, 'is4c_op');
        
	$query = "SELECT Shareholder, Secondary, CardNo, Phone
            FROM phoneList
            WHERE DATE(Modified) BETWEEN '$date1' AND '$date2'
            ORDER BY CardNo ASC";
                
	$result = mysqli_query($db_slave, $query);
        $rowNum = 1;
        while ($row = mysqli_fetch_row($result)) {
            if ($rowNum == 1) {
                echo "<h2>Phone Bank List From $date1 to $date2</h2>";
                echo '<table border="1" cellpadding="3" cellspacing="3">
                    <tr><th style="width:250px">Name</th><th>Phone</th><th>Card #</th><th style="width:20px">LM?</th><th style="width:20px"><small>Attend?<small></th><th>Call Date</th><th style="width:300px">Notes</th></tr>';
            }
            echo "<tr><td>{$row[0]}<br />";
            if (!is_null($row[1])) {
                echo " & " . $row[1];
            } else {
                echo "&nbsp";
            }
            echo "</td><td>{$row[3]}</td><td align=\"center\">{$row[2]}</td><td>&nbsp</td><td>&nbsp</td><td>&nbsp</td><td>&nbsp</td></tr>";
            if ($rowNum == 13) {
                echo '</table>';
                $rowNum = 0;
            }
            $rowNum++;
        }
        exit();
	
	
} elseif (isset($_POST['submitted']) && $_POST['submit'] == 'date') {
	require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
	mysqli_select_db($db_master, 'is4c_op');
	
	$query = "INSERT INTO is4c_log.phonelist (date) VALUES (now())";
	$result = mysqli_query($db_master, $query);
	if (!$result) {
		$page_title = 'Fannie - Membership Module';
		$header = 'Print Member Labels';
		include_once('../includes/header.html');
		echo "<h1>The print date could not be set, please try again later.</h1>";
		include ('../includes/footer.html');
	}
}

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_slave, 'is4c_log');

$query = "SELECT DATE_FORMAT(MAX(date), '%W %M %D at %r') FROM is4c_log.phonelist";
$result = mysqli_query($db_slave, $query);
list($lastprint) = mysqli_fetch_row($result);

$page_title = 'Fannie - Membership Module';
$header = 'Generate Phone Bank List';
include_once('../includes/header.html');
printf('
    <head>
    <link href="../style.css"
          rel="stylesheet" type="text/css" />
    <script src="../src/CalendarControl.js"
            language="javascript"></script>
    </head>
    <form name="labels" action="phoneList.php" method="post">
            <p>Please select a start and end date for phone bank list generation.<br />
            If no end date is selected, it will default to today.</p>
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                            <td align="right">
                                    <p><b>Date Start</b> </p>
                                    <p><b>End</b></p>
                            </td>
                            <td>			
                                    <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                                    <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                            </td>
                            <td colspan=2>
                            <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                            </td>
                    </tr>
            </table><br />
            <input type="hidden" name="submitted" value="TRUE" />
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                            <td align="left"><button name="submit" value="submit">Submit</button></td>
                            <td align="right"><button name="submit" value="date" align="right">Set Last Print Date</button></td>
                    </tr>
            </table>
            <p>(Phone list was last printed on: %s)</p>
    </form>', $lastprint);
include_once('../includes/footer.html');

?>