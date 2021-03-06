<?php
//2010-11-08 sdh - edited queries to include last names too....

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

if (isset($_POST['submitted'])) {
  //  if ($_POST['type'] == 'month') {
	$startMonth = (int) $_POST['start_month'];
	$endMonth = (int) $_POST['end_month'];
	$startYear = (int) $_POST['start_year'];
	$endYear = (int) $_POST['end_year'];
	$startDate = $startYear . '-' . $startMonth . '-01';
	$endDate = $endYear . '-' . $endMonth . '-31';

        // Overall numbers
        $diff = $endMonth - $startMonth + 1;
	$header = "<p><font size='+1'>Overall Numbers for a $diff month period.</font></p><br />";
	$where = "t.date BETWEEN '$startDate' AND '$endDate'";
        $type = 'Month';

  /*  } elseif ($_POST['type'] == 'payperiod') {
        // Overall numbers
        $diff = $_POST['end_period'] - $_POST['start_period'] + 1;
	$where = "t.periodID BETWEEN {$_POST['start_period']} AND {$_POST['end_period']}";
        $header = "<p><font size='+1'>Overall Numbers for $diff pay periods.</font></p><br />";
	$type = 'Pay Period';

    }*/
    echo $header;

    // By Department
    $runningtotal = 0;
    echo '<p>By Department</p>';
    $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) AS Hours,
	s.ShiftName AS Shift
	FROM is4c_log.timesheet t INNER JOIN is4c_log.shifts s ON t.area=s.ShiftID
	WHERE $where AND t.area <> 0
	GROUP BY t.area";

    $result = mysqli_query($db_slave, $query);
    if (mysqli_num_rows($result) != 0) {
	echo '<table border="1" cellpadding="3"><tr><th>Shift</th><th>Hours</th><th>Average Hours Per ' . $type . '</th></tr>';
	while ($row = mysqli_fetch_row($result)) {
	    echo "<tr><td align='left'>{$row[1]}</td><td align='right'>{$row[0]}</td><td align='right'>" . number_format($row[0] / $diff, 2) . "</td></tr>";
	    $runningtotal += $row[0];
	}
	echo '<tr><th align="left">Total Hours</th><th align="right">' . $runningtotal . '</th><th align="right">' . number_format($runningtotal / $diff, 2) . '</th></tr>';
	echo '</table><br />';
    } else {
	echo '<p>Your report generated no results. Maybe try a different date range?</p>';
	include ('../includes/footer.html');
	exit();
    }


$area_with_most_hours = "select ts.date, ts.emp_no, MAX(ts.Hours_Worked) as hours, ts.area, ts.periodID, ts.vacation, ts.sub, e.pay_rate as wage, e.pay_rate * MAX(ts.Hours_Worked) as earned from (select t.date, t.emp_no,ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) as Hours_Worked, t.area,t.periodID,t.vacation,t.sub from is4c_log.timesheet as t where t.date BETWEEN '$startDate' and '$endDate' and area != 0 group by concat(emp_no, ' - ' , date, ' - ' , area) order by Hours_Worked DESC) as ts inner JOIN is4c_op.employees as e on ts.emp_no = e.emp_no INNER JOIN is4c_log.shifts as s on ts.area = s.ShiftName group by emp_no,date ORDER BY ts.date,ts.area ASC";

