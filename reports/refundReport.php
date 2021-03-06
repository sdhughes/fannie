<?php
$header = "Refund Report";
$page_title = "Fannie - Reports Module";
require_once ('../includes/header.html');

echo '<link href="../style.css" rel="stylesheet" type="text/css">';
echo "<link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.core.css\" />
    <link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.theme.css\" />
    <link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.datepicker.css\" />
    <script type=\"text/javascript\" src=\"../includes/javascript/jquery.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/datepicker/date.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/ui.datepicker.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/ui.core.js\"></script>
    <script type=\"text/javascript\">
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
";

//  <script src="../src/CalendarControl.js" language="javascript"></script>';

if (isset($_POST['submitted'])) {
    
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db ($db_slave, 'is4c_log');
    
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    
    $query = "SELECT DATE(datetime), emp_no, upc, description, ROUND(unitprice, 2), itemqtty FROM transarchive
        WHERE trans_status = 'R'
        AND trans_type IN ('I', 'D')
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND date(datetime) BETWEEN '$date1' AND '$date2'
        ORDER BY emp_no, datetime";
    $result = mysqli_query($db_slave, $query);
    
    if ($result) {
        echo '<table border="1" cellspacing="3" cellpadding="3">
            <tr><th>Date</th><th>Emp. #</th><th>UPC</th><th>Description</th><th>Price</th><th>Quantity</th></tr>';
        while (list($date, $emp_no, $upc, $description, $price, $qty) = mysqli_fetch_row($result)) {
            echo "<tr><td>$date</td><td>$emp_no</td><td>$upc</td><td>$description</td><td>$price</td><td>$qty</td></tr>";
        }
        echo '</table>';
    } else {
        echo "<p>Error...</p><p>Query: $query</p>" .  mysqli_error($db_slave);
    }
    
    echo "<br /><br />";
    
    $otherQ = "SELECT COUNT(t.total), SUM(t.total), t.emp_no, e.FirstName
        FROM transarchive AS t
            INNER JOIN is4c_op.employees AS e ON (t.emp_no = e.emp_no)
        WHERE trans_status = 'R'
        AND trans_type IN ('I', 'D')
        AND department <> 0
        AND trans_status <> 'X'
        AND t.emp_no <> 9999
        AND date(datetime) BETWEEN '$date1' AND '$date2'
        GROUP BY t.emp_no
        ORDER BY t.emp_no";
    $otherR = mysqli_query($db_slave, $otherQ);
    
    $refundCount = 0;
    $cashCount = 0;

    if ($otherR) {
        echo '<table border="1" cellspacing="3" cellpadding="3">
            <tr><th>Emp. #</th><th>Name</th><th>Number of Refunds</th><th>Total Value of Refunds</th><th>Average Refund</th></tr>';
        while (list($count, $sum, $emp_no, $name) = mysqli_fetch_row($otherR)) {
	    $refundCount += $count;
	    $cashCount += $sum;
            echo "<tr><td>$emp_no</td><td>$name</td><td align='center'>$count</td><td align='right'>$" . number_format($sum * -1, 2) . "</td><td align='right'>$" . number_format(($sum * -1)/$count, 2) . "</td></tr>";
        }
        echo "<tr><td>&nbsp;</td><td>Totals:</td><td align='center'>$refundCount</td><td align='right'> $$cashCount</td><td align='right'> $" . number_format(($cashCount*-1)/$refundCount,2) . "</td></tr></table>";
    } else {
        echo "<p>Error...</p><p>Query: $otherQ</p>" .  mysqli_error($db_slave);
    }
    
    mysqli_close($db_slave);

} else {
    echo '<form action="refundReport.php" method="POST">
            <div id="box">
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                        <td align="right">
                                <p><b>Date Start</b> </p>
                        <p><b>End</b></p>
                        </td>
                        <td>			
                        <!--        <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                                <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p> -->
                                <p><input type="text" size="10" name="date1" class="datepick" /></p>
                                <p><input type="text" size="10" name="date2" class="datepick" /></p>
                        </td>
                        <td colspan=2>
                                <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                        </td>
                    </tr>
                    <tr> 
                        <td>&nbsp;</td>
                        <td> <input type=submit name=submit value="Submit"> </td>
                        <input type="hidden" name="submitted" value="TRUE">
                    </tr>
            </table>
            </div>
            </form>';
        
}

require_once ('../includes/footer.html');
?>
