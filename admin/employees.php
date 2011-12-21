<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_op');
mysqli_select_db($db_slave, 'is4c_op');

ob_start();
$page_title = 'Fannie - Admin Module';
$header = 'Employee Management';
include_once ('../includes/header.html');
echo "<script type='text/javascript' src='../includes/javascript/emp_mgmt.js'></script>";

//print_r($_POST);
if (isset($_POST['submit'])) {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
}

if (isset($_POST['submit'])) {
	foreach ($id AS $emp_no) {
		if (isset($EmpActive[$emp_no]) && $EmpActive[$emp_no] == 'on') $active = 1; else $active = 0;
		if (empty($CardNo[$emp_no]) || !isset($CardNo[$emp_no])) {$CardNo[$emp_no] = 'NULL';}
		$updateQ = "UPDATE employees SET
                        FirstName = '" . escape_data($FirstName[$emp_no]) . "',
                        LastName = '" . escape_data($LastName[$emp_no]) . "',
                        RealFirstName = '" . escape_data($RealFirstName[$emp_no]) . "',
                        card_no=$CardNo[$emp_no], pay_rate=$Wage[$emp_no],
                        budgeted_hours=$Budgeted_Hours[$emp_no],
                        EmpActive=$active,
                        JobTitle = '{$JobTitle[$emp_no]}'
                    WHERE emp_no = $emp_no";
		$updateR = mysqli_query($db_master, $updateQ) or die("Update Error: " . mysqli_error());
		if (!$updateR) {echo '<p><font color="red">One or more employees could not be updated. Please try again.</font> Query: ' . $updateQ . '</p>';}
	}
	$max = $_POST['add'];
	if (isset($EmpActive[$max]) && $EmpActive[$max] == 'on' && $_POST['submit'] == 'add') {
		$insertQ = "INSERT INTO employees VALUES
                    ($max, $max, $max,
                    '" . escape_data($FirstName[$max]) . "',
                    '" . escape_data($LastName[$max]) . "',
                    '{$JobTitle[$max]}', 1, 15, 15, " . (empty($CardNo[$max]) ? 'NULL' : $CardNo[$max]) . ", ". (empty($Wage[$max]) ? 'NULL' :$Wage[$max]) . ", " . (empty($Budgeted_Hours[$max]) ? 'NULL' : $Budgeted_Hours[$max]) . ",
                    '" . escape_data($RealFirstName[$max]) . "')";
		echo $insertQ . "<br />";

        $insertR = mysqli_query($db_master, $insertQ) or die('Insert Error: ' . mysqli_error($db_master));
		if (mysqli_affected_rows($db_master) != 1) {
                        //printf("mySQL Error: %s\t Query: %s", mysqli_error($db_master), $insertQ);
			echo '<p><font color="red">The new employee could not be added. Please try again.</font></p>';
		}
	}
}

if (isset($_POST['pdf'])) {
	ob_end_clean();
	define('FPDF_FONTPATH','../src/fpdf/font/');
	require('../src/fpdf/fpdf.php');
	$order = $_POST['sort'];
	$query = "SELECT emp_no, FirstName
		FROM employees
		WHERE EmpActive=1
		ORDER BY $order ASC";
	$result = mysqli_query($db_slave, $query);

	$pdf=new FPDF('P', 'mm', 'Letter');
	$pdf->SetMargins(5, 14);
	$y = 14;
	$pdf->SetAutoPageBreak('off', 0);
	$pdf->AddPage('P');
	$pdf->SetFont('Courier', 'B', 14);

	for ($i = 1; $i <=2; $i++) {

		$height = 6;
		$width = 40;
		$query = "SELECT emp_no, FirstName
			FROM employees
			WHERE EmpActive=1
			ORDER BY $order ASC";
		$result = mysqli_query($db_slave, $query);
		$cell = 1;
		$num = mysqli_num_rows($result);
		$box_h = (ROUND($num / 2, 0) + 1) * $height;
		$box_w = ($width * 4) + 5;

		// Draw some boxes in case it's not an even number of employees.
		if ($i == 2 && !isset($once)) {
			$once = TRUE;
			$y = $y + 10;
			$pdf->Rect(5, $y, $box_w, $box_h);
		} elseif ($i == 1 && !isset($box)) {
			$box = TRUE;
			$pdf->Rect(5, $y, $box_w, $box_h);
		}

		$pdf->SetXY(5, $y);
		$pdf->Cell($width, $height, 'Cashier No.', 1, 'C');
		$pdf->SetXY(5 + $width, $y);
		$pdf->Cell($width, $height, 'First Name', 1, 'C');
		$pdf->SetXY(5 + ($width * 2), $y);
		$pdf->Cell(5, $height, '', 1);
		$pdf->SetXY(10 + ($width * 2), $y);
		$pdf->Cell($width, $height, 'Cashier No.', 1, 'C');
		$pdf->SetXY(10 + ($width * 3), $y);
		$pdf->Cell($width, $height, 'First Name', 1, 'C');
		$y = $y + $height;

		while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
			// 2 Columns.

			if ($cell % 2 == 1) { // First Column
				$pdf->SetXY(5, $y);
				$pdf->Cell($width, $height, $row[0], 1, 'C');
				$pdf->SetXY(5 + $width, $y);
				$pdf->Cell($width, $height, $row[1], 1, 'C');
				$pdf->SetXY(5 + ($width * 2), $y);
				$pdf->Cell(5, $height, '', 1);
			} elseif ($cell % 2 == 0) { // Second Column
				$pdf->SetXY(10 + ($width * 2), $y);
				$pdf->Cell($width, $height, $row[0], 1, 'C');
				$pdf->SetXY(10 + ($width * 3), $y);
				$pdf->Cell($width, $height, $row[1], 1, 'C');
				$y = $y + 6; // New Row
			}
			/*
			if ($cell == 30) {
				$cell = 0;
				$y = 14;
				$pdf->AddPage('P');
			}
			*/
			$cell++;
		}
	}
	$pdf->Output();

	exit();
}

