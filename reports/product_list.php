<?php

/*
 * Init Vars
 */

//Set the base path to something writable 
$base_path = '/tmp';

//if (isset($_POST['submit'])) print_r($_POST);
//if (isset($_GET['submit'])) print_r($_GET);

if (isset($_POST['submitted'])) {
	require_once('../includes/mysqli_connect.php');
	include_once('../includes/dept_picker_generator.php');

	mysqli_select_db($db_slave, 'is4c_op');
	
	foreach ($_POST AS $key => $value) {
            $$key = $value;
	}	
	
	$today = date("F d, Y");	
	
	if (!isset($_POST['dept'])) {
		$deptArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,40";
		$arrayName = "ALL DEPARTMENTS";
	} else {
		if (isset($_POST['dept'])) {$deptArray = implode(",",$_POST['dept']);}
		elseif (isset($_GET['dept'])) {$deptArray = $_GET['dept'];}
		$arrayName = $deptArray;
	}
	
	if (isset($inUse) && $inUse==1) {$inUseQ = 'AND inUse = 1';} else {$inUseQ = '';}

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
       
    if (isset($_POST['outFile'])) {
        $date = date('Y-m-d-H-i-s');
        $fileName = "$base_path/product_list_$date.tsv";   
        $query .= " INTO OUTFILE '$fileName'";
    } 
	$result = mysqli_query($db_slave, $query) or die("Query Error: " . mysqli_error($db_slave));

   
    if (isset($_POST['outFile'])) {
        $page_title = "Fannie - Reports Module";
        $header = "Product List";
        include("../includes/header.html");
        echo "<h2>Product list has been saved as <span>" . $fileName . "'>$fileName</span></h2><p class='directions'> Please, verify to be sure it was created properly.</p>";
        include("../includes/footer.html");

    } else {
    // Create a simpler page than just creating the header/footer page

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
        <script type="text/javascript" src="../includes/javascript/jquery.metadata.js"></script>
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
        echo "<p> Department range: " . $arrayName . "</p>";

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
        
        echo '</tbody></table>';
    }//end else on-screen list, not printed.

    echo '</body></html>';
	
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
     <p id="select_department">Select Department</p>
                    <!--<td><font size="-1">Sort By: <select name="order">
                    <option value=upca>UPC</option>
                    <option value=dpa>Department</option>
                    <option value=sda>Subdepartment</option>
                    </select></td>-->';
    include_once("../includes/mysqli_connect.php");
    include_once("../includes/dept_picker_generator.php");
    echo "<div id=\"dept_picker\">
	<div><input type=\"checkbox\" value=1 name=\"allDepts\"><b>All Departments</b></div>";
        dept_picker('dept_tile');
    echo '</div>';
 
    echo '<p><b><input type="checkbox" value="1" id="deptDetails" name="deptDetails" CHECKED />
                                    <label for="deptDetails">Include department details</b>&nbsp;&nbsp;&nbsp;</label>
        <input type="checkbox" name="inUse" id="inUse" value=1><label for="inUse" name="filter_label"><b>Filter PLUs that aren\'t In Use?</b></label></font><br />
        <input type="checkbox" name="outFile" id="outFile" value=0><label for="outFile" ><b>Dump Data to directory: (' . $base_path . ')? NOTE: nothing will print on the screen!</b></label></font><br />
        <input type=submit name=submit value="Submit">
                                    <input type=reset name=reset value="Start Over">
                            <input type="hidden" name="submitted" value="TRUE">
                                    
        </form>';
	include ('../includes/footer.html');
}

?>
