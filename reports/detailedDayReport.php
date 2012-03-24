<?php # detailedDayReport.php - Give hour by hour report of daily sales, customer count, etc.
if (isset($_POST['submitted'])) {
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_log');
    
    $date = $_POST['date'];
    $year = substr($date, 0, 4);
    
    if (date('Y-m-d') != $date) $transtable = 'trans_' . $year;
    else $transtable = 'dtransactions';
    
    echo "<p>Detailed Daily Report For: " . date('l F jS, Y', strtotime($date)) . "</p>";
    
    for ($i = 8; $i <= 23; $i++) {
        $SalesByHour[$i] = '0.00';
        $memSalesByHour[$i] = '0.00';
        $CountByHour[$i] = 0;
        $memCountByHour[$i] = 0;
    }
    
    $memCountQ = "SELECT COUNT(upc) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND emp_no <> 9999
        AND memtype IN (1,2)
        AND staff NOT IN (1,2,5)
        AND trans_status <> 'X'
        AND trans_subtype <> 'LN'";
    $memCountR = mysqli_query($db_slave, $memCountQ);
    list($memCount) = mysqli_fetch_row($memCountR);
    
    $CountQ = "SELECT COUNT(upc) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND emp_no <> 9999
        AND trans_status <> 'X'
        AND trans_subtype <> 'LN'";
    $CountR = mysqli_query($db_slave, $CountQ);
    list($Count) = mysqli_fetch_row($CountR);
    
    $memSalesQ = "SELECT ROUND(SUM(total),2) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND memtype IN (1,2)
        AND staff NOT IN (1,2,5)";
    $memSalesR = mysqli_query($db_slave, $memSalesQ);
    list($memSales) = mysqli_fetch_row($memSalesR);
    
    
    $SalesQ = "SELECT ROUND(SUM(total),2) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999";
    $SalesR = mysqli_query($db_slave, $SalesQ);
    list($Sales) = mysqli_fetch_row($SalesR);
    
    $memSalesByHourQ = "SELECT ROUND(SUM(total),2), HOUR(datetime) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND memtype IN (1,2)
        AND staff NOT IN (1,2,5)
        GROUP BY HOUR(datetime)";
    $memSalesByHourR = mysqli_query($db_slave, $memSalesByHourQ);
    while ($row = mysqli_fetch_array($memSalesByHourR)) {
        $memSalesByHour[$row[1]] = $row[0];
    }
    
    $SalesByHourQ = "SELECT ROUND(SUM(total),2), HOUR(datetime) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        GROUP BY HOUR(datetime)";
    $SalesByHourR = mysqli_query($db_slave, $SalesByHourQ);
    while ($row = mysqli_fetch_array($SalesByHourR)) {
        $SalesByHour[$row[1]] = $row[0];
    }
    
    $memCountByHourQ = "SELECT COUNT(upc), HOUR(datetime) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND memtype IN (1,2)
        AND staff NOT IN (1,2,5)
        GROUP BY HOUR(datetime)";
    $memCountByHourR = mysqli_query($db_slave, $memCountByHourQ);
    while ($row = mysqli_fetch_array($memCountByHourR)) {
        $memCountByHour[$row[1]] = $row[0];
    }
    
    $CountByHourQ = "SELECT COUNT(upc), HOUR(datetime) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND trans_status <> 'X'
        AND emp_no <> 9999
        GROUP BY HOUR(datetime)";
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
        <td align='center'>\${$SalesByHour[$i]}</td>
        <td align='center'>\${$memSalesByHour[$i]}</td>
        <td align='center'>{$CountByHour[$i]}</td>
        <td align='center'>{$memCountByHour[$i]}</td>
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
    
    echo '</table>';
    
    echo "<br /><br />
        <table cellpadding='5' cellspacing='2'><tr>
        <th align = 'left'><b>Total Sales: </b>$$Sales</th>
        <th align = 'left'><b>Customer Count: </b>$Count</th>
        <th align = 'left'><b>Average Bag: </b>$" . number_format($Sales / $Count, 2) . "</th>
        <th align = 'left'><b>Member Representation: </b>" . number_format(($memCount / $Count) * 100, 2) . "%</th></tr>
        <tr>
        <th align = 'left'><b>Total Member Sales: </b>$$memSales</th>
        <th align = 'left'><b>Member Count: </b>$memCount</th>
        <th align = 'left'><b>Member Average Bag: </b>$" . number_format($memSales / $memCount, 2) . "</th>
        <th align = 'left'><b>% Sales to Members: </b>" . number_format(($memSales / $Sales) * 100, 2) . "%</th>
        </tr></table>";
    
    
} else { // Show the form
$page_title = 'Fannie - Reports Module';
$header = 'Detailed Daily Report';
include ('../includes/header.html');
        ?>
        
<!-- <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
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
	<script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
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
		
//    $('.datepick').focus();
		});
        </script>
	<?php

?>
<form target="_blank" action="detailedDayReport.php" method="POST">
    <p>Which day do you want a report for?&nbsp&nbsp</p>
    <span style="width: 175px !important;float:left;"><input type="text" size="10" name="date" class="datepick" autocomplete="off" /></span>
    <br /><br />
    <input type="hidden" name="submitted" value="TRUE" />
    <button name="submit" type="submit" id='myButton'>Show me the numbers!</button>
</form> 
<?php
include ('../includes/footer.html');
}
