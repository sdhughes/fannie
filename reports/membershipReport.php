<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

$months = array(1 => 'January','February','March','April','May','June','July','August','September','October','November','December');

if (!isset($_POST['submitted'])) {
   $page_title = 'Fannie - Reports Module';
   $header = 'Membership Report';
   include ('../includes/header.html');
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
    </script>";

echo '
      <form method="post" action="membershipReport.php" target="_blank">
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
         <tr><td colspan="5">&nbsp;</td></tr>
         <tr>
            <th>&nbsp;</th>
            <th colspan="2">Start Date</th>
            <th colspan="2">End Date</th>
         </tr>
         <tr>
            <td><input type="radio" id="dates" name="subType" value="dates" /></td>
            <td align="center" colspan="2"><input type="text" size="10" name="date1" class="datepick" /></td>
            <td align="center" colspan="2"><input type="text" size="10" name="date2" class="datepick" /></td>
         </tr>
      </table>
      <br />
      <button name="mainType" value="membership">Membership Report</button>
      <button name="mainType" value="equity">Equity Report</button>
      <input type="hidden" name="submitted" value="true" />
  </div>
  </form>
  </body>';

   include ('../includes/footer.html');
} else {
   echo '<body>';

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

      case 'dates':
         $date1 = $_POST['date1'];
         $date2 = $_POST['date2'];

         $transtable = 'trans_' . substr($date1, 0, 4);

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

   if ($_POST['mainType'] == 'membership') {

      $customerCountQ = "SELECT COUNT(upc) FROM is4c_log.$transtable WHERE upc='DISCOUNT' AND $where";
      $customerCountR = mysqli_query($db_slave, $customerCountQ);
      $customerCountRow = mysqli_fetch_row($customerCountR);
      $customerCount = $customerCountRow[0];

      $customerTotalQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND $where";
      $customerTotalR = mysqli_query($db_slave, $customerTotalQ);
      $customerTotalRow = mysqli_fetch_row($customerTotalR);
      $customerTotal = $customerTotalRow[0];

      $avg_bag = number_format(($customerTotal / $customerCount), 2);
      $avg_count = round($customerCount / $datediff, 0);

      $memTypesQ = "SELECT COUNT(CardNo), memdesc, m.memtype
	FROM is4c_op.custdata AS c
	    INNER JOIN is4c_op.memtype AS m ON (c.memtype = m.memtype)
	WHERE c.memtype BETWEEN 1 AND 5
	    AND c.personnum = 1
	GROUP BY m.Memtype
	ORDER BY m.memtype ASC";

      $memTypesR = mysqli_query($db_slave, $memTypesQ);
      $memTypes = array();

      while (list($count, $memDesc, $memNo) = mysqli_fetch_row($memTypesR)) {
	$memTypes[$memNo]['count'] = $count;
	$memTypes[$memNo]['desc'] = ucfirst(strtolower($memDesc));
      }

      $memberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND memtype IN (1,2) AND staff = 0 AND $where";
      $memberSalesR = mysqli_query($db_slave, $memberSalesQ);
      list($memberSales) = mysqli_fetch_row($memberSalesR);

      $wmemberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND memtype IN (1,2) AND staff IN (2,3) AND $where";
      $wmemberSalesR = mysqli_query($db_slave, $wmemberSalesQ);
      list($wmemberSales) = mysqli_fetch_row($wmemberSalesR);

      $miscMemberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND memtype IN (1,2) AND staff IN (1,4,5) AND $where";
      $miscMemberSalesR = mysqli_query($db_slave, $miscMemberSalesQ);
      list($miscMemberSales) = mysqli_fetch_row($miscMemberSalesR);

      $totalSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND $where";
      $totalSalesR = mysqli_query($db_slave, $totalSalesQ);
      list($totalSales) = mysqli_fetch_row($totalSalesR);

      $percenttoMembers = number_format(($memberSales / $totalSales) * 100,2);
      $percenttoWMembers = number_format(($wmemberSales / $totalSales) * 100,2);
      $percenttoMMembers = number_format(($miscMemberSales / $totalSales) * 100, 2);

      $transCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT'";
      $transCountR = mysqli_query($db_slave, $transCountQ);
      list($transCount) = mysqli_fetch_row($transCountR);

      $memtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff = 0";
      $memtransCountR = mysqli_query($db_slave, $memtransCountQ);
      list($memtransCount) = mysqli_fetch_array($memtransCountR);

      $avg_memCount = round($memtransCount / $datediff, 0);

      $wmemtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff IN (2,3)";
      $wmemtransCountR = mysqli_query($db_slave, $wmemtransCountQ);
      list($wmemtransCount) = mysqli_fetch_row($wmemtransCountR);

      $avg_wmemCount = round($wmemtransCount / $datediff, 0);

      $miscMemtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff IN (1,4,5)";
      $miscMemtransCountR = mysqli_query($db_slave, $miscMemtransCountQ);
      list($miscMemtransCount) = mysqli_fetch_row($miscMemtransCountR);

      $avg_miscMemCount = round($miscMemtransCount / $datediff, 0);

      $memberBag = number_format($memberSales / $memtransCount,2);
      $memberpercent = number_format(($memtransCount / $transCount) * 100,2);

      $wmemberBag = number_format($wmemberSales / $wmemtransCount,2);
      $wmemberpercent = number_format(($wmemtransCount / $transCount) * 100,2);

      $miscMemBag = number_format($miscMemberSales / $miscMemtransCount, 2);
      $miscMemPercent = number_format( ($miscMemtransCount / $transCount) * 100, 2);

      $newPaymentQ = "SELECT SUM(ItemQtty) AS QTY FROM is4c_log.$transtable
         WHERE $where AND card_no IN (3000, 99999) AND department = 45";

      $newPaymentR = mysqli_query($db_slave, $newPaymentQ);
      list($newPCount) = mysqli_fetch_array($newPaymentR);
      /*
      $PCount = 0;
      $PaymentQ = "SELECT SUM(ItemQtty) AS QTY, ABS(total) FROM is4c_log.$transtable
         WHERE $where AND card_no NOT IN (3000, 99999) AND department = 45
         GROUP BY ABS(total)
         HAVING QTY <> 0";
      $PaymentR = mysqli_query($db_slave, $PaymentQ);
      while (list($count, $amount) = mysqli_fetch_array($PaymentR)) {
         $Payment[$amount] = $count;
         $PCount += $count;
      }
      */

      $TCount = 0;
      $TPaymentQ = "SELECT SUM(ItemQtty) AS QTY, ABS(total) FROM is4c_log.$transtable
         WHERE $where AND card_no AND department = 45
         GROUP BY ABS(total)
         HAVING QTY <> 0";
      $TPaymentR = mysqli_query($db_slave, $TPaymentQ);
      while (list($count, $amount) = mysqli_fetch_array($TPaymentR)) {
         $TPayment[$amount] = $count;
         $TCount += $count;
      }

      $equityQ = "SELECT SUM(total), SUM(ItemQtty) FROM is4c_log.$transtable WHERE $where AND department = 45";
      $equityR = mysqli_query($db_slave, $equityQ);
      $equity = mysqli_fetch_row($equityR);

      $totalActive = $memTypes[1]['count'] + $memTypes[2]['count'];

      echo "<h2>Membership Report From " . date('M jS, Y', strtotime($date1)) . " to " . date('M jS, Y', strtotime($date2)) . "</h2>" .
      '<table cellspacing="15" frame="border">
      <tr>
         <th colspan="2">Member Types</th>
         <th colspan="2">Member Sales</th>
         <th colspan="2">Working Member/Sub Sales</th>
         <th colspan="2">Board/Staff Sales</th>
      </tr>' .
      "<tr>
         <td>Total {$memTypes[1]['desc']}</td>
         <td>{$memTypes[1]['count']}</td>
         <td>% of Sales to Members</td>
         <td>$percenttoMembers%</td>
         <td>% of Sales to Working Members/Subs</td>
         <td>$percenttoWMembers%</td>
         <td>% of Sales to Board Members/Staff</td>
         <td>$percenttoMMembers%</td>
      </tr>
      <tr>
         <td>Total {$memTypes[2]['desc']}</td>
         <td>{$memTypes[2]['count']}</td>
         <td>Member Average Bag</td>
         <td>$$memberBag</td>
         <td>Working Member/Sub Average Bag</td>
         <td>$$wmemberBag</td>
         <td>Board Member/Staff Average Bag</td>
         <td>$$miscMemBag</td>
      </tr>
      <tr>
         <td>Total {$memTypes[3]['desc']}</td>
         <td>{$memTypes[3]['count']}</td>
         <td>Member % of Customers</td>
         <td>$memberpercent%</td>
         <td>Working Member/Sub % of Customers</td>
         <td>$wmemberpercent%</td>
         <td>Board Member/Staff % of Customers</td>
         <td>$miscMemPercent%</td>
      </tr>
      <tr>
	 <td>Total {$memTypes[4]['desc']}</td>
	 <td>{$memTypes[4]['count']}</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
      </tr>
      <tr>
	 <td>Total {$memTypes[5]['desc']}</td>
	 <td>{$memTypes[5]['count']}</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
      </tr>
      <tr>
	 <td>Total Active Owners</td>
	 <td>$totalActive</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
	 <td>&nbsp;</td>
      </tr>" .

   '</table>
   <br />
   <table>
      <tr>
      <td><table cellspacing="3" cellpadding="3" frame="box">
            <thead><tr><th colspan="2">Member Payments</th></tr></thead>
            <tbody>';
      $fullthismonth = 0;
      foreach ($TPayment AS $amount => $count) {
         if (($amount > 0) && ($count > 0)) {echo "<tr><td>$" . number_format($amount,2) . "</td><td>$count</td></tr>";}
         if ($amount == 180) $fullthismonth += $count;
      }
      printf('</body><tfoot><tr><th>%s</th><th>%s</th></tr></tfoot>', 'Total', $TCount);
      printf('</table></td>
      <td>
      <table cellspacing="15" frame="border">
         <tr>
            <th colspan="4">Useful Info</th>
         </tr>
         <tr>
            <td>Average Customer Count</td>
            <td>%s</td>
            <td>Total Number of Payments</td>
            <td>%s</td>
         </tr>
         <tr>
            <td>Average Bag</td>
            <td>$%s</td>
            <td>Total Value of Payments</td>
            <td>$%s</td>
         </tr>
         <tr>
            <td>Average Member Count</td>
            <td>%s</td>
            <td>Member Retention Rate</td>
            <td>%s%%</td>
         </tr>
         <tr>
            <td>Average Working Member Count</td>
            <td>%s</td>
            <td colspan="2">&nbsp;</td>
         </tr>
      </table>
      </td></table>', $avg_count, "{$equity[1]} ($newPCount new)", $avg_bag, number_format($equity[0], 2), $avg_memCount,
	number_format(($memTypes[1]['count'] + $memTypes[2]['count'])/($memTypes[1]['count'] + $memTypes[2]['count'] + $memTypes[3]['count'] + $memTypes[5]['count'])*100,2), $avg_wmemCount);

   } elseif ($_POST['mainType'] == 'equity') {
      $where = "DATE(datetime) BETWEEN '$date1' AND '$date2' AND d.emp_no <> 9999 AND trans_status <> 'X'";

      $equityQ = "SELECT e.firstname AS 'Cashier', date(d.datetime) AS 'Date', d.card_no AS 'Card Number', ROUND(SUM(d.total),2) as Total
           FROM is4c_log.$transtable AS d JOIN is4c_op.employees AS e ON e.emp_no = d.emp_no
           WHERE $where
           AND d.department = 45
           GROUP BY DATE(datetime), register_no, d.emp_no, trans_no
           HAVING Total <> 0
           ORDER BY datetime";
      $equityR = mysqli_query($db_slave, $equityQ);

      if (!$equityR) echo "<p>Query: $equityQ</p><p>Error: " . mysqli_error($db_slave) . "</p>";

      echo '<table border="1" cellspacing="3" cellpadding="3">
         <tr><th colspan="6">Equity Report From ' . date('M jS, Y', strtotime($date1)) . " to " . date('M jS, Y', strtotime($date2)) . '</th></tr>
         <tr><th>Cashier</th><th>Date</th><th>Card Number</th><th>Assigned Number</th><th>Payment Amount</th><th>Entry Verified?</th></tr>';

      while ($row = mysqli_fetch_row($equityR)) {
         echo "<tr>
            <td>{$row[0]}</td>
            <td align=\"center\">{$row[1]}</td>
            <td align=\"center\">{$row[2]}</td>
            <td>&nbsp;</td>
            <td align=\"center\">$$row[3]</td>
            <td>&nbsp;</td>
         </tr>";
      }
      echo '</table>';

   }

}
mysqli_close($db_slave);
?>