$query = "SELECT * FROM employees ORDER BY emp_no ASC";
$result = mysqli_query($db_slave, $query);

$maxQ = "SELECT MAX(emp_no)+1 FROM employees";
$maxR = mysqli_query($db_slave, $maxQ);
list($max) = mysqli_fetch_array($maxR, MYSQLI_NUM);

echo '<form action="employees.php" method="POST">
	<h3 align="center">Select Sort Order</h3>
	<p align="center">First Name<input type="radio" name="sort" value="FirstName">
	Cashier Number<input type="radio" name="sort" value="emp_no" checked="checked">
	<p align="center"><button name="pdf" type="submit">Generate Printable List</button></p>
	</form>';

echo '<form action="employees.php" method="POST">';
echo "<table id='employee_table'>";
echo "<th>Emp No</th><th>Last Name</th><th>Nickname</th><th>First Name</th><th>Job Title</th><th>Card No.</th><th class='wage_element'>Wage</th>";
echo "<th>Budgeted Hours</th><th>Active?</th><th>&nbsp;</th>";


$bg = '#eeeeee';
while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	$id = $row[0];
	echo "<tr bgcolor='$bg'>";
	echo "<td class='emp_no'>".$row[0]."</td>";
	echo '<td><input type="text" name="LastName[' . $id . ']" maxlength="20" size="10" value="' . $row[4] . '"></td>';
	echo '<td><input type="text" name="FirstName[' . $id . ']" maxlength="20" size="10" value="' . $row[3] . '"></td>';
	echo '<td><input type="text" name="RealFirstName[' . $id . ']" maxlength="20" size="10" value="' . $row[12] . '"></td>';
	echo "<td><select name='JobTitle[$id]'>
		<option value='STAFF'";
	if ($row[5] == 'STAFF') echo ' SELECTED';
	echo ">Staff</option>
		<option value='SUB'";
	if ($row[5] == 'SUB') echo ' SELECTED';
	echo ">Sub</option>
		<option value='WORKING MEMBER'";
	if ($row[5] == 'WORKING MEMBER') echo ' SELECTED';
	echo ">Working Member</option>
		</select></td>";
	echo '<td><input type="text" name="CardNo[' . $id . ']" maxlength="5" size="5" value="' . $row[9] . '" /></td>';
	echo '<td class="wage_element"><input type="text" name="Wage[' . $id . ']" maxlength="6" size="6" value="' . number_format($row[10], 2) . '" /></td>';
	echo '<td><input type="text" name="Budgeted_Hours[' . $id . ']" maxlength="6" size="6" value="' . number_format($row[11], 2) . '" /></td>';
	echo "<td><input type='checkbox' name='EmpActive[" . $id . "]'";
	if ($row[6] == 1) echo ' checked="checked" ';
	echo "/>";
	echo "<input type=hidden name='id[]' value=".$row[0].">&nbsp;</td>"; 
    echo "<td><button class='edit_employee' >edit</button></td>";
    echo "</tr>\n";
}
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	$max;
	echo "<tr bgcolor='$bg'>";
	echo "<td>".$max."</td>";
	echo '<td><input type="text" name="LastName[' . $max . ']" maxlength="20" size="10"></td>';
	echo '<td><input type="text" name="FirstName[' . $max . ']" maxlength="20" size="10"></td>';
	echo '<td><input type="text" name="RealFirstName[' . $max . ']" maxlength="20" size="10"></td>';
	echo "<td><select name='JobTitle[$max]'>
		<option value='STAFF'>Staff</option>
		<option value='SUB'>Sub</option>
		<option value='WORKING MEMBER'>Working Member</option>
		</select></td>";
	echo '<td><input type="text" name="CardNo[' . $max . ']" maxlength="5" size="5" /></td>';
	echo '<td class="wage_element"><input type="text" name="Wage[' . $max . ']" maxlength="6" size="6" /></td>';
	echo '<td><input type="text" name="Budgeted_Hours[' . $max . ']" maxlength="6" size="6" /></td>';
	echo "<td><input type='checkbox' name='EmpActive[" . $max . "]' />";
	echo "<input type='hidden' name='add' value='" . $max . "'>&nbsp;</td>";

echo "<td><input type=submit name=submit value=add></td></tr>\n";
echo "</table></form>";

include ('../includes/footer.html');
ob_end_flush();
?>
