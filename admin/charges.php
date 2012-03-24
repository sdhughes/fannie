<?php
$page_title = 'Fannie - Administration Module';
$header = 'House Charge Report';
include ('../includes/header.html');
/* 
print_r($_SERVER);
echo "post:";
print_r($_POST);
echo "<br / >";
*/
?>
<SCRIPT TYPE="text/javascript">
<!--
function popup(mylink, windowname)
{
if (! window.focus)return true;
var href;
if (typeof(mylink) == 'string')
   href=mylink;
else
   href=mylink.href;
window.open(href, windowname, 'width=400,height=300,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes');
return false;
}
//-->
</SCRIPT>

</head>
<body>

<?php
setlocale(LC_MONETARY, 'en_US');
require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

if (isset($_POST["Submit"])) {
	$query = mysqli_query($db_slave, "SELECT * FROM is4c_log.payperiods WHERE periodID = ". $_POST["period"]);
	$row = mysqli_fetch_array($query);
	$pay_start = $row["periodStart"];
	$pay_end = $row["periodEnd"];
} else {
	$query = mysqli_query($db_slave, "SELECT * FROM is4c_log.payperiods WHERE curdate() >= periodEnd ORDER BY periodID DESC LIMIT 1");
	$row = mysqli_fetch_array($query);
	$pay_start = $row["periodStart"];
	$pay_end = $row["periodEnd"];
}

$year_start = substr($pay_start, 0, 4);
$year_end = substr($pay_end, 0, 4);

//pull the House charges for each staff member
if ($year_start == $year_end) {

    $transtable = 'trans_' . $year_start;

    $query = "SELECT d.card_no, ROUND(SUM(d.total),2) as charges
            FROM is4c_log.$transtable AS d
            WHERE d.datetime BETWEEN '$pay_start' AND '$pay_end'
            AND d.trans_subtype = 'MI'
            AND d.emp_no <> 9999 AND d.trans_status <> 'X'
            AND d.card_no NOT IN (9999, 99999)
            GROUP BY d.card_no";
} else {
    $query = "SELECT card_no, SUM(charges) FROM (";

    for ($year = $year_start; $year <= $year_end; $year++) {
        $query .= "SELECT card_no, ROUND(SUM(total),2) as charges
            FROM is4c_log.trans_$year
            WHERE datetime BETWEEN '$pay_start' AND '$pay_end'
            AND trans_subtype = 'MI'
            AND emp_no <> 9999 AND trans_status <> 'X'
            AND card_no NOT IN (9999, 99999)
            GROUP BY card_no";

        if ($year == $year_end)
            $query .= ") AS yearSpan GROUP BY card_no";
        else
            $query .= " UNION ALL ";
    }

}


//pull the Staff charges
if ($year_start == $year_end) {

    $transtable = 'trans_' . $year_start;

    $staffQuery = "SELECT d.card_no, ROUND(SUM(d.total),2) as charges
            FROM is4c_log.$transtable AS d
            WHERE d.datetime BETWEEN '$pay_start' AND '$pay_end'
            AND d.trans_subtype = 'MI'
            AND d.emp_no <> 9999 AND d.trans_status <> 'X'
            AND d.card_no IN (9999, 99999)
            GROUP BY d.card_no";
} else {
    $staffQuery = "SELECT card_no, SUM(charges) FROM (";

    for ($year = $year_start; $year <= $year_end; $year++) {
        $staffQuery .= "SELECT card_no, ROUND(SUM(total),2) as charges
            FROM is4c_log.trans_$year
            WHERE datetime BETWEEN '$pay_start' AND '$pay_end'
            AND trans_subtype = 'MI'
            AND emp_no <> 9999 AND trans_status <> 'X'
            AND card_no IN (9999, 99999)
            GROUP BY card_no";

        if ($year == $year_end)
            $staffQuery .= ") AS yearSpan";
        else
            $staffQuery .= " UNION ALL ";
    }

}

$queryR = mysqli_query($db_slave, $query);

