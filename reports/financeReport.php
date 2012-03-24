<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

$months = array(1 => 'January','February','March','April','May','June','July','August','September','October','November','December');
$storeArea = 2440;

if (!isset($_POST['submitted'])) {
   $page_title = 'Fannie - Reports Module';
   $header = 'Finance Report';
   include ('../includes/header.html');
   echo '<form method="post" action="' . $_SERVER["PHP_SELF"] . '" target="_blank">
      <div id="box">
      <table border="0" cellspacing="3" cellpadding="3">
         <tr>
            <th>&nbsp;</th>
            <th colspan="2">Month</th>
            <th colspan="2">Year</th>
         </tr>
         <tr>
            <td><input type="radio" id="month" name="subType" value="month" /></td>
            <td colspan="2" align="center"><select name="month" onclick="document.getElementById(\'month\').checked=\'checked\';">';
               foreach ($months AS $key => $month) {
                  if (date('m') == ($key+1)) echo "<option value=$key SELECTED>$month</option>";
                  else echo "<option value=$key>$month</option>";
               }
               echo '</select></td>
            <td colspan="2" align="center"><select name="year" onclick="document.getElementById(\'month\').checked=\'checked\';">';
               for ($year = 2007; $year <= date('Y'); $year++) {
                  if (date('Y') == ($year)) echo "<option value=$year SELECTED>$year</option>";
                  else echo "<option value=$year>$year</option>";
               }
               echo '</select></td>
         </tr>
         <tr><td colspan="5">&nbsp;</td></tr>
         <tr>
            <td><input type="radio" id="range" name="subType" value="range" /></td>
            <td colspan="2" align="center"><b>Report Range:</b></td>
            <td colspan="1" align="center">
               <select name="quarter" onclick="document.getElementById(\'range\').checked=\'checked\';">
                  <option value="1">1st Quarter</option>
                  <option value="2">2nd Quarter</option>
                  <option value="3">3rd Quarter</option>
                  <option value="4">4th Quarter</option>
                  <option value="YTD">Year To Date</option>
               </select>
            </td>
            <td colspan="1" align="center">
               <select name="rangeYear" onclick="document.getElementById(\'range\').checked=\'checked\';">';
               for ($year = 2007; $year <= date('Y'); $year++) {
                  if (date('Y') == ($year)) echo "<option value=$year SELECTED>$year</option>";
                  else echo "<option value=$year>$year</option>";
               }
               echo '</select></td>
         </tr>
      </table>
      <br />
      <button name="submit" value="submit">Finance Report</button>
      <input type="hidden" name="submitted" value="true" />
  </div>
  </form>
  </body>';

   include ('../includes/footer.html');
} else {
   echo '<html><body>';

   $transtable = 'transarchive';

   switch ($_POST['subType']) {
      case 'month':
         $month = str_pad($_POST['month'], 2, 0, STR_PAD_LEFT);
         $year = $_POST['year'];
         if ($year != date('Y')) $transtable = 'trans_' . $year;
         $date1 = "$year-$month-01";

         $date2Q = "SELECT DATE_SUB(DATE_ADD('$date1', INTERVAL 1 MONTH), INTERVAL 1 DAY)";
         $date2R = mysqli_query($db_slave, $date2Q);
         list($date2) = mysqli_fetch_row($date2R);

         break;

      case 'range':

         if ($_POST['rangeYear'] < 2007 || $_POST['rangeYear'] > date('Y'))
            $year = date('Y');
         else
            $year = $_POST['rangeYear'];

         switch ($_POST['quarter']) {
            case 1:
               $date1 = $year . "-01-01";
               $date2 = $year . "-03-31";
               break;

            case 2:
               $date1 = $year . "-04-01";
               $date2 = $year . "-06-30";
               break;

            case 3:
               $date1 = $year . "-07-01";
               $date2 = $year . "-09-30";
               break;

            case 4:
               $date1 = $year . "-10-01";
               $date2 = $year . "-12-31";
               break;

            case 'YTD':
               $date1 = $year . "-01-01";
               $date2 = $year . "-12-31";
               break;
         }
         $transtable = "trans_$year";

         break;

      default:
         break;

   }

   $where = "DATE(datetime) BETWEEN '$date1' AND '$date2' AND emp_no <> 9999 AND trans_status <> 'X'";
   if ($year == date('Y') && $_POST['quarter'] == 'YTD')
      $datediffQ = "SELECT DATEDIFF(curdate(), '$date1')+1";
   else
      $datediffQ = "SELECT DATEDIFF('$date2', '$date1')+1";
   $datediffR = mysqli_query($db_slave, $datediffQ);
   list($datediff) = mysqli_fetch_row($datediffR);

    $numdaysQ = "SELECT COUNT(date), DAYOFWEEK(date) FROM is4c_log.dates WHERE date BETWEEN '$date1' AND '$date2' GROUP BY DAYOFWEEK(date)";
    $numdaysR = mysqli_query($db_slave, $numdaysQ);
    while (list($numdays, $day) = mysqli_fetch_row($numdaysR)) {
	$days[$day]['numdays'] = $numdays;
    }


   $customerCountQ = "SELECT COUNT(upc), DAYOFWEEK(datetime) FROM is4c_log.$transtable WHERE upc='DISCOUNT' AND $where GROUP BY DAYOFWEEK(datetime)";
   $customerCountR = mysqli_query($db_slave, $customerCountQ);
   $customerCount = 0;
   while (list($count, $day) = mysqli_fetch_row($customerCountR)) {
	$days[$day]['custCount'] = $count;
	$customerCount += $count;
   }

   $customerTotalQ = "SELECT SUM(total), DAYOFWEEK(datetime) FROM is4c_log.$transtable WHERE department BETWEEN 1 AND 35 AND $where GROUP BY DAYOFWEEK(datetime)";
   $customerTotalR = mysqli_query($db_slave, $customerTotalQ);
   $customerTotal = 0.0;
   while (list($total, $day) = mysqli_fetch_row($customerTotalR)) {
	$days[$day]['total'] = $total;
	$days[$day]['avg_bag'] = $total / $days[$day]['custCount'];
	$customerTotal += $total;
   }

    for ($i = 1; $i <= 7; $i++) {
	$busiestQ = "SELECT COUNT(upc) AS `count`, HOUR(datetime), DATE_FORMAT(datetime, '%l%p') FROM is4c_log.$transtable WHERE $where AND upc='DISCOUNT' AND DAYOFWEEK(datetime) = '$i' GROUP BY HOUR(datetime) ORDER BY `count` DESC LIMIT 3";
	$busiestR = mysqli_query($db_slave, $busiestQ);
	if (!$busiestR)
	    printf('<p>Query: %s</p><p>Error: %s</p>', $busiestQ, mysqli_error($db_slave));
	for ($j = 1; $j <= 3; $j++) {
	    list($trash, $trash, $hour) = mysqli_fetch_row($busiestR);
	    $days[$i][$j] = $hour;
	}
    }

   $avg_bag = number_format(($customerTotal / $customerCount), 2);
   $avg_count = round($customerCount / $datediff, 0);

   $activeQ = "SELECT Cardno FROM is4c_op.custdata WHERE memtype IN (1,2) GROUP BY Cardno";
   $activeR = mysqli_query($db_slave, $activeQ);
   $active = mysqli_num_rows($activeR);

   $inactiveQ = "SELECT Cardno FROM is4c_op.custdata WHERE memtype = 5 GROUP BY Cardno";
   $inactiveR = mysqli_query($db_slave, $inactiveQ);
   $inactive = mysqli_num_rows($inactiveR);

   $refundQ = "SELECT Cardno FROM is4c_op.custdata WHERE memtype = 4 GROUP BY Cardno";
   $refundR = mysqli_query($db_slave, $refundQ);
   $refund = mysqli_num_rows($refundR);

   $paidinfullQ = "SELECT Cardno FROM is4c_op.custdata WHERE memtype = 1 GROUP BY Cardno";
   $paidinfullR = mysqli_query($db_slave, $paidinfullQ);
   $paidinfull = mysqli_num_rows($paidinfullR);

   $retention = number_format(($active / ($inactive + $active + $refund)) * 100, 1);

   $memberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department BETWEEN 1 AND 35 AND memtype IN (1,2) AND staff IN (0,2,3) AND $where";
   $memberSalesR = mysqli_query($db_slave, $memberSalesQ);
   list($memberSales) = mysqli_fetch_row($memberSalesR);

   $totalSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department BETWEEN 1 AND 35 AND $where";
   $totalSalesR = mysqli_query($db_slave, $totalSalesQ);
   list($totalSales) = mysqli_fetch_row($totalSalesR);

   $percenttoMembers = number_format(($memberSales / $totalSales) * 100,1);

   $transCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND trans_subtype <> 'LN' AND upc = 'DISCOUNT'";
   $transCountR = mysqli_query($db_slave, $transCountQ);
   list($transCount) = mysqli_fetch_row($transCountR);

   $memtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND trans_subtype <> 'LN' AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff IN (0,2,3)";
   $memtransCountR = mysqli_query($db_slave, $memtransCountQ);
   list($memtransCount) = mysqli_fetch_array($memtransCountR);

   $avg_memCount = round($memtransCount / $datediff, 0);

   $memberBag = number_format($memberSales / $memtransCount,2);
   $memberpercent = number_format(($memtransCount / $transCount) * 100,1);

    $totalHoursQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2) AS Hours
            FROM is4c_log.timesheet t
            WHERE t.date BETWEEN '$date1' AND '$date2'
		AND t.area <> 13";

    $totalHoursR = mysqli_query($db_slave, $totalHoursQ);
    list($totalHours) = mysqli_fetch_row($totalHoursR);


   $header = '<div style="text-align: center;">' . "
   <h2>Finance Report From " . date('M jS, Y', strtotime($date1)) . " to " . date('M jS, Y', strtotime($date2)) . "</h2>";
   $table = '<table style="margin-left:auto;margin-right:auto;" cellspacing="15" frame="border">
   <tr>
      <th colspan="2">Member Stats</th>
      <th colspan="2">Member Sales</th>
   </tr>' .
   "<tr>
      <td>Total Active Members</td>
      <td>$active</td>
      <td>% of Sales to Members</td>
      <td>$percenttoMembers%</td>
   </tr>
   <tr>
      <td>Total Inactive Members</td>
      <td>$inactive</td>" .
      '<td colspan="2">&nbsp;</td>
   </tr>' .
   "<tr>
      <td>Member Retention Rate</td>
      <td>$retention%</td>
      <td>Member % of Customers</td>
      <td>$memberpercent%</td>
   </tr>" .

