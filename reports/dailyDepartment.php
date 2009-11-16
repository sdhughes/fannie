<?php
$page_title = 'Fannie - Reports Module';
$header = 'Department Daily Report';
include ('../includes/header.html');
require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

if (isset($_POST['submitted'])) {
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    $dept = $_POST['department'];
    
    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);
    
    $query = "SELECT ROUND(SUM(total),2), date FROM (";
    
    for ($year = $year1; $year <= $year2; $year++) {
        $query .= "SELECT ROUND(SUM(total), 2) AS total, DATE(datetime) AS `date` FROM is4c_log.trans_$year
            WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
            AND department = $dept
            AND trans_status <> 'X'
            AND emp_no <> 9999
            GROUP BY `date`";
        
        if ($year == $year2) {
            if (date('Y-m-d') == $date2) {
                $query .= " UNION SELECT ROUND(SUM(total), 2) AS total, DATE(datetime) AS `date` FROM is4c_log.dtransactions
                    WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
                    AND department = $dept
                    AND trans_status <> 'X'
                    AND emp_no <> 9999
                    GROUP BY `date`";
            }
            $query .= ") AS yearSpan GROUP BY date";
        } else {
            $query .= " UNION ";
        }
        
    }
    
    $sales = 0.00;
    $result = mysqli_query($db_slave, $query);
    echo '<table cellpadding="3" border="1" cellspacing="3"><thead><tr><th align="left">Date</th><th align="right">Daily Total</th></tr></thead><tbody>';
    while ($row = mysqli_fetch_row($result)) {
        echo "<tr><td align=\"left\">" . date('l F jS', strtotime($row[1])) . "</td>
            <td align=\"right\">$$row[0]</td></tr>";
        $sales += $row[0];
    }
    echo '</tbody><tfoot><tr><td align="left"><b>Total</b></td><td align="right"><b>$' . number_format($sales, 2) . '</b></td></tr></tfoot></table>';
    
    $query = "SELECT (DATEDIFF('$date2', '$date1')+1)";
    $result = mysqli_query($db_slave, $query);
    
    list($datediff) = mysqli_fetch_row($result);
    
    echo "<p>The average daily sales for $datediff days were $" . number_format($sales / $datediff, 2) . ".</p>";
    //echo "<p>The total sales were $" . number_format($sales, 2) . ".</p>";



} else { // Show the form
echo '<link href="../style.css"
        rel="stylesheet" type="text/css">
<script src="../src/CalendarControl.js"
        language="javascript"></script>';
?>
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
<script type="text/javascript" src="../includes/javascript/jquery.js"></script>
<script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
<script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker.js"></script>
<script type="text/javascript">
    Date.format = 'yyyy-mm-dd';
    $(function(){    
        $('.datepick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true})
        .dpSetOffset(0, 125);
    });
</script>
<form target="_blank" action="dailyDepartment.php" method="POST">
    <p>Which Department?</p>
    <select name="department">
        <?php
            $query = "SELECT * FROM is4c_op.departments WHERE dept_discount = 1";
            $result = mysqli_query($db_slave, $query);
            while ($row = mysqli_fetch_array($result)) {
                echo "<option value=\"{$row['dept_no']}\">" . ucfirst(strtolower($row['dept_name'])) . "</option>";
            }
        ?>
    </select>
    <p>What date range do you want a report for?&nbsp&nbsp</p>
    <table>
        <tr>
            <td>From: </td>
            <td><input type="text" size="10" name="date1" class="datepick" autocomplete="off" /></td>
        </tr>
        <tr>
            <td>To: </td>
            <td><input type="text" size="10" name="date2" class="datepick" autocomplete="off" /></td>
        </tr>
    </table>
    <input type="hidden" name="submitted" value="TRUE" /><br />
    <button name="submit" type="submit">Show me the numbers!</button>
</form>
<?php
}
include ('../includes/footer.html');
?>
