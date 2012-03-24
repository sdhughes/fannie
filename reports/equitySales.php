<?php

$page_title = "Fannie - Reporting Module";
$header = "Equity Sales by Day";
	include_once("../includes/header.html");
	include_once("../includes/mysqli_connect.php");
?>    
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
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd'});
        });
    </script>
<?php

if (isset($_POST['submit'])) {
	mysqli_select_db($db_master, 'is4c_log') or die ("Couldn't select database: " . mysqli_error($db_master));

	$date1 = $_POST['date1'];
	$date2 = $_POST['date2'];
	
	$year1 = substr($date1,0,4);
	$year2 = substr($date2,0,4);


    // Equity sales
    $salesQ = "SELECT dept, dept_no, SUM(total),date(datetime) FROM (";
    for ($i = $year1; $i <= $year2; $i++) {
        $salesQ .= "SELECT d.dept_name AS dept, d.dept_no AS dept_no, ROUND(SUM(t.total),2) AS total, datetime
            FROM is4c_op.departments AS d, is4c_log.trans_$i AS t
            WHERE d.dept_no = t.department
                AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                AND t.department IN (45)
                AND t.trans_status <> 'X'
                AND t.emp_no <> 9999 group by date(datetime)
            ";

        if ($i == $year2) {
            if ($date2 == date('Y-m-d')) {
                $salesQ .= " UNION ALL SELECT d.dept_name AS dept, d.dept_no AS dept_no, ROUND(SUM(t.total),2) AS total, datetime
                    FROM is4c_op.departments AS d, is4c_log.dtransactions AS t
                    WHERE d.dept_no = t.department
                        AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                        AND t.department IN 
(45)
                        AND t.trans_status <> 'X'
                        AND t.emp_no <> 9999 group by date(datetime)
                    ";
            }

            $salesQ .= ") AS yearSpan group by date(datetime)";

        } else $salesQ .= " UNION ALL ";

    }

	$salesR = mysqli_query($db_master,$salesQ) or die ("Couldn't complete query: " . mysqli_error($db_master));

$totalEquity = 0;
echo "<h2>Earned Membership Equity</h2>";
echo "<table class='thinborder'><td>Date</td><td>&nbsp;</td><td>Amt</td>";
	while ($equityArray = mysqli_fetch_array($salesR)) {

		echo "<tr><td>$equityArray[3]</td><td>$equityArray[0] </td><td> $$equityArray[2].</p></td></tr>";
        $totalEquity += $equityArray[2];	
	}
echo "</table>";
    echo "<h2>Total Equity Sales: $$totalEquity</h2>";
}

?>
    <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='POST' >
        <p>Please select the dates between which we'll pull the equity sales for.</p>
        <input type="text" name="date1" class="datepick" />
        <input type="text" name="date2" class="datepick" />
        <input type="submit" name="submit" value="Submit" />
    </form>

<?php
	include_once("../includes/footer.html");
?>