'</table>';

echo $header . $table;

printf('<br /><table style="margin-left:auto;margin-right:auto;" frame="border" border="1" cellspacing="3" cellpadding="3" style="text-align:center;">
    <thead>
    <tr>
	<th>&nbsp;</th>
	<th>Sunday</th>
	<th>Monday</th>
	<th>Tuesday</th>
	<th>Wednesday</th>
	<th>Thursday</th>
	<th>Friday</th>
	<th>Saturday</th>
    </tr>
    </thead>
    <tbody><tr><td>Average Count</td>');

for ($i = 1; $i <= 7; $i++) {
    printf('<td>%s</td>', number_format($days[$i]['custCount'] / $days[$i]['numdays'], 0));
}
echo '</tr><tr><td>Average Sales</td>';

for ($i = 1; $i <= 7; $i++) {
    printf('<td>$%s</td>', number_format($days[$i]['total'] / $days[$i]['numdays'], 2));
}
echo '</tr><tr><td>Average Bag</td>';

for ($i = 1; $i <= 7; $i++) {
    printf('<td>$%s</td>', number_format($days[$i]['avg_bag'], 2));
}

echo '</tr><tr><td>Busiest Hours</td>';

for ($i = 1; $i <= 7; $i++) {
    printf('<td>%s</td>', $days[$i][1] . ", " . $days[$i][2] . ", " . $days[$i][3]);
}

echo '</tr></tbody></table>
<p>Total Hours Worked: ' . number_format($totalHours, 0) . '</p>
<p>Sales Per Square Foot: $' . number_format(($totalSales * 7 / $storeArea) / ($datediff), 2) . '/ft<sup>2</sup></p>
<p>Sales Per Labor Hour: $' . number_format($totalSales / $totalHours, 2) . '/labor hour</p>
<p>Average Bag: $' . number_format($avg_bag, 2) . '</p>
<p>Average Member Bag: $' . number_format($memberBag, 2) . '</p>
<p>Average Cust Count: ' . number_format($customerCount / $datediff, 0) . '</p>';

$footer = '</body></html>';

for ($i = 8; $i <= 23; $i++) {
    $SalesByHour[$i] = '0.00';
    $memSalesByHour[$i] = '0.00';
    $CountByHour[$i] = 0;
    $memCountByHour[$i] = 0;
}
// Customer count
$CountQ = "SELECT COUNT(upc) AS plu FROM is4c_log.trans_$year
	    WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
	    AND upc = 'DISCOUNT'
	    AND emp_no <> 9999
	    AND trans_status <> 'X'
	    AND trans_subtype <> 'LN'";

$CountR = mysqli_query($db_slave, $CountQ);
if (!$CountR) printf('<p>Query: %s</p><p>Error: %s</p>', $CountQ, mysqli_error($db_slave));
list($Count) = mysqli_fetch_row($CountR);

// Overall sales.
$SalesQ = "SELECT ROUND(SUM(total),2) AS total FROM is4c_log.trans_$year
    WHERE DATE(datetime) BETWEEN '$date1' and '$date2'
    AND department BETWEEN 1 and 35
    AND trans_status <> 'X'
    AND emp_no <> 9999";

$SalesR = mysqli_query($db_slave, $SalesQ);
list($Sales) = mysqli_fetch_row($SalesR);

// Overall Sales by hour.
$SalesByHourQ .= "SELECT ROUND(SUM(total),2) AS total, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
    WHERE DATE(datetime) BETWEEN '$date1' and '$date2'
    AND department BETWEEN 1 AND 35
    AND trans_status <> 'X'
    AND emp_no <> 9999
    GROUP BY `hour`";

$SalesByHourR = mysqli_query($db_slave, $SalesByHourQ);
while ($row = mysqli_fetch_array($SalesByHourR)) {
    $SalesByHour[$row[1]] = $row[0];
}


// Customer count by hour
$CountByHourQ .= "SELECT COUNT(upc) AS plu, HOUR(datetime) AS `hour` FROM is4c_log.trans_$year
    WHERE DATE(datetime) BETWEEN '$date1' and '$date2'
    AND upc = 'DISCOUNT'
    AND trans_status <> 'X'
    AND emp_no <> 9999
    GROUP BY `hour`";

$CountByHourR = mysqli_query($db_slave, $CountByHourQ);
while ($row = mysqli_fetch_array($CountByHourR)) {
    $CountByHour[$row[1]] = $row[0];
}

        echo '<table style="margin-left:auto;margin-right:auto;" frame="border" border="1"><tr>
            <th align="center">Hour</th>
            <th align="center">Total Sales</th>
            <th align="center">Customer Count</th>
            <th align="center">% of Total Customers</th>
            <th align="center">% of Gross Sales</th>
            <th align="center">Average Bag</th></tr>';

        for ($i = 8; $i <= 23; $i++) {
            if ($i <= 11) {$suffix = 'AM'; $curi = $i; $nexti = $i + 1;}
            elseif ($i == 12) {$suffix = 'PM'; $curi = 'Noon'; $nexti = 1;}
            elseif ($i == 23) {$suffix = NULL; $curi = $i -12; $nexti = 'Midnight';}
            else {$suffix = 'PM'; $curi = $i - 12; $nexti = $curi + 1;}
            if ($nexti == 12 && $i != 23) {$nexti = 'Noon'; $suffix = NULL;}
            echo "<tr>
            <td align='center'>$curi-$nexti$suffix</t>
            <td align='center'>\$" . number_format($SalesByHour[$i] / $datediff, 2) . "</td>
            <td align='center'>" . round($CountByHour[$i] / $datediff) . "</td>
            <td align='center'>" . number_format(($CountByHour[$i] / $Count) * 100, 2) . "%</td>
            <td align='center'>" . number_format(($SalesByHour[$i] / $Sales) * 100, 2) . "%</td>
            <td align='center'>";
            if ($CountByHour[$i] == 0) {
                echo 'N/A';
            } else {
                echo "$" . number_format($SalesByHour[$i] / $CountByHour[$i], 2) . "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";

        echo "<br /><br />
            <table cellpadding='5' cellspacing='2'><tr>
            <th align = 'left'><b>Total Sales: </b>$$Sales</th>
            <th align = 'left'><b>Average Sales: </b>$" . number_format($Sales / $datediff, 2) . "</th>
            <th align = 'left'><b>Customer Count: </b>" . round($Count / $datediff) . "</th>
            <th align = 'left'><b>Average Bag: </b>$" . number_format($Sales / $Count, 2) . "</th>
	    </tr>
            </table>";


	    echo '</div>' . $footer;
}
mysqli_close($db_slave);
?>
