<?php # Member Label Printing - member_label.php
// A script to generate labels from the membership database.

if (isset($_POST['submitted']) && $_POST['submit'] == 'submit') {
	
	if (!isset($_POST['date1'])) {
		$date1 = '2008-01-01 00:00:01';
	} else {
		$date1 = $_POST['date1'] . ' 00:00:01';
	}
	
	if (!isset($_POST['date2'])) {
		$date2 = date('Y-m-d') . ' 23:59:59';
	} else {
		$date2 = $_POST['date2'] . ' 23:59:59';
	}
	
	define('FPDF_FONTPATH','../src/fpdf/font/');
	require('../src/fpdf/fpdf.php');
	
        require_once('../includes/mysqli_connect.php');
	mysqli_select_db($db_slave, 'is4c_op');
        
	$query = "SELECT custdata.FirstName, custdata.LastName, custdata.CardNo, memtype.memDesc,
		CASE custdata.personNum
		WHEN 1 THEN 'Primary'
		WHEN 2 THEN 'Secondary'
		ELSE 'Error'
		END AS personNum
		FROM custdata INNER JOIN memtype ON (custdata.memType=memtype.memtype)
		WHERE custdata.memType NOT IN (0, 6, 7) AND custdata.modified BETWEEN '$date1' AND '$date2'
		AND custdata.personNum = 1
		ORDER BY custdata.LastName ASC";
	$result = mysqli_query($db_slave, $query);
	
	$pdf=new FPDF('P', 'mm', 'Letter');
	$diff = 3;
	$pdf->SetMargins(4.7625+$diff,14);
	$y = 14;
	$pdf->SetAutoPageBreak('off',0);
	$pdf->AddPage('P');
	$pdf->SetFont('Arial','B',12);
	$cell = 1;
	while ($row = mysqli_fetch_array($result)) {
		// 3 Columns, 10 Rows.
		$row[3] = ucfirst(strtolower($row[3]));
		
		if ($cell % 3 == 1) { // First Column
			$pdf->SetXY(4.7625+$diff, $y);
			$pdf->MultiCell(66.675,4.23,"\n" . $row[0] . " " . $row[1] . "\n" . $row[4] . "\n(" . $row[3] . ")\n" . $row[2] . "\n ",0,'C');
		} elseif ($cell % 3 == 2) { // Second Column
			$pdf->SetXY(71.4375+$diff, $y);
			$pdf->MultiCell(66.675,4.23,"\n" . $row[0] . " " . $row[1] . "\n" . $row[4] . "\n(" . $row[3] . ")\n" . $row[2] . "\n ",0,'C');
		} elseif ($cell % 3 == 0) { // Third Column
			$pdf->SetXY(138.1125+$diff, $y);
			$pdf->MultiCell(66.675,4.23,"\n" . $row[0] . " " . $row[1] . "\n" . $row[4] . "\n(" . $row[3] . ")\n" . $row[2] . "\n ",0,'C');
			$y = $y + 25.4; // New Row
		}
		if ($cell == 30) {
			$cell = 0;
			$y = 14;
			$pdf->AddPage('P');
		}
		$cell++;
	}
	$pdf->Output();

} elseif (isset($_POST['submitted']) && $_POST['submit'] == 'date') {
	require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
	mysqli_select_db($db_master, 'is4c_log');
	
	$query = "INSERT INTO is4c_log.memberlabels (date) VALUES (now())";
	$result = mysqli_query($db_master, $query);
	if (!$result) {
		$page_title = 'Fannie - Membership Module';
		$header = 'Print Member Labels';
		include_once('../includes/header.html');
		echo "<h1>The print date could not be set, please try again later.</h1>";
		include ('../includes/footer.html');
	}
	$page_title = 'Fannie - Membership Module';
	$header = 'Print Member Labels';
	include_once('../includes/header.html');
	$query = "SELECT DATE_FORMAT(MAX(date), '%W %M %D at %r') FROM is4c_log.memberlabels";
	$result = mysqli_query ($db_master, $query);
	list($lastprint) = mysqli_fetch_row($result);
	printf('
            <head>
            <link href="../style.css"
                  rel="stylesheet" type="text/css" />
            <script src="../src/CalendarControl.js"
                    language="javascript"></script>
            </head>
            <form name="labels" action="member_label.php" method="post">
                    <p>Please select a start and end date for label generation.<br />
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
                    <p>(Labels were last printed on: %s)</p>
            </form>', $lastprint);
	include_once('../includes/footer.html');
} else {
	require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
	mysqli_select_db($db_slave, 'is4c_log)');

	$query = "SELECT DATE_FORMAT(MAX(date), '%W %M %D at %r') FROM is4c_log.memberlabels";
	$result = mysqli_query ($db_slave, $query);
	list($lastprint) = mysqli_fetch_row($result);
	
	$page_title = 'Fannie - Membership Module';
	$header = 'Print Member Labels';
	include_once('../includes/header.html');
	printf('
            <head>
            <script src="../src/CalendarControl.js"
                    language="javascript"></script>
            </head>
            <form name="labels" action="member_label.php" method="post">
                    <p>Please select a start and end date for label generation.<br />
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
                    <p>(Labels were last printed on: %s)</p>
            </form>', $lastprint);
	include_once('../includes/footer.html');
}
?>
	