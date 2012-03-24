<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

$months = array(1 => 'January','February','March','April','May','June','July','August','September','October','November','December');

if (!isset($_POST['submitted']) || (!isset($_POST["subType"]) && $_POST["submit"] != "submitSecond")) {
   $page_title = 'Fannie - Reports Module';
   $header = 'Board Membership Report';
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
      <button name="submit" value="submitFirst">Membership Report</button>
      <input type="hidden" name="submitted" value="true" />
  </div>
  </form>
  </body>';
  
   include ('../includes/footer.html');  
} elseif ($_POST["submit"] == "submitFirst") {
   @session_start();
   
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

   $retention = number_format(($active / ($inactive + $active + $refund)) * 100, 2);
   
   $memberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND memtype IN (1,2) AND staff IN (0,2,3) AND $where";
   $memberSalesR = mysqli_query($db_slave, $memberSalesQ);
   list($memberSales) = mysqli_fetch_row($memberSalesR);
   
   $miscMemberSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND memtype IN (1,2) AND staff IN (1,4,5) AND $where";
   $miscMemberSalesR = mysqli_query($db_slave, $miscMemberSalesQ);
   list($miscMemberSales) = mysqli_fetch_row($miscMemberSalesR);
   
   $totalSalesQ = "SELECT SUM(total) FROM is4c_log.$transtable WHERE department NOT IN (0, 40, 45) AND $where";
   $totalSalesR = mysqli_query($db_slave, $totalSalesQ);
   list($totalSales) = mysqli_fetch_row($totalSalesR);
   
   $percenttoMembers = number_format(($memberSales / $totalSales) * 100,2);
   $percenttoMMembers = number_format(($miscMemberSales / $totalSales) * 100, 2);
   
   $transCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT'";
   $transCountR = mysqli_query($db_slave, $transCountQ);
   list($transCount) = mysqli_fetch_row($transCountR);
   
   $memtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff IN (0,2,3)";
   $memtransCountR = mysqli_query($db_slave, $memtransCountQ);
   list($memtransCount) = mysqli_fetch_array($memtransCountR);
   
   $avg_memCount = round($memtransCount / $datediff, 0);
   
   $miscMemtransCountQ = "SELECT COUNT(total) FROM is4c_log.$transtable WHERE $where AND upc = 'DISCOUNT' AND memtype IN (1,2) AND staff IN (1,4,5)";
   $miscMemtransCountR = mysqli_query($db_slave, $miscMemtransCountQ);
   list($miscMemtransCount) = mysqli_fetch_row($miscMemtransCountR);
   
   $avg_miscMemCount = round($miscMemtransCount / $datediff, 0);
   
   // $memberBag = number_format($memberSales / $memtransCount,2);
   $memberpercent = number_format(($memtransCount / $transCount) * 100,2);
   
   // $miscMemBag = number_format($miscMemberSales / $miscMemtransCount, 2);
   $miscMemPercent = number_format( ($miscMemtransCount / $transCount) * 100, 2);
   
   $_SESSION['header'] = '<div style="text-align: center;">' . "
   <h2>Membership Report From " . date('M jS, Y', strtotime($date1)) . " to " . date('M jS, Y', strtotime($date2)) . "</h2>";
   $_SESSION['table'] = '<table style="margin-left:auto;margin-right:auto;" cellspacing="15" frame="border">
   <tr>
      <th colspan="2">Member Stats</th>
      <th colspan="2">Member Sales</th>
      <th colspan="2">Board/Staff Sales</th>
   </tr>' . 
   "<tr>
      <td>Total Active Members</td>
      <td>$active</td>
      <td>% of Sales to Members</td>
      <td>$percenttoMembers%</td>
      <td>% of Sales to Board Members/Staff</td>
      <td>$percenttoMMembers%</td>
   </tr>
   <tr>
      <td>Total Inactive Members</td>
      <td>$inactive</td>" .
      '<td colspan="4">&nbsp;</td>
   </tr>' .
   "<tr>
      <td>Member Retention Rate</td>
      <td>$retention%</td>
      <td>Member % of Customers</td>
      <td>$memberpercent%</td>
      <td>Board Member/Staff % of Customers</td>
      <td>$miscMemPercent%</td>
   </tr>" . 
   
'</table></div>';

$_SESSION['active'] = $active;
$_SESSION['inactive'] = $inactive;
$_SESSION['retention'] = $retention;
$_SESSION['salesToMembers'] = $percenttoMembers;
$_SESSION['salesToStaff'] = $percenttoMMembers;
$_SESSION['memberPercent'] = $memberpercent;
$_SESSION['staffPercent'] = $miscMemPercent;
$_SESSION['date1'] = date('F jS, Y', strtotime($date1));
$_SESSION['date2'] = date('F jS, Y', strtotime($date2));

echo $_SESSION['header'] . $_SESSION['table'];

printf('<br /><div style="text-align:center;"><form method="POST" action="%s">', $_SERVER["PHP_SELF"]);
echo '<textarea name="headText" cols="120" rows="5">Pre-Bullet Text</textarea><br />';

for ($i = 0; $i < 10; $i++) {
   printf('<div style="margin-left:auto;margin-right:auto;display:block;">Bullet #%s
          <input type="text" name="bullet[%s]" size="100" maxlength="150" /></div>', $i+1, $i);
}

echo '<textarea name="footText" cols="120" rows="5">Post-Bullet Text</textarea><br />
      <button name="submit" value="submitSecond">Generate PDF</button>
      <input type="hidden" name="submitted" value="true" />
      </form></div></body></html>';
      

} elseif ($_POST["submit"] == "submitSecond") {
   @session_start();
   require_once ('../src/fpdf/fpdf.php');
   define('FPDF_FONTPATH','font/');
   
   $pdf=new FPDF('P', 'mm', 'Letter');
   $pdf->SetMargins(6, 15);
   $pdf->SetAutoPageBreak('off',0);
   $pdf->AddPage('P');
   $pdf->SetFont('Times','B',18);
   
   $cellHeight = 8;
   $bigCellHeight = 10;
   
   // Page Header
   $pdf->SetXY(6,15);
   $pdf->Cell(0, 20, "Membership Report From {$_SESSION['date1']} to {$_SESSION['date2']}", 0, 1, 'C');
   
   // Table Header Row
   $pdf->SetFont('Times','B',16);
   $pdf->Cell(102, $bigCellHeight, "Member Sales", 0, 0, 'C');
   $pdf->Cell(102, $bigCellHeight, "Staff/Board Sales", 0, 1, 'C');
   
   // First Table Row
   $pdf->SetFont('Times','',14);
   $pdf->SetX(20);
   $pdf->Cell(60, $cellHeight, "% Sales to Members", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['salesToMembers'] . '%', 0, 0, 'R');
   $pdf->SetX(120);
   $pdf->Cell(60, $cellHeight, "% Sales to Board/Staff", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['salesToStaff'] . '%', 0, 1, 'R');
   
   // Second Table Row
   $pdf->SetFont('Times','',14);
   $pdf->SetX(20);
   $pdf->Cell(60, $cellHeight, "Member % of Customers", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['memberPercent'] . '%', 0, 0, 'R');
   $pdf->SetX(120);
   $pdf->Cell(60, $cellHeight, "Staff/Board % of Customers", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['staffPercent'] . '%', 0, 1, 'R');
   $pdf->Cell(0, $cellHeight, "\n", 0, 1, 'C');

   // Third Table Row
   $pdf->SetFont('Times','B',16);
   $pdf->Cell(0, $bigCellHeight, "Membership Stats", 0, 1, 'C');
   
   $pdf->SetFont('Times','',14);
   $pdf->SetX(65);
   $pdf->Cell(60, $cellHeight, "Total Active Members", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['active'], 0, 1, 'R');
   $pdf->SetX(65);
   $pdf->Cell(60, $cellHeight, "Total Inactive Members", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['inactive'], 0, 1, 'R');
   $pdf->SetX(65);
   $pdf->Cell(60, $cellHeight, "Member Retention Rate", 0, 0, 'L');
   $pdf->Cell(20, $cellHeight, $_SESSION['retention'] . '%', 0, 1, 'R');
   $pdf->Cell(0, $cellHeight, "\n", 0, 1, 'C');
   
   // Third Table Row
   $pdf->SetFont('Times','B',16);
   $pdf->Cell(0, $bigCellHeight, "Notes", 0, 1, 'C');
   
   $pdf->SetFont('Times','',14);
   $pdf->SetX(12);
   $pdf->MultiCell(190, $cellHeight, $_POST['headText'], 0, 'L');
   
   foreach ($_POST['bullet'] AS $b) {
      if ($b !== '') {
         $pdf->SetX(18);
         $pdf->SetFont('Times','',20);
         $pdf->Cell(8, $cellHeight, chr(149), 0, 0, 'L');
         
         $pdf->SetFont('Times','',14);
         $pdf->MultiCell(175, $cellHeight, $b, 0, 'L');
      }
   }
   $pdf->SetX(12);
   $pdf->MultiCell(190, $cellHeight, $_POST['footText'], 0, 'L');
   
   $pdf->Output();
   
}

mysqli_close($db_slave);
?>