<?php
require_once('/pos/fannie/includes/mysqli_connect.php');
require_once('../includes/common.php');

$page_title = "Fannie - Backoffice Shrink Tool";
$header = "Shrink Tool";
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

<div id='shrinkContent'>
	<div id='shrinkHeader'>
		<div class='shrinkHeader_sub'> 	
			<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post' autocomplete='off' >

				<table>
					<tr>
						<td><label>Enter Employee #:</label><td>
			<?php

		//makeEmployeeSelector(); //create the select boxes

		?>
						<input type='text' name='emp_no' value='' autocomplete='off' />
        
					</td>
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
                                	<OPTION value="10">10. Stolen
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

        $emp_no =  $_POST['emp_no'];

        $empQ = "SELECT COUNT(*) FROM is4c_op.employees WHERE emp_no = $emp_no and EmpActive = 1";

        $empR = mysqli_query($db_master, $empQ) or die("Query Error<br /> $empQ<br />" . mysqli_error($db_master));

        $empRow = mysqli_fetch_row($empR);

        $emp_no_count = $empRow[0];

        if ($emp_no_count == 0) {

            echo "<h2 class='red thinborder'>Employee not recognized. Please try again.</h2>";

        } elseif ($shrink_task == 0) {

            echo "<h2 class='red thinborder'>Please select reason for shrinking.</h2>";

        } else {
    
            $shrinkQ = "INSERT INTO is4c_log.shrinkLog (datetime, emp_no, department, price, quantity, reason, upc) VALUES (now(), {$_POST['emp_no']}, {$row['department']}, $price, $qty, $shrink_task, {$_POST['shrinkUPC']})";

            $shrinkR = mysqli_query($db_master, $shrinkQ) or die(mysqli_error($db_master));
            //echo $shrinkQ . "<br />";
            if (!$shrinkR) {
                echo "Error shrinking, please try again.";
            } else {
                $total = $qty * $price;
                sprintf("<h2>**You just shrunk %u of %s for a total cost of $%.2f.**</h2>", $qty, $upc,$total);
            }
        }

//print_r($_POST);


    }//end if isset(submit)	

    //Now print out all items that have been shrunk.

    //$startDate = date("Y-m-d", strtotime('-3 days') );  
    $currDate = date('Y-m-d');

    //if dates not set, use current date
    if (!isset($_POST['startDate'])) $startDate = $currDate;
	else $startDate = $_POST['startDate'];
    if (!isset($_POST['endDate'])) $endDate = $currDate;
	else $endDate = $_POST['endDate'];

	$where = "";

	if ((isset($_POST['dept'])) && (!empty($_POST['dept']))) {

		$deptArray = $_POST['dept'];
		$deptString = implode(",",$deptArray);	
		$where = " AND s.department IN ($deptString) ";
	}


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
                         AND '$endDate' 
			$where
                      ORDER BY datetime ASC;";

	$result = mysqli_query($db_master, $showQuery) or die('Query Error: ' . mysqli_error($db_master));

		echo "<form method='post' action='shrinkTool.php'>";

		echo "<div align='left'>Filter by dept:";
			dept_picker('dept_tile');
		echo "</div>";
		echo "<span>See shrink from other dates?</span>
			<input type='text' value='' name='startDate' class='datepick' title='Choose an earlier start date!'/>
			<input type='text' value='' name='endDate' class='datepick' title='Choose an different end date! Blank selects current date.'/>
		<input type='submit' name='submit' value='submit' />
	</form></div>";

	if ($startDate == $currDate){
		echo "<h3>Items Shrunk Today ($currDate): </h3>";

	} elseif ($startDate == $endDate) {
		echo "<h3>Items Shrunk on $startDate: </h3>";
	} else {
		echo "<h3>Items Shrunk Between $startDate and $endDate: </h3>";
	}

//echo $showQuery . "<br />";
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
