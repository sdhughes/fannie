<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db ($db_slave, 'is4c_log');

// Overall Stats...
$query = "SELECT DATE_FORMAT(DATE(datetime), '%m-%d-%y'), card_no, total
    FROM transarchive
    WHERE upc='MADISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999
    ORDER BY datetime";
$result = mysqli_query($db_slave, $query);
$num = mysqli_num_rows($result);
$runningTotal = 0;
echo '<h1>Overall Data</h1>
    <table cellpadding="3" cellspacing="3" border="1">';
echo '<tr><th>Date</th><th>Card No</th><th>Coupon Value</th></tr>';
while (list($date, $cardno, $total) = mysqli_fetch_row($result)) {
    $total = number_format(($total * -1) * (15/13), 2);
    echo "<tr><td align=\"center\">$date</td><td align=\"center\">$cardno</td><td align=\"center\">$$total</td></tr>";
    $runningTotal += $total;
}
echo '</table>';
echo "<p><b>Total Coupons Redeemed:&nbsp</b>$num</p>";
echo "<p><b>Total Coupon Redemption:&nbsp</b>$$runningTotal</p>";
echo "<p><b>Average Coupon Redemption:&nbsp</b>$" . number_format($runningTotal / $num, 2) . "</p><br /><br />";

// By Day...
$query = "SELECT DATE_FORMAT(DATE(datetime), '%m-%d-%y'), COUNT(upc), ROUND(SUM(total), 2)
    FROM transarchive
    WHERE upc='MADISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999
    GROUP BY DATE(datetime)
    ORDER BY datetime";
$result = mysqli_query($db_slave, $query);
echo '<h1>By Day</h1>
    <table cellpadding="3" cellspacing="3" border="1">';
echo '<tr><th>Date</th><th>Number Redeemed</th><th>Value Redeemed</th></tr>';
while (list($date, $count, $total) = mysqli_fetch_row($result)) {
    $total = number_format(($total * -1) * (15/13), 2);
    echo "<tr><td align=\"center\">$date</td><td align=\"center\">$count</td><td align=\"center\">$$total</td></tr>";
}
echo '</table>';
$query = "SELECT DATEDIFF(MAX(DATE(datetime)), MIN(DATE(datetime))), MIN(total)
    FROM transarchive
    WHERE upc='MADISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999";
$result = mysqli_query($db_slave, $query);
list($datediff, $max) = mysqli_fetch_row($result);
$datediff += 1;
echo "<p><b>Average # Per Day:&nbsp</b>" . number_format($num/$datediff, 2) . "</p>";
echo "<p><b>Average $ Per Day:&nbsp</b>$" . number_format($runningTotal/$datediff, 2) . "</p>";
echo "<p><b>Highest Redemption:&nbsp</b>$" . number_format($max * -1, 2) . "</p>";

$query = "SELECT DATE_FORMAT(DATE(datetime), '%m-%d-%y'), COUNT(upc) AS Count, ROUND(SUM(total), 2)
    FROM transarchive
    WHERE upc='MADISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999
    GROUP BY DATE(datetime)
    ORDER BY Count DESC";
$result = mysqli_query($db_slave, $query);
list($date, $maxcount) = mysqli_fetch_row($result);
echo "<p><b>Most In a Day:&nbsp</b>$maxcount coupons on $date</p>";

$query = "SELECT DATE_FORMAT(DATE(datetime), '%m-%d-%y'), ROUND(SUM(total), 2) AS Total, COUNT(upc) AS Count
    FROM transarchive
    WHERE upc='MADISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999
    GROUP BY DATE(datetime)
    ORDER BY Total ASC";
$result = mysqli_query($db_slave, $query);
list($date, $maxdollar) = mysqli_fetch_row($result);
echo "<p><b>Most In a Day:&nbsp</b>$" . number_format(($maxdollar * -1) * (15/13), 2) . " on $date</p>";
?>