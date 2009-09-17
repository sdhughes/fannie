<?php
if (isset($_POST['submitted'])) {
	require_once('../includes/mysqli_connect.php');
	mysqli_select_db($db_slave, 'is4c_op');
	?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<title>Department Product List</title>
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/themes/blue/style.css" />
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/addons/pager/jquery.tablesorter.pager.css" />
        <style rel="STYLESHEET" type="text/css">
            tr.alt td {
                background: #ecf6fc !important;
            }
            tr.over td {
                background: #bcd4ec !important;
            }
        </style>
        <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
        <script type="text/javascript" src="../includes/javascript/jquery.tablesorter.js"></script>
        <script type="text/javascript" src="../includes/javascript/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src="../includes/javascript/jquery.metadata.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $(".tablesorter")
                    .tablesorter({widthFixed: false, debug: false, widgets:['zebra']})
                $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
            });
        </script>
        </head>
        <body>
        <?php
        
	
	foreach ($_POST AS $key => $value) {
            $$key = $value;
	}	
	
	$today = date("F d, Y");	
	
	if (isset($allDepts)) {
		$deptArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,40";
		$arrayName = "ALL DEPARTMENTS";
	} else {
		if (isset($_POST['dept'])) {$deptArray = implode(",",$_POST['dept']);}
		elseif (isset($_GET['dept'])) {$deptArray = $_GET['dept'];}
		$arrayName = $deptArray;
	}
	
	echo "<p>Report sorted by " . $order_name . " on <br />" . $today . "<br />Department range: " . $arrayName . "</p>";
	if ($inUse==1) {$inUseQ = 'AND inUse = 1';} else {$inUseQ = '';}
	if (isset($deptDetails)) {
		$query = "SELECT
                        CASE WHEN p.upc <= 999 THEN SUBSTR(p.upc, 11, 3) WHEN p.upc <= 9999 THEN SUBSTR(p.upc, 10, 4) ELSE p.upc END AS UPC,
                        p.description, ROUND(p.normal_price, 2), d.dept_name, s.subdept_name, p.foodstamp, p.scale, p.inuse, ROUND(p.special_price, 2)
			FROM products AS p INNER JOIN subdepts AS s ON s.subdept_no = p.subdept INNER JOIN departments as d ON d.dept_no=p.department
			WHERE p.department IN ($deptArray)
			$inUseQ
                        AND discounttype <> 3
			ORDER BY CAST(UPC AS SIGNED), p.subdept";
		$table_header = '<th>Department</th>
				<th>Subdepartment</th>';
		$saleRow = 8;
	} elseif (!isset($deptDetails)) {
		$query = "SELECT
                        CASE WHEN p.upc <= 999 THEN SUBSTR(p.upc, 11, 3) WHEN p.upc <= 9999 THEN SUBSTR(p.upc, 10, 4) ELSE p.upc END AS UPC,
                        p.description, ROUND(p.normal_price, 2), p.foodstamp, p.scale, p.inuse, ROUND(p.special_price, 2)
			FROM products AS p
			WHERE p.department IN ($deptArray)
			$inUseQ
                        AND discounttype <> 3
			ORDER BY CAST(UPC AS SIGNED), p.subdept";
		$table_header = '';
		$saleRow = 6;
	}
        
	$result = mysqli_query($db_slave, $query);
	
	// Table header.
	echo '<table border="1" class="tablesorter" cellspacing="3" cellpadding="3">
	<thead>
            <tr>
                <th>UPC</th>
                <th>Description</th>
                <th>Current Price</th>'
                . $table_header .
                '<th>Foodstampable</th>
                <th>Scaled</th>
                <th>In Use</th>
                <th>Sale Price</th>
           </tr>
        </thead>
        <tbody>';
	
	// Fetch and print all the records.
	while ($row = mysqli_fetch_array ($result, MYSQLI_NUM)) {
		echo '<tr>';
		for ($i=0; $i<=$saleRow; $i++) {
		   if (is_numeric($row[$i])) {
			if ($row[$i] == 0) {
				if ($i == $saleRow) {
					$row[$i] = 'Not on Sale';}
				else {$row[$i] = 'No';}
			} elseif ($row[$i] == 1) {$row[$i] = 'Yes';}
		   } else {$row[$i] = $row[$i];}
		   if ($i == 0) {echo '<td><a href="/item/itemMaint.php?submitted=search&upc=' . $row[$i] . '">' . $row[$i] . '</a></td>';
		   } else {echo '<td>' . $row[$i] . '</td>';}
		}
		echo '</tr>' . "\n";
	}
	
	echo '</tbody></table></body></html>';
	
	//
	// PHP INPUT DEBUG SCRIPT  -- very helpful!
	//
	/*
	function debug_p($var, $title) 
	{
	    print "<p>$title</p><pre>";
	    print_r($var);
	    print "</pre>";
	}  
	
	debug_p($_REQUEST, "all the data coming in");
	*/

} elseif (!isset($_POST['submitted'])) {
	$page_title = 'Fannie - Reports Module';
	$header = 'Product List';
	
	include ('../includes/header.html');

	echo '<form method = "post" action="product_list.php" target="_blank">
		<table border="0" cellspacing="3" cellpadding="5" align="center">
			<tr> 
		    <th colspan="2" align="center"> <p><b>Select Department</b></p></th>
			</tr>
			<tr>
				<td><font size="-1"><p>
					<input type="checkbox" value=1 name="allDepts"><b>All Departments</b><br>
					<input type="checkbox" name="dept[]" value="1">Grocery<br>
					<input type="checkbox" name="dept[]" value="2">Bulk<br>
					<input type="checkbox" name="dept[]" value="3">Perishable<br>
					<input type="checkbox" name="dept[]" value="4">Dairy<br>
					<input type="checkbox" name="dept[]" value="7">Cheese<br>
					<input type="checkbox" name="dept[]" value="15">Deli<br>
					<input type="checkbox" name="dept[]" value="16">Bread & Juice<br>
					<input type="checkbox" name="dept[]" value="14">Beer<br>
					<input type="checkbox" name="dept[]" value="5">Wine<br>
					</p></font>
				</td>
				<td><font size="-1"><p>
					<input type="checkbox" name="dept[]" value="8">Produce<br>
					<input type="checkbox" name="dept[]" value="6">Frozen<br>
					<input type="checkbox" name="dept[]" value="12">NF-Supplements<br>
					<input type="checkbox" name="dept[]" value="11">NF-Personal Care<br>
					<input type="checkbox" name="dept[]" value="10">NF-General<br>
					<input type="checkbox" name="dept[]" value="9">Bulk Herbs<br>
					<input type="checkbox" name="dept[]" value="13">NF-Pet<br>
					<input type="checkbox" name="dept[]" value="40">Tri-Met<br />
					<input type="checkbox" name="dept[]" value="18">Marketing
					</p></font>
				</td>
			</tr>
		<tr><td><font size="-1"><input type="checkbox" name="inUse" value=1><b>Filter PLUs that aren\'t In Use?</b></font><br /></td></tr>
		<tr><!--<td><font size="-1">Sort By: <select name="order">
		<option value=upca>UPC</option>
		<option value=dpa>Department</option>
		<option value=sda>Subdepartment</option>
		</select></td>-->
					
				<td><p><b><input type="checkbox" value="1" name="deptDetails" CHECKED />
                                Include department details</b></p></td>
                            </tr>
		
	
			<tr> 
				<td><input type=submit name=submit value="Submit"> </td>
				<td><input type=reset name=reset value="Start Over"> </td>
			<input type="hidden" name="submitted" value="TRUE">
				<td>&nbsp;</td>
			</tr>
		</table>
	</form>';
	include ('../includes/footer.html');
}

?>
