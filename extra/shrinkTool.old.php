<?php
require_once('/pos/fannie/includes/mysqli_connect.php');
require_once('../includes/common.php');

$header = "Shrink Tool";
$page_title = "Fannie - Backoffice Shrink Tool";
include('../includes/header.html');
?>

  <link rel="STYLESHEET" type="text/css" href="../includes/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <!--<script language='javascript' type="text/javascript" src="../includes/javascript/myquery.js"></script> -->
    <script language='javascript' type="text/javascript" src="../includes/javascript/shrinkTool.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/flot/jquery.flot.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script language='javascript' type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){            
                $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd'});
        });
    </script>
<div id='shrinkHeader'><div class='shrinkHeader_sub'> 	<form action='shrinkTool.php' method='post'>

		<table>
			<tr>
				<td><label>Name:</label><td>
		<?php

		makeEmployeeSelector(); //create the select boxes

		?></td>
			</tr>
			<tr>
<!--		<label >Select Date: </label>
		<input type='text' value='' name='date1' class='datepick'><br />-->
				<td><label >PLU/UPC: </label></td>
				<td><input type='text' value='' name='shrinkUPC'></td>
			</tr>
			<tr>
				<td><label >Quantity:</label></td>
				<td><input type='text' value='' name='shrinkQty'></td>
			</tr>
			<tr>
				<td><label >Reason for shrink?:</label></td>
                            	<td>
					<SELECT name='selectlist' onblur='document.selectform.selectlist.focus();' >
                                	<OPTION value="0">
                                	<OPTION value="1">1. Half-off
	                                <OPTION value="2">2. Damaged Product
        	                        <OPTION value="3">3. Expired (Past-Date)
                	                <OPTION value="4">4. Spoiled Before Exp. Date
                        	        <OPTION value="5">5. Customer Dissatisfied
                               		<OPTION value="6">6. Abandoned
                                	<OPTION value="7">7. Other
                            		<OPTION value="8">8. Donation
                                	<OPTION value="9">9. Samples
					</SELECT>
				</td>
			</tr>
		</table>
		<input type='submit' value='submit' name='submit'><input type='reset' value='reset' name='reset'>
	</form></div>
	<div class='shrinkHeader_sub'>
		<p class='directions'>
			- Input values and press 'submit' to shrink.<br />- Any deleted line will appear <span class='red'> red</span>.<br />- Refresh for clean list. 
		</p>
	</div>
	</div>
<?php
global $db_master;

mysqli_select_db($db_master, 'is4c_log') or die('Could not select DB:' . mysqli_error($db_master));

	//if submitted
	if (isset($_POST['submit']) && isset($_POST['shrinkUPC'])) {

		//$query = 'INSERT INTO fannie.shrinklog () VALUES ()';


if (isset($_POST["selectlist"]) && $_POST["selectlist"] != 0) {
    $shrink_task = strtoupper(trim($_POST["selectlist"]));
} else {
    $shrink_task = NULL;
}

    $upc = $_POST['shrinkUPC'];
    if ($upc) {

    }


    $query = "select * from is4c_op.products where upc = $upc AND inUse = 1";

    $result = mysqli_query($db_master, $query) or die('Could not query: ' . mysqli_error($db_master));
    $num_rows = mysqli_num_rows($result);
    $row = mysqli_fetch_array($result);


    //sdh- altered so it just takes the normal price and not the sale price
    //$price = (isset($row["special_price"]) && $row["special_price"] > 0) ? $row["special_price"] : $row["normal_price"];
    $price = $row["normal_price"];
    $qty = (isset($_POST["shrinkQty"])) ? $_POST["shrinkQty"] : 1;
//print_r($row);
//echo " emp: " . $_POST['emp_selector'] ;
//echo "entered upc: $upc";
//echo "price: " . $row['normal_price'];


    
    $shrinkQ = "INSERT INTO is4c_log.shrinkLog (datetime, emp_no, department, price, quantity, reason, upc) VALUES (now(), {$_POST['emp_selector']}, {$row['department']}, $price, $qty, $shrink_task, {$_POST['shrinkUPC']})";
    $shrinkR = mysqli_query($db_master, $shrinkQ);


	$result = mysqli_query($db_master, $query) or die(mysqli_error($db_master));
		
	if (!$result) {
		echo "Error shrinking, please try again.";
	} else {
		$total = $qty * $price;
		echo "<h2>**You just shrunk $qty of $upc for a total cost of $$total.**</h2>";
	}
}//end if isset(submit)	