if (!$queryR) echo "<p>$query</p><p>" . mysqli_error($db_slave) . "</p>";
$charge = array();
while ($queryRow = mysqli_fetch_array($queryR, MYSQLI_NUM)) {
    $newQ = "SELECT FirstName, LastName, emp_no
        FROM is4c_op.employees
        WHERE card_no = $queryRow[0]
        AND EmpActive = 1";
    $newR = mysqli_query($db_slave, $newQ);
    list($first, $last, $emp_no) = mysqli_fetch_array($newR, MYSQLI_NUM);
    $charge[$emp_no] = array('CardNo' => $queryRow[0], 'total' => $queryRow[1], 'last' => $last, 'first' => $first);
}
//ksort($charge);
echo "query = " . $query . "<br />";
//echo "staffQuery = " . $staffQuery . "<br />";

echo "<center><h2>Previous Pay Period</h2></center> \n";
echo "<center><h3><span id='date1'>".strftime('%D', strtotime($pay_start))."</span> through <span id='date2'>".strftime('%D', strtotime($pay_end))."</span></h3></center> \n";
echo "<table border=0 width=95% cellspacing=0 cellpadding=5 align=center> \n";
//echo "<th>Card No<th>Last Name<th>First Name<th>Type<th>Charges \n";
// Table header.
echo '<thead><tr>
	<th>Card No</th>
	<th>Last Name</th>
	<th>First Name</th>
	<th>Charges</th>
	<th>&nbsp;</th>
	</tr></thead><tbody>';
$bg = '#eeeeee';
// while($query = mysql_fetch_row($queryR)){
$count = 0;
$total = 0;

foreach ($charge as $emp_no => $v) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo "<tr bgcolor='$bg'>
                <td>{$v['CardNo']}</td>
                <td>{$v['last']}</td>
                <td>{$v['first']}</td>
                <td align=\"right\">$" . number_format($v['total'],2) . "</td>";
	echo '<td align=right><a href="chgdetail.php?cn='.$v['CardNo'].'&ps='.$pay_start.'&pe='.$pay_end.'" onClick="return popup(this, \'chgdetail\')">detail</a></tr>';
	++$count;
	$total += $v['total'];
}
//query the db about store charges
$staffQueryR = mysqli_query($db_slave, $staffQuery);
$row = mysqli_fetch_array($staffQueryR, MYSQLI_NUM);

$storeNumber = $row[0];
$storeChargeTotal =  $row[1];
$date1 = substr($pay_start,0,10);
$date2 = substr($pay_end,0,10);

printf('</tbody><tfoot>
    <tr align="center" style="font-weight: bold;">
	<td colspan="4">%u Employees House Charged For A Total of $%.2f</td>
    </tr>
    <tr align="center" style="font-weight: bold;">
	<td colspan="4" id="chargeDetail">
    <form method="post" action="../reports/storeCharges.php">
    <input type="hidden" name="date1" value="%s" />
    <input type="hidden" name="date2" value="%s" /> 
    Employees used #%u to Store Charge For A Total of $%.2f
    (view charges<input type="submit" name="submitted" value="submit" />)
  </form> 
    </td>
    </tr>
    </tfoot></table>', $count, -1 * $total, $date1,$date2, $storeNumber,-1 * $storeChargeTotal);



//fetch the data and print out the total staff charges
echo "<div id='storeCharges'>";
echo "</div>";

//seriously? just use a <hr>.....
echo "<table width=100% border=0><tr><td colspan='3' height='1' bgcolor='cccccc'></td></tr></table>";

$query = "SELECT * FROM is4c_log.payperiods WHERE periodEnd <= curdate() ORDER BY periodEnd DESC LIMIT 45";
$results = mysqli_query($db_slave, $query) or
	die("<li>errorno=".mysqli_errno($db_slave)
		."<li>error=" .mysqli_error($db_slave)
		."<li>query=".$query);

//echo "<div id='box'>";
echo "<br /><br /><center><table border=0 cellpadding=0 cellspacing=0><tr><td align=center> \n";
echo "<h3>Select another pay period</h3> \n";
echo "</td></tr><tr><td align=center> \n";
echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";
echo "<select name=period id=period> \n";
while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
	echo "<option value=" .$row["periodID"] . ">";
	echo strftime('%D', strtotime($row["periodStart"])). " --> " .strftime('%D', strtotime($row["periodEnd"]));
  	echo "</option> \n";
}
echo "</td></tr><tr><td align=center><input type=submit name=Submit value=Submit></form></td></tr></table></center>";
include('../includes/footer.html');
?>
