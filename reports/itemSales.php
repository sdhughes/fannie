<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_op');

$page_title = 'Fannie - Reports Module';
$header = 'Item Movement Report';
include ('../includes/header.html');

if ((!isset($_POST['submit'])) && (!isset($_POST['upc'])) && (!isset($_GET['upc']))) {

?>
    <SCRIPT LANGUAGE="JavaScript">
        function putFocus(formInst, elementInst) {
            if (document.forms.length > 0) {
                document.forms[formInst].elements[elementInst].focus();
            }
        }
    </script>
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
<!--    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
-->    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
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
<!--    <BODY onLoad="putFocus(0,0);">
-->        <form method="post" action="itemSales.php">
            <div id="box">
                <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                        <input name=upc type=text id=upc> Enter UPC/PLU or product name here<br /><br /><br />
                    </tr>
                    <tr>
                        <td align="right">
                            <p><b>Date Start</b> </p><p><b>End</b></p>
                        </td>
                        <td>
                            <p><input type="text" size="10" autocomplete="off" name="date1" class="datepick">&nbsp;&nbsp;*</p>
                            <p><input type="text" size="10" autocomplete="off" name="date2" class="datepick">&nbsp;&nbsp;*</p>
                        </td>
                        <td colspan=2>
                            <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                        </td>
                    </tr>
                    <tr>
                        <td><p><b>Separate Days</b></p></td><td><input name=byDate type=checkbox id=byDate /></td><td></td>
                    </tr>
                </table>
                <br />
                <p>* denotes a required field</p>
                <p>If no date is selected, it will default to today and August 1st, 2007.</p><br /><br />
                <input type=submit name=submit value="Submit">
            </div>

        </form>
    </body>
    <?php

}

$upc = (isset($_REQUEST['upc']) ? escape_data($_REQUEST['upc']) : NULL);
$date1 = (isset($_REQUEST['date1']) ? escape_data($_REQUEST['date1']) : NULL);
$date2 = (isset($_REQUEST['date2']) ? escape_data($_REQUEST['date2']) : NULL);

?>

<body>