//$startDate = date("Y-m-d", strtotime('-3 days') );  
	$currDate = date('Y-m-d');
	if (!isset($_POST['startDate'])) $startDate = $currDate;
	else $startDate = $_POST['startDate'];

	$showQuery = "SELECT s.datetime, 
                         e.FirstName, 
                         s.upc, 
                         p.description, 
                         d.dept_name, 
                         s.quantity, 
                         s.price, 
                         sr.shrinkReason 
                      FROM shrinkLog as s 
                         INNER JOIN is4c_op.employees as e 
                         INNER JOIN is4c_op.products as p 
                         INNER JOIN is4c_op.departments as d 
                         INNER JOIN shrinkReasons as sr 
                      ON 
                         s.emp_no = e.emp_no 
                         AND s.upc = p.upc 
                         AND s.department = d.dept_no 
                         AND s.reason = sr.shrinkID 
                      WHERE 
                         date(datetime) BETWEEN '$startDate' 
                         AND '$currDate' 
                      ORDER BY datetime DESC;";

	$result = mysqli_query($db_master, $showQuery) or die('Query Error: ' . mysqli_error($db_master));

	if ($startDate == $currDate){
		echo "<h3>Items Shrunk Today ($currDate): </h3>";

	} else {
		echo "<h3>Items Shrunk Between $startDate and $currDate: </h3>";
	}
		echo "
	<form method='post' action='shrinkTool.php'>
		<span>See shrink from other dates?</span><input type='text' value='' name='startDate' class='datepick' title='Choose an earlier start day!'/>
		<input type='submit' name='submit' value='submit' />
	</form>";

echo "<form action='editShrinkLog.php' method=post>
<table id='shrinkTable'>
<tr>
	<th>Timestamp</th><th>Who?</th><th>UPC</th><th>Description</th><th>Dept</th><th>Qty</th><th>Price</th><th>Total</th><th>Reason</th><th>Unshrink?</th>
</tr>
";
		while ($row = mysqli_fetch_row($result)) {
			

		$total = $row[5] * $row[6];
	/*		echo "<tr><td class='timestamp'>$row[0]</td>
				<td class='empno'>$row[1]</td>
				<td class='UPC'>$row[2]</td>
				<td class='dept'>$row[3]</td>
				<td class='quantity'>$row[4]</td>
				<td class='price'>$$row[5]</td>
				<td class='total'>$$total</td>
				<td class='reason'>$row[6]</td>
				<td class='delete'><input type='submit' name='delete' value='delete' class='deletebutton' /></td> 
</tr>";*/
		$output = sprintf("<tr><td class='timestamp'>%s</td>
				<td class='empno'>%s</td>
				<td class='UPC'>%s</td>
				<td class='description'>%s</td>
				<td class='dept'>%s</td>
				<td class='quantity'>%s</td>
				<td class='price'>$%s</td>
				<td class='total'>$%.2f</td>
				<td class='reason'>%s</td>
				<td class='delete'><input type='submit' name='delete' value='delete' class='deletebutton' /></td> 
</tr>",$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$total,$row[7]);
			echo $output;
		}
		echo "</table>";

	//echo "<input type='submit' value='submit' />";
	echo "</form>";
?>
<?php

include('../includes/footer.html');

?>
