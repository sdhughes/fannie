<?php # Phone List Generation - phoneList.php
// A script to generate a phone bank list from the membership database.

if (isset($_POST['submitted'])) {

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
	mysqli_select_db($db_comet, 'comet');

	$query = "SELECT Shareholder, Secondary, CardNo, Phone
            FROM phoneList
            WHERE DATE(Modified) BETWEEN '$date1' AND '$date2'
            ORDER BY CardNo ASC";

	$result = mysqli_query($db_slave, $query);

	echo $_POST['type'];


} else {

    $page_title = 'Fannie - Membership Module';
    $header = 'Generate Phone Bank List';
    include_once('../includes/header.html');
    ?>
	<head>
	<link href="../style.css"
	      rel="stylesheet" type="text/css" />
	<script src="../src/CalendarControl.js"
		language="javascript"></script>
	</head>
	<form action="contactList.php" method="post">
		<p>Please select a start and end date for contact list generation.<br />
		If no end date is selected, it will default to today.</p>
		<table border="0" cellspacing="3" cellpadding="3">
			<tr>
			    <td colspan="2" align="left"><strong>Fields to be included:</strong></td>
			</tr>
			<tr><td colspan="2" align="right"><strong>Card Number</strong><input type="checkbox" name="card" checked="checked" /></td></tr>
			<tr><td colspan="2" align="right"><strong>First Name</strong><input type="checkbox" name="first" checked="checked" /></td></tr>
			<tr><td colspan="2" align="right"><strong>Last Name</strong><input type="checkbox" name="last" checked="checked" /></td></tr>
			<tr><td colspan="2" align="right"><strong>Email Address</strong><input type="checkbox" name="email" /></td></tr>
			<tr><td colspan="2" align="right"><strong>Phone Number</strong><input type="checkbox" name="phone" /></td></tr>
			<tr>
			    <td colspan="2" align="left"><strong>Format of list</strong></td>
			</tr>
			<tr><td colspan="2" align="right">TSV (Tab Separated Value) File <input type="radio" name="type" value="tsv" /></td></tr>
			<tr><td colspan="2" align="right">XLS (Spreadsheet) File <input type="radio" name="type" value="xls" /></td></tr>
			<tr><td colspan="2" align="right">HTML Table <input type="radio" name="type" value="html" /></td></tr>
			<tr>
				<td align="right">
					<p><b>Date Start</b></p>
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
			</tr>
		</table>
	</form>
    <?php
    include_once('../includes/footer.html');
}
?>