echo "<br/> "  . $startDate . " AND " . $endDate . "<br />Query: $area_with_most_hours<br />Hours worked by Emp # + Area<br />";
    $areaResult = mysqli_query($db_slave, $area_with_most_hours) or die ("Query error: " . mysqli_error($db_slave) . "<br />");

    if (mysqli_num_rows($areaResult) != 0) {
	echo '<table border="1" cellpadding="3"><tr><th>Date</th><th>Emp No</th><th>Hours</th><th>Area</th><th>Period</th></tr>';
	while ($row = mysqli_fetch_row($areaResult)) {
	    echo "<tr>
<td align='left'>" . $row[0] . "</td>
<td align='right'>" . $row[1] . "</td>
<td align='right'>" . $row[2] . "</td>
<td align='right'>" . $row[3] . "</td>
<td align='right'>" . $row[4] . "</td>
<td align='right'>" . $row[7] . "</td>
<td align='right'>" . sprintf("$%.2f",($row[2] * $row[7])) . "</td>
</tr>";
	    //$runningtotal += $row[0];
	}
	//echo '<tr><th align="left">Total Hours</th><th align="right">' . $runningtotal . '</th><th align="right">' . number_format($runningtotal / $diff, 2) . '</th></tr>';
	echo '</table><br />';
    } else {
	echo '<p>Your report generated no results. Maybe try a different date range?</p>';
	include ('../includes/footer.html');
	exit();
    }
