<?php # avgDetailedDay.php - For getting average info about one day of the week over a period of time.

if (isset($_POST['submitted'])) {
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_log');

    $errors = array();
    $days = array('Pick One', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

    if ($_POST['day'] == 0) {
        $errors[] = "You forgot to select a day.";
    } else {
        $day = $_POST['day'];
    }

    if (empty($_POST['date1'])) {
        $errors[] = "You didn't enter a start date.";
    } else {
        $date1 = escape_data($_POST['date1']);
    }

    if (empty($_POST['date2'])) {
        $errors[] = "You didn't enter an end date.";
    } else {
        $date2 = escape_data($_POST['date2']);
    }

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    if (empty($errors)) {

        $numdaysQ = "SELECT COUNT(date) FROM is4c_log.dates WHERE date BETWEEN '$date1' and '$date2' and DAYOFWEEK(date)=$day";
        $numdaysR = mysqli_query($db_slave, $numdaysQ);
        list($numdays) = mysqli_fetch_row($numdaysR);

        echo "<p>Detailed Daily Report For {$days[$day]} From: " . date('l F jS, Y', strtotime($date1)) . " to " . date('l F jS, Y', strtotime($date2)) . "</p>";

        for ($i = 8; $i <= 23; $i++) {
            $SalesByHour[$i] = '0.00';
            $memSalesByHour[$i] = '0.00';
            $CountByHour[$i] = 0;
            $memCountByHour[$i] = 0;
        }

        // Member customer count.
        $memCountQ = "SELECT SUM(plu) FROM (";

        for ($year = $year1; $year <= $year2; $year++) {

            $memCountQ .= "SELECT COUNT(upc) AS plu FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND upc = 'DISCOUNT'
                AND emp_no <> 9999
                AND memtype IN (1,2)
                AND staff NOT IN (1,2,5)
                AND trans_status <> 'X'
                AND trans_subtype <> 'LN'";

            if ($year == $year2) {
                $memCountQ .= ") AS yearSpan";
            } else {
                $memCountQ .= " UNION ALL ";
            }

        }

        $memCountR = mysqli_query($db_slave, $memCountQ);
        list($memCount) = mysqli_fetch_row($memCountR);

        // Customer count
        $CountQ = "SELECT SUM(plu) FROM (";

        for ($year = $year1; $year <= $year2; $year++) {

            $CountQ .= "SELECT COUNT(upc) AS plu FROM is4c_log.trans_$year
                        WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                        AND upc = 'DISCOUNT'
                        AND emp_no <> 9999
                        AND trans_status <> 'X'
                        AND trans_subtype <> 'LN'";

            if ($year == $year2) {
                $CountQ .= ") AS yearSpan";
            } else {
                $CountQ .= " UNION ALL ";
            }

        }

        $CountR = mysqli_query($db_slave, $CountQ);
        list($Count) = mysqli_fetch_row($CountR);


        // Member sales totals.
        $memSalesQ = "SELECT ROUND(SUM(total),2) FROM (";

        for ($year = $year1; $year <= $year2; $year++) {

            $memSalesQ .= "SELECT ROUND(SUM(total),2) AS total FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND department <> 0
                AND trans_status <> 'X'
                AND emp_no <> 9999
                AND memtype IN (1,2)
                AND staff NOT IN (1,2,5)";

            if ($year == $year2)
                $memSalesQ .= ") AS yearSpan";
            else
                $memSalesQ .= " UNION ALL ";

        }

        $memSalesR = mysqli_query($db_slave, $memSalesQ);
        list($memSales) = mysqli_fetch_row($memSalesR);

        // Overall sales.
        $SalesQ = "SELECT ROUND(SUM(total),2) FROM (";

        for ($year = $year1; $year <= $year2; $year++) {

            $SalesQ .= "SELECT ROUND(SUM(total),2) AS total FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND department <> 0
                AND trans_status <> 'X'
                AND emp_no <> 9999";

            if ($year == $year2)
                $SalesQ .= ") AS yearSpan";
            else
                $SalesQ .= " UNION ALL ";
        }

        $SalesR = mysqli_query($db_slave, $SalesQ);
        list($Sales) = mysqli_fetch_row($SalesR);


        // Member Sales by hour.
        $memSalesByHourQ = "SELECT ROUND(SUM(total),2), hour FROM (";
        for ($year = $year1; $year <= $year2; $year++) {

            $memSalesByHourQ .= "SELECT ROUND(SUM(total),2) AS total, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND department <> 0
                AND trans_status <> 'X'
                AND emp_no <> 9999
                AND memtype IN (1,2)
                AND staff NOT IN (1,2,5)
                GROUP BY `hour`";

            if ($year == $year2)
                $memSalesByHourQ .= ") AS yearSpan GROUP BY hour";
            else
                $memSalesByHourQ .= " UNION ALL ";

        }

        $memSalesByHourR = mysqli_query($db_slave, $memSalesByHourQ);
        while ($row = mysqli_fetch_array($memSalesByHourR)) {
            $memSalesByHour[$row[1]] = $row[0];
        }


        // Overall Sales by hour.
        $SalesByHourQ = "SELECT ROUND(SUM(total),2), hour FROM (";
        for ($year = $year1; $year <= $year2; $year++) {

            $SalesByHourQ .= "SELECT ROUND(SUM(total),2) AS total, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND department <> 0
                AND trans_status <> 'X'
                AND emp_no <> 9999
                GROUP BY `hour`";

            if ($year == $year2)
                $SalesByHourQ .= ") AS yearSpan GROUP BY hour";
            else
                $SalesByHourQ .= " UNION ALL ";

        }

        $SalesByHourR = mysqli_query($db_slave, $SalesByHourQ);
        while ($row = mysqli_fetch_array($SalesByHourR)) {
            $SalesByHour[$row[1]] = $row[0];
        }


        // Member count by hour
        $memCountByHourQ = "SELECT SUM(plu), hour FROM (";
        for ($year = $year1; $year <= $year2; $year++) {

            $memCountByHourQ .= "SELECT COUNT(upc) AS plu, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND upc = 'DISCOUNT'
                AND trans_status <> 'X'
                AND emp_no <> 9999
                AND memtype IN (1,2)
                AND staff NOT IN (1,2,5)
                GROUP BY `hour`";

            if ($year == $year2)
                $memCountByHourQ .= ") AS yearSpan GROUP BY hour";
            else
                $memCountByHourQ .= " UNION ALL ";

        }

        $memCountByHourR = mysqli_query($db_slave, $memCountByHourQ);
        while ($row = mysqli_fetch_array($memCountByHourR)) {
            $memCountByHour[$row[1]] = $row[0];
        }


        // Customer count by hour
        $CountByHourQ = "SELECT SUM(plu), hour FROM (";
        for ($year = $year1; $year <= $year2; $year++) {

            $CountByHourQ .= "SELECT COUNT(upc) AS plu, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
                WHERE DATE(datetime) BETWEEN '$date1' and '$date2' and DAYOFWEEK(datetime)=$day
                AND upc = 'DISCOUNT'
                AND trans_status <> 'X'
                AND emp_no <> 9999
                GROUP BY `hour`";

            if ($year == $year2)
                $CountByHourQ .= ") AS yearSpan GROUP BY hour";
            else
                $CountByHourQ .= " UNION ALL ";

        }

        $CountByHourR = mysqli_query($db_slave, $CountByHourQ);
        while ($row = mysqli_fetch_array($CountByHourR)) {
            $CountByHour[$row[1]] = $row[0];
        }

        echo '<table border="2"><tr>
            <th align="center">Hour</th>
            <th align="center">Total Sales</th>
            <th align="center">Member Sales</th>
            <th align="center">Customer Count</th>
            <th align="center">Member Count</th>
            <th align="center">% of Total Customers</th>
            <th align="center">% of Gross Sales</th>
            <th align="center">Average Bag</th>
            <th align="center">% of Member Customers</th>
            <th align="center">% of Member Gross Sales</th>
            <th align="center">Member Average Bag</th></tr>';

        for ($i = 8; $i <= 23; $i++) {
            if ($i <= 11) {$suffix = 'AM'; $curi = $i; $nexti = $i + 1;}
            elseif ($i == 12) {$suffix = 'PM'; $curi = 'Noon'; $nexti = 1;}
            elseif ($i == 23) {$suffix = NULL; $curi = $i -12; $nexti = 'Midnight';}
            else {$suffix = 'PM'; $curi = $i - 12; $nexti = $curi + 1;}
            if ($nexti == 12 && $i != 23) {$nexti = 'Noon'; $suffix = NULL;}
            echo "<tr>
            <td align='center'>$curi-$nexti$suffix</t>
            <td align='center'>\$" . number_format($SalesByHour[$i] / $numdays, 2) . "</td>
            <td align='center'>\$" . number_format($memSalesByHour[$i] / $numdays, 2) . "</td>
            <td align='center'>" . round($CountByHour[$i] / $numdays) . "</td>
            <td align='center'>" . round($memCountByHour[$i] / $numdays) . "</td>
            <td align='center'>" . number_format(($CountByHour[$i] / $Count) * 100, 2) . "%</td>
            <td align='center'>" . number_format(($SalesByHour[$i] / $Sales) * 100, 2) . "%</td>
            <td align='center'>";
            if ($CountByHour[$i] == 0) {
                echo 'N/A';
            } else {
                echo "$" . number_format($SalesByHour[$i] / $CountByHour[$i], 2) . "</td>";
            }
            echo "<td align='center'>" . number_format(($memCountByHour[$i] / $memCount) * 100, 2) . "%</td>
            <td align='center'>" . number_format(($memSalesByHour[$i] / $memSales) * 100, 2) . "%</td>
            <td align='center'>";
            if ($memCountByHour[$i] == 0) {
                echo 'N/A';
            } else {
                echo "$" . number_format($memSalesByHour[$i] / $memCountByHour[$i], 2) . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";

        echo "<br /><br />
            <table cellpadding='5' cellspacing='2'><tr>
            <th align = 'left'><b>Total Sales: </b>$$Sales</th>
            <th align = 'left'><b>Average Sales: </b>$" . number_format($Sales / $numdays, 2) . "</th>
            <th align = 'left'><b>Customer Count: </b>" . round($Count / $numdays) . "</th>
            <th align = 'left'><b>Average Bag: </b>$" . number_format($Sales / $Count, 2) . "</th>
            <th align = 'left'><b>Member Representation: </b>" . number_format(($memCount / $Count) * 100, 2) . "%</th></tr>
            <tr>
            <th align = 'left'><b>Total Member Sales: </b>$$memSales</th>
            <th align = 'left'><b>Average Member Sales: </b>$" . number_format($memSales / $numdays, 2) . "</th>
            <th align = 'left'><b>Member Count: </b>" . round($memCount / $numdays) . "</th>
            <th align = 'left'><b>Member Average Bag: </b>$" . number_format($memSales / $memCount, 2) . "</th>
            <th align = 'left'><b>% Sales to Members: </b>" . number_format(($memSales / $Sales) * 100, 2) . "%</th>
            </tr></table>";

        } else {
            $header = "Average Detailed Daily Report";
            $page_title = "Fannie - Reports Module";
            include ('../includes/header.html');
            echo '<p>The following errors were noted: </p><ul>';
            foreach ($errors as $msg) {
                echo "<li>$msg</li>";
            }
            echo '</ul><br /><br />';
            include ('../includes/footer.html');
        }

} else {

    $header = "Average Detailed Daily Report";
    $page_title = "Fannie - Reports Module";
    include ('../includes/header.html');

    $days = array('Pick One', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
    ?>
<!--    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
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
    </script> -->
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
<!--    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
-->    <script type="text/javascript" src="../includes/javascript/myquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
		Date.format = 'yyyy-mm-dd';
		$(function(){
		  $('.datepick').datepicker({   startDate:'2007-08-01',
                                                endDate: (new Date()).asString(), 
                                                clickInput: true, 
                                                dateFormat: 'yy-mm-dd', 
                                                changeMonth: true, 
                                                changeYear: true,
                                                duration: 0
                                                 });

			//  $('.datepick').focus();
    		});
    </script>
    <?php
    echo '<form method="post" action="avgDetailedDay.php" target="_blank">
        <p>Which day would you like the report for? <select name="day">';
        foreach ($days as $key => $value) {
            echo "<option value='$key'>$value</option>";
        }
    echo '</select></p>
    <p>And which date range?</p>
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
    <button name="submit" type="submit" id="myButton">Submit</button>
    </form>';
    include ('../includes/footer.html');
}
?>
