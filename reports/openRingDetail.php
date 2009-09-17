<html>
<head>
<Title>Open Ring Detail</Title>
<link rel="stylesheet" href="../style.css" type="text/css" />
</head>
<body>
<?php
setlocale(LC_MONETARY, 'en_US');

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

$date1 = $_GET['date1'];
$date2 = $_GET['date2'];
$dept = $_GET['dept'];

$year1 = substr($date1, 0, 4);
$year2 = substr($date2, 0, 4);
        
if ($year1 != date('Y')) $transtable = 'trans_' . $year1;
else $transtable = 'transarchive';

$deptQ = "SELECT dept_name FROM is4c_op.departments WHERE dept_no = $dept";
$deptR = mysqli_query($db_slave, $deptQ);
list($deptname) = mysqli_fetch_row($deptR);

$query = "SELECT date(t.datetime) as Date, t.total AS Price, t.emp_no, e.FirstName
	FROM $transtable AS t INNER JOIN is4c_op.employees AS e
                USING (emp_no)
	WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
        AND department = $dept
	AND trans_type = 'D'
        AND t.emp_no <> 9999
        AND trans_status <> 'X'
	ORDER BY datetime";

$result = mysqli_query($db_slave, $query);
if ($result) {
        echo '<center><h3>Open Ring Report For ' . ucfirst(strtolower($deptname)) . ' Department</h3></center>';
        echo '<center><table border="1" cellspacing="3" cellpadding="3">
                <tr><th>Date</th><th>Price</th><th>Emp #</th><th>Name</th></tr>' . "\n";
        while (list($date, $price, $emp_no, $name) = mysqli_fetch_row($result)) {
                echo '<tr><td align="left">' . $date . '</td><td align="center">' . number_format($price, 2) . '</td><td align="right">' . $emp_no . '</td><td>' . $name . '</td></tr>';
        }
        echo '</table></center>';
} else {
        echo "<p>$query</p>";
        echo '<p>' . mysqli_error($db_slave) . '</p>';
}
?>
