<?php # Contact List Generation - contactList.php
// A script to generate uploadable email list from the membership database.


//check to see if the page is 1st time, or recently submitted
if (isset($_POST['submitted'])) {

	//print_r($_POST);
	
	//check to see if the dated were selected
	if (!isset($_POST['date1'])) {
		echo 'No Start Date Selected. Defaulting to 2008-01-01.';
		$date1 = '2008-01-01';
	} else {
		$date1 = $_POST['date1'];
	}

	if (!isset($_POST['date2'])) {
		echo 'No End Date Selected. Defaulting to today.';
		$date2 = date('Y-m-d');
	} else {
		$date2 = $_POST['date2'];
	}

	//connect to the database
//	require_once('../includes/mysqli_connect.php');
//	mysqli_select_db($db_comet, 'comet');

/*	$query = "SELECT Shareholder, Secondary, CardNo, Phone
            FROM phoneList
            WHERE DATE(Modified) BETWEEN '$date1' AND '$date2'
            ORDER BY CardNo ASC";
        //BE CAREFUL, phoneList is a VIEW and loads very very slow....
	$result = mysqli_query($db_slave, $query);
*/
	$db = mysqli_connect('localhost','comet','c0m3t','comet') or die('Could not create connection ' . mysqli_errno());

	//init the var which will hold the SELECT statement
	$select = '';

	if (isset($_POST['card'])) {
		$select .= 'o.cardNo AS CardNo';
	}
 
	if (isset($_POST['first'])) {
		if ($select != '') { 
			$select .= ', ';
		}	
		$select .=  'o.firstName AS First';
	}
	if (isset($_POST['last'])) {
		if ($select != '') { 
			$select .= ', ';
		}	
		$select .= 'o.lastName AS Last';
	}
	if (isset($_POST['email'])) {
		if ($select != '') { 
			$select .= ', ';
		}	
		$select .= 'd.email AS Email';
	}
/*	if ($isset($_POST['phone'])) {
		$select .= '';
	}
	if ($isset($_POST['type'])) {

	}*/

	if ($select == '') {
		echo "<p>Nothing was selected so all were selected!</p>";
		$select = 'o.cardNo AS CardNo, o.firstName AS First, o.lastName AS Last, d.email AS Email';
	}

	$listQ = "SELECT $select FROM comet.details AS d INNER JOIN comet.owners AS o INNER JOIN is4c_op.custdata AS c ON d.cardNo = o.cardNo AND o.cardNo = c.CardNo WHERE substr(c.modified,-19,10) BETWEEN '$date1' AND '$date2' GROUP BY Email";
	$listR = mysqli_query($db, $listQ);
	
	//show what query we're running...
	//echo "<p>$listQ</p>";
	
	$filename = 'modified_emails.txt';
	
	//if there are results....
	if ($listR) {

		echo "From: " . $date1 . "<br />Until: " . $date2 . "<br />";
	
		if (!file_exists($filename)) {
			echo "<p>No file found, creating file: $filename </p>";
			touch($filename);
		} else {
			echo "<p>Found file: $filename! </p>";
		}
		
		//check to see if the can be written to
		$writable = is_writable($filename);
		if (!$writable) {
			echo "$filename is not writeable. <br />";
		}
		
		//set the file name and establish the working directory
		$file = fopen($filename,'w');
		$currentWorkingDirectory = getcwd();
		
		echo '<br /> you are in: ' . $currentWorkingDirectory . '<br />';	
		
		echo '<table>';
		
		//while there is results left in the array, print
		while (	$row = mysqli_fetch_row($listR) ) {
			//create file contents		
			$writeString = implode("	", $row);
			$writeString .= "\n";
			
			//create screen display
			echo "<tr><td>";
			$printString = implode("</td><td>", $row);
			//either print to page or print to file, do both now
			echo $printString;
			echo "</td></tr>";
			if ($writable) fwrite($file, $writeString);
		}
		echo '</table>';

		fclose($file);
		if ($writable) echo "<br />You just printed to: <a href='$filename' id = 'contactListLink'>$currentWorkingDirectory/$filename </a>";

	//	print_r($row);
	//	echo $fullString;		
        
	} else {
		echo "<p>No emails were modified between $date1 and $date2.</p>";
	}
	mysqli_close($db);

} else {

    $page_title = 'Fannie - Membership Module';
    $header = 'Generate Member Contact List';
    include_once('../includes/header.html');
    ?>
	<head>
	<link href="../style.css"
	      rel="stylesheet" type="text/css" />
	<script src="../src/CalendarControl.js"
		language="javascript"></script>
<!-- /////////////////// -->

    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
		Date.format = 'yyyy-mm-dd';
		$(function(){
				$('.datepick').datepicker({ 
	                                        startDate:'2007-08-01',
                                                endDate: (new Date()).asString(), 
                                                clickInput: true, 
                                                dateFormat: 'yy-mm-dd', 
                                                changeMonth: true, 
                                                changeYear: true,
                                                duration: 0
                                                 });
		   
// $('.datepick').focus();
		});
    </script>


<!-- /////////////////////// /   -->
	</head>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<p>Please select a start and end date for contact list generation.<br />
		If no end date is selected, it will default to today.</p>
		<table border="0" cellspacing="3" cellpadding="3">
			<tr>
			    <td colspan="2" align="left"><strong>Fields to be included:</strong></td>
			</tr>
			<tr><td colspan="2" align="right"><strong>Card Number</strong><input type="checkbox" name="card" /></td></tr>
			<tr><td colspan="2" align="right"><strong>First Name</strong><input type="checkbox" name="first" checked="checked" /></td></tr>
			<tr><td colspan="2" align="right"><strong>Last Name</strong><input type="checkbox" name="last" checked="checked" /></td></tr>
			<tr><td colspan="2" align="right"><strong>Email Address</strong><input type="checkbox" name="email" checked="checked" /></td></tr>
<!--			<tr><td colspan="2" align="right"><strong>Phone Number</strong><input type="checkbox" name="phone" /></td></tr> -->
			<tr>
			    <td colspan="2" align="left"><strong>Format of list</strong></td>
			</tr>
			<tr><td colspan="2" align="right">TSV (Tab Separated Value) File <input type="radio" name="type" value="tsv" checked /></td></tr>
<!--			<tr><td colspan="2" align="right">XLS (Spreadsheet) File <input type="radio" name="type" value="xls" /></td></tr>
			<tr><td colspan="2" align="right">HTML Table <input type="radio" name="type" value="html" /></td></tr> -->
			<tr>
				<td align="right">
					<p><b>Date Start</b></p>
					<p><b>End</b></p>
				</td>
				<td>
					<p><input class='datepick' type='text' size='10' name='date1' autocomplete='off' />&nbsp;&nbsp;*</p>
					<p><input class='datepick' type='text' size='10' name='date1' autocomplete='off' />&nbsp;&nbsp;*</p>
		<!--			<p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
					<p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
	-->			</td>
				<td colspan=2>
				<p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
				</td>
			</tr>
		</table><br />
		<input type="hidden" name="submitted" value="TRUE" />
		<table border="0" cellspacing="3" cellpadding="3">
			<tr>
				<td align="left"><button name="submit" value="submit">Submit</button></td>
			</tr>
		</table>
	</form>
    <?php
    include_once('../includes/footer.html');
}
?>