/*
    // By Person
    $runningtotal = 0;
    $budgettotal = 0;
    $totaldiff = 0;

    echo '<p>By Person</p>
            <p>Staff</p>';

    $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) AS Hours,
	CONCAT(e.RealFirstName, ' ', e.LastName) AS Name, e.emp_no
	FROM is4c_log.timesheet t INNER JOIN is4c_op.employees e ON t.emp_no=e.emp_no
	WHERE $where
	AND t.sub = 0
	GROUP BY e.emp_no
	ORDER BY Name ASC";
    $result = mysqli_query($db_slave, $query);

    if (mysqli_num_rows($result) != 0) {
	echo '<table border="1" cellpadding="3"><tr><th>First Name</th><th>Hours</th><th>Budgeted Hours</th>
	    <th>Difference</th><th style="width:50px">Average Hours Per Pay Period</th>
	    <th style="width:50px">Budgeted Hours Per Pay Period</th><th>Difference</th></tr>';
	while ($row = mysqli_fetch_row($result)) {
	    $budgetQ = "SELECT (budgeted_hours * 2) FROM is4c_op.employees WHERE emp_no = {$row[2]}";
	    $budgetR = mysqli_query($db_slave, $budgetQ);
	    list($budgetHours) = mysqli_fetch_row($budgetR);
	    $difference = number_format(($row[0] / $diff) - $budgetHours, 2);
	    $actual_diff = number_format($row[0] - ($budgetHours * $diff), 2);
	    printf('
		   <tr>
		    <td align="left">%s</td>
		    <td align="left">%s</td>
		    <td align="left">%s</td>
		    <td align="left">%s%s%s</td>
		    <td align="left">%s</td>
		    <td align="left">%s</td>
		    <td align="left">%s%s%s</td>
		</tr>',
		$row[1], $row[0], number_format($budgetHours * $diff, 2),
		($actual_diff > (2*$diff) ? '<font color="red">' : '<font>'), $actual_diff, '</font>',
		number_format($row[0] / $diff, 2), $budgetHours, ($difference > 2 ? '<font color="red">' : '<font>'), $difference, '</font>'
	    );
	    $runningtotal += $row[0];
	    $budgettotal += $budgetHours;
	    $totaldiff += $difference;
	}
	echo '<tr><th align="left">Total Hours</th><th align="left">' . number_format($runningtotal, 2) . '</th><th>' . number_format($budgettotal * $diff, 2) .
	    '</th><th>' . number_format(($budgettotal * $diff) - $runningtotal, 2) . '</th><th align="left">' . number_format($runningtotal / $diff, 2) .
	    '</th>' . "<th>" . number_format($budgettotal, 2) . "</th><th>" . number_format($totaldiff, 2) . "</th>" . '</tr>';
	echo '</table><br />';
    } else {
	echo '<p>Your report generated no results. Maybe try a different date range?</p>';
	include ('../includes/footer.html');
	exit();
    }
    $staffrunningtotal = $runningtotal;


    echo '<p>Subs</p>';
    $query = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) AS Hours,
	CONCAT(e.RealFirstName, ' ', e.LastName) AS Name, e.emp_no
	FROM is4c_log.timesheet t INNER JOIN is4c_op.employees e ON t.emp_no=e.emp_no
	WHERE $where
	AND t.sub = 1
	GROUP BY e.emp_no
	ORDER BY Name ASC";
    $result = mysqli_query($db_slave, $query);

    $runningtotal = 0;

    if (mysqli_num_rows($result) != 0) {
	echo '<table border="1" cellpadding="3"><tr><th>First Name</th><th>Hours</th><th style="width:50px">Average Hours Per ' . $type . '</th></tr>';
	while ($row = mysqli_fetch_row($result)) {
	    $budgetQ = "SELECT (budgeted_hours * 2) FROM is4c_op.employees WHERE emp_no = {$row[2]}";
	    $budgetR = mysqli_query($db_slave, $budgetQ);
	    list($budgetHours) = mysqli_fetch_row($budgetR);
	    $difference = number_format(($row[0] / $diff) - $budgetHours, 2);
	    $actual_diff = number_format(($budgetHours * $diff) - $row[0], 2);
	    echo "<tr>
		    <td align='left'>{$row[1]}</td>
		    <td align='left'>{$row[0]}</td>
		    <td align='left'>" . number_format($row[0] / $diff, 2) . "</td>
		</tr>";
	    $runningtotal += $row[0];
	}
	echo '<tr>
		<th align="left">Total Hours</th>
		<th align="left">' . number_format($runningtotal, 2) . '</th>
		<th align="left">' . number_format($runningtotal / $diff, 2) . '</th>
	    </tr>';
	echo '</table><br />';

	$subrunningtotal = $runningtotal;
    }
    echo "<table border='1' cellspacing='3' cellpadding='3'>
	<tr>
	    <td>Total Budgeted Hours: " . number_format($budgettotal * $diff, 2) . "</td>
	    <td>Average Budgeted Hours: " . number_format($budgettotal, 2) . "</td>
	</tr>
	<tr>
	    <td>Total Staff Hours Worked: " . number_format($staffrunningtotal, 2) . "</td>
	    <td>Average Staff Hours Worked: " . number_format($staffrunningtotal / $diff, 2) . "</td>
	</tr>
	<tr>
	    <td>Total Sub Hours Worked: " . number_format($subrunningtotal, 2) . "</td>
	    <td>Average Sub Hours Worked: " . number_format($subrunningtotal / $diff, 2) . "</td>
	</tr>
	<tr>
	    <p>Total Hours Worked: " . number_format($staffrunningtotal + $subrunningtotal, 2) . "</td>
	    <p>Average Hours Worked: " . number_format(($staffrunningtotal + $subrunningtotal) / $diff, 2) . "</td>
	</tr>
	<tr>
	    <td>Difference: " . number_format(($budgettotal * $diff) - ($staffrunningtotal + $subrunningtotal), 2) . "</td>
	    <td>Average Difference: " . number_format((($budgettotal * $diff) - ($staffrunningtotal + $subrunningtotal)) / $diff, 2) . "</td>
	</tr>
	</table><br />";

    // Detailed hours section.
    echo "<p><font size='+1'>Detailed Hours Breakdown</p></font><br />";
    $shiftQ = "SELECT ShiftName, ShiftID FROM is4c_log.shifts ORDER BY ShiftID ASC";
    $shiftR = mysqli_query($db_slave, $shiftQ);
    while ($row = mysqli_fetch_row($shiftR)) {
	echo "<p><b><i>{$row[0]}</b></i></p>";
	echo '<table border="1" cellpadding="3"><tr><th>Employee</th><th>Hours Worked</th><th>Average Hours Per ' . $type . '</th></tr>';
	if ($row[1] == 13) {
                $detailQ = "SELECT (e.RealFirstName , ' ',e.Lastname) AS Name, ROUND(SUM(vacation), 2) AS Hours
                    FROM is4c_log.timesheet t INNER JOIN is4c_op.employees e ON t.emp_no=e.emp_no
                    WHERE $where
                    AND t.area = {$row[1]}
                    GROUP BY t.emp_no";
            } else {
                $detailQ = "SELECT CONCAT(e.RealFirstName, ' ', e.Lastname) AS Name, ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) AS Hours
                    FROM is4c_log.timesheet t INNER JOIN is4c_op.employees e ON t.emp_no=e.emp_no
                    WHERE $where
                    AND t.area = {$row[1]}
                    GROUP BY t.emp_no";
            }
            $detailR = mysqli_query($db_slave, $detailQ);
            if (mysqli_num_rows($detailR) != 0) {
                while ($detailRow = mysqli_fetch_row($detailR)) {
                    echo "<tr><td align='left'>{$detailRow[0]}</td><td align='right'>{$detailRow[1]}</td><td align='right'>" . number_format($detailRow[1] / $diff, 2) . "</td></tr>";
                }
                echo '</table><br /><br />';
	} else {
	    echo '<tr><td colspan="3" align="center">No Hours For That Area In The Specified Range</td></tr></table>';
	}
    }
*/
print_r($_POST);

} else { // Draw the form.

//////////////////////////////
//
// First Submission, clean form
//
//////////////////////////////
    $header = 'Labor Hours Plus Report';
    $page_title = 'Fannie - Reporting Module';
    include ('../includes/header.html');
    $months = array(1 => 'January','February','March','April','May','June','July','August','September','October','November','December');
    echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">
        <h3>Report Options</h3>
        <div>
 '; 
//       <input type="radio" name="type" value="month" checked="CHECKED" id="byMonth"/><label for="byMonth"><font color="black">By Month</font></label><br />
 echo '       (Months are inclusive)
        <p>Start Month: <select name="start_month">';
    foreach ($months AS $number => $month) {
        echo "<option value='$number'>$month</option>\n";
    }
    echo '</select><select name="start_year">';
    for ($year = 2008; $year <= date('Y'); $year++) {
	printf('<option value="%u"%s>%u</option>', $year, ($year == date('Y') ? ' SELECTED="SELECTED"' : NULL), $year);
    }

    echo '</select></p>
    <p>End Month: <select name="end_month">';
    foreach ($months AS $number => $month) {
        echo "<option value='$number'>$month</option>\n";
    }
    echo '</select><select name="end_year">';
    for ($year = 2008; $year <= date('Y'); $year++) {
	printf('<option value="%u"%s>%u</option>', $year, ($year == date('Y') ? ' SELECTED="SELECTED"' : NULL), $year);
    }

    echo '</select></p></div>'; 

	
	
    echo '<div>';
/*
    $query = "SELECT periodID, DATE_FORMAT(periodStart, '%W %M %D, %Y') AS Start, DATE_FORMAT(periodEnd, '%W %M %D, %Y') AS End FROM payperiods WHERE periodID > 17 AND periodStart < now() ORDER BY periodID DESC";
    $result = mysqli_query($db_slave, $query);
    echo '<input type="radio" name="type" value="payperiod" id="byPayperiod" /><label for="byPayperiod"><font color="black">By Payperiod</font></label><br />
        (Payperiods are also inclusive)
        <p>Start Pay Period: <select name="start_period">';
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        echo "<option value=\"{$row['periodID']}\">{$row['Start']}</option>";
    }
    echo '</select></p>
        <p>Ending Pay Period: <select name="end_period">';
    $query = "SELECT periodID, DATE_FORMAT(periodStart, '%W %M %D, %Y') AS Start, DATE_FORMAT(periodEnd, '%W %M %D, %Y') AS End FROM payperiods WHERE periodID > 17 AND periodStart < now() ORDER BY periodID DESC";
    $result = mysqli_query($db_slave, $query);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        echo "<option value=\"{$row['periodID']}\">{$row['End']}</option>";
    }
    echo '</select></p>'; 
*/
    echo '<input type="hidden" name="submitted" value="TRUE" />
    <button name="submit" type="submit">Do It!!</button>
    </form>';
    echo "</div>";//added this in to make it format.... sdh 2011-11-21
    include ('../includes/footer.html');
}
?>