<?php
if (isset($upc)) {
    if (is_numeric($upc)) {
	$upc = str_pad($upc,13,0,STR_PAD_LEFT);
        $queryItem = "SELECT * FROM products WHERE upc = '$upc'";
    } else {
        $queryItem = "SELECT * FROM products WHERE description LIKE '%$upc%' ORDER BY description";
    }
    $resultItem = mysqli_query($db_slave, $queryItem);
    $num = mysqli_num_rows($resultItem);

    if ($num == 0) {
        echo "<p>No item match found, would you like to add this (<a href='../item/itemMaint.php?submitted=search&upc=$upc'>" . $upc . " </a>) item?</p>";
    } elseif($num > 1) {
        //if there is more than one item resulting, display the individual items
        echo "<p class='directions'>Multiple items found. Please choose one for a more detailed search.</p>";
        echo "<table>";
        echo "<tr>
        <th>UPC</th>
        <th>Description</th>
        <th>Normal Price</th>
        ";
        
        for ($i=0;$i < $num;$i++) {
            $rowItem= mysqli_fetch_array($resultItem);
            $upc = $rowItem['upc'];
            echo "<tr><td><a href='itemSales.php?upc=$upc&date1=$date1&date2=$date2'>" . $upc . " </a></td><td>" . $rowItem['description'] . "</td><td> $" .$rowItem['normal_price']. "</td></tr>";
        }
        echo "</table>";
    } else {

        $today = date("F d, Y");
        if (empty($date1)) $date1 = '2007-08-01';
        if (empty($date2)) $date2 = date('Y-m-d');
        // Following lines creates a header for the report, listing sort option chosen, report date, date and department range.

        echo "Report on item $upc<br />
        Report run on: $today<br />
        From $date1 to $date2<br /><br />";

        $year1 = substr($date1, 0, 4);
        $year2 = substr($date2, 0, 4);

        if (isset($_POST['byDate'])) $byDateClause = ', Date(datetime)';
    
        if (is_numeric($upc)) {
            $query = "SELECT PLU, Description, Current, Price, Dept, Subdept, SUM(Qty) AS Qty, SUM(Total) AS Total, Scale AS scale,datetime FROM (";
            for ($i = $year1; $i <= $year2; $i++) {
                $query .= "SELECT DISTINCT
                    p.upc AS PLU,
                    p.description AS Description,
                    ROUND(p.normal_price,2) AS Current,
                    ROUND(t.unitPrice,2) AS Price,
                    d.dept_name AS Dept,
                    s.subdept_name AS Subdept,
                    SUM(t.quantity) AS Qty,
                    ROUND(SUM(t.total),2) AS Total,
                    p.scale as Scale,
                    date(t.datetime) as datetime
                FROM is4c_log.trans_$i t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
                WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.emp_no <> 9999
                    AND t.trans_status <> 'X'
                    AND t.upc = $upc
                GROUP BY CONCAT(t.upc, '-',t.unitprice) $byDateClause";

                if ($i == $year2) {
                    if (substr($date2, 0, 10) == date('Y-m-d')) {
                        $query .= " UNION ALL SELECT DISTINCT
                            p.upc AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS Current,
                            ROUND(t.unitPrice,2) AS Price,
                            d.dept_name AS Dept,
                            s.subdept_name AS Subdept,
                            SUM(t.quantity) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            date(t.datetime) as datetime
                        FROM is4c_log.dtransactions t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
                        WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            AND t.upc = $upc
                        GROUP BY CONCAT(t.upc, '-',t.unitprice) $byDateClause";
                    }

                    $query .= ") AS yearSpan GROUP BY yearSpan.PLU, Price $byDateClause ORDER BY datetime";

                } else $query .= " UNION ALL ";
            }

        } elseif (!is_numeric($upc)) {
            $query = "SELECT PLU, Description, Current, Price, Dept, Subdept, SUM(Qty) AS Qty, SUM(Total) AS Total, Scale AS scale FROM (";
            for ($i = $year1; $i <= $year2; $i++) {
                $query .= "SELECT DISTINCT
                        p.upc AS PLU,
                        p.description AS Description,
                        ROUND(p.normal_price,2) AS Current,
                        ROUND(t.unitPrice,2) AS Price,
                        d.dept_name AS Dept,
                        s.subdept_name AS Subdept,
                        SUM(t.quantity) AS Qty,
                        ROUND(SUM(t.total),2) AS Total,
                        p.scale as Scale,
                        date(t.datetime) as datetime
                    FROM is4c_log.trans_$year t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
                    WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
                        AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                        AND t.emp_no <> 9999
                        AND t.trans_status <> 'X'
                        AND t.description LIKE '%$upc%'
                    GROUP BY CONCAT(t.upc, '-',t.unitprice) $byDateClause";
                if ($i == $year2) {
                    if (substr($date2, 0, 10) == date('Y-m-d')) {
                        $query .= "UNION ALL SELECT DISTINCT
                                p.upc AS PLU,
                                p.description AS Description,
                                ROUND(p.normal_price,2) AS Current,
                                ROUND(t.unitPrice,2) AS Price,
                                d.dept_name AS Dept,
                                s.subdept_name AS Subdept,
                                SUM(t.quantity) AS Qty,
                                ROUND(SUM(t.total),2) AS Total,
                                p.scale as Scale,
                                date(t.datetime) as datetime
                            FROM is4c_log.dtransactions t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
                            WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
                                AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                                AND t.emp_no <> 9999
                                AND t.trans_status <> 'X'
                                AND t.description LIKE '%$upc%'
                            GROUP BY CONCAT(t.upc, '-',t.unitprice) $byDateClause";
                    }

                    $query .= ") AS yearSpan GROUP BY yearSpan.PLU, Price $byDateClause ORDER BY datetime";

               } else $query .= " UNION ALL ";
            }
        }

        $query2 = "SELECT DATEDIFF('$date2', '$date1')";
        $result2 = mysqli_query($db_slave, $query2);
        $row2 = mysqli_fetch_row($result2);
        $numdays = $row2[0] + 1;

        $result = mysqli_query($db_slave, $query);

        if (isset($_POST['byDate'])) $dateClause = "<th>Date</th>";
        else $dateClause = "";

        echo "<table border=1 cellpadding=3 cellspacing=3>";
        echo "<tr>$dateClause<th>UPC</th><th>Description</th><th>Current Price</th><th>Price Sold At</th><th>Department</th><th>Subdept</th><th>Qty</th><th>Sales</th><th>Scale</th></tr>";

        if (!$result) {
            $message  = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Whole query: ' . $query;
            die($message);
        }
        $row_count = mysqli_num_rows($result);
            //echo "<tr><td colspan=9>Result: $row_count</td></tr>";
        if ($row_count < 1) {
            echo "<tr><td colspan=9>No Results</td></tr>";

        } else {
        
            $total_sold = 0;
            $total_value_sold = 0;

            while ($myrow = mysqli_fetch_row($result)) { //create array from query
                if ($myrow[8] == 0) {$myrow[8] = 'No';} elseif ($myrow[8] == 1) {$myrow[8] = 'Yes';}
                $total_sold += $myrow[6];
                $total_value_sold += $myrow[7];
                if (isset($_POST['byDate'])) printf('<tr><td>%s</td><td><a href="/item/itemMaint.php?submitted=search&upc=%s">' . $myrow[0] . '</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",$myrow[9],$myrow[0], $myrow[1], number_format($myrow[2], 2), number_format($myrow[3], 2),$myrow[4],$myrow[5],number_format($myrow[6], 2), number_format($myrow[7], 2), $myrow[8]);
                else printf('<tr><td><a href="/item/itemMaint.php?submitted=search&upc=%s">' . $myrow[0] . '</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",$myrow[0], $myrow[1], number_format($myrow[2], 2), number_format($myrow[3], 2),$myrow[4],$myrow[5],number_format($myrow[6], 2), number_format($myrow[7], 2), $myrow[8]);
                //convert row information to strings, enter in table cells
            }
        }
        $avg_sold_per_day = number_format(($total_sold / $numdays),2);
        if ($total_sold != 0) {$avg_price_sold_at = "$" . number_format(($total_value_sold / $total_sold),2);} else {$avg_price_sold_at = 'N/A';}

        echo "</table>\n";//end table
        //echo $query . "<br />";
       // print_r $_POST;
       // echo "<br />";

        echo "  <p>Number of Days: $numdays </p>
                <p>Total Sold: $total_sold </p>
                <p>Total Sales: $" . $total_value_sold . "</p>
                <p>An average of $avg_sold_per_day were sold per day.</p>
                <p>The average price was " . $avg_price_sold_at . ".</p>";
    }
}

include ('../includes/footer.html');
?>
