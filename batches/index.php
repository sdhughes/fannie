<?php 

//<script src="../src/CalendarControl.js"
//        language="javascript"></script>
//</head>

$_SESSION['batchID'] = 1;
$header = 'Batch Maintenance';
$page_title = 'Fannie - Batch Module';
include ('../includes/header.html');
?>
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
	<script type="text/javascript" src="../includes/javascript/batches.js"></script>
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
		    //$('.datepick').focus();
		});
	</script>
<?php

require_once ('../includes/mysqli_connect.php');
require_once ('../includes/common.php');
//original batch query, replaced by SDH 2011-05-16
//$batchListQ= "SELECT b.batchID,b.batchName,b.batchType,DATE(b.startDate),b.endDate,d.dept_name as department FROM batches as b INNER JOIN departments as d on b.department = d.dept_no ORDER BY b.batchID DESC";
if (isset($_POST['orderBy'])) {

    switch ($_POST['orderBy']) {

	case 'batch':
		$orderBy = 'b.batchID';
		break;
	case 'desc':
		$orderBy = 'b.batchName';
		break;
	case 'startDate':
		$orderBy = 'b.startDate';
		break;
	case 'endDate':
		$orderBy = 'b.endDate';
		break;
	case 'maintainer':
		$orderBy = 'maintainer';
		break;
	default: 
		$orderBy = 'b.batchID';
    }		

} else {

	$orderBy = 'b.startDate';
}

if (isset($_POST['abcOrder']) && $_POST['abcOrder'] == 'a->z') {
	$abcOrder = 'ASC';
} else {
	$abcOrder = 'DESC';
}

$batchListQ =  "SELECT b.batchID,b.batchName,b.batchType,DATE(b.startDate),b.endDate, e.FirstName as maintainer,b.deleted FROM batches as b LEFT JOIN employees as e ON b.maintainer = e.emp_no ORDER BY $orderBy $abcOrder";
mysqli_select_db($db_slave, 'is4c_op');

$batchListR = mysqli_query($db_slave, $batchListQ) or die ("Query error: " . mysqli_error($db_slave));

$maxBatchQ = "SELECT max(batchID) FROM batches";
$maxBatchR = mysqli_query($db_slave, $maxBatchQ) or die ("Query error: " . mysqli_error($db_slave));
$maxBatchW = mysqli_fetch_row($maxBatchR) or die ("Fetch error: " . mysqli_error($db_slave));
$newBatch = $maxBatchW[0] + 1;


if (!isset($_POST["showinactive"])) {$_POST["showinactive"] = "hide";}


//if (isset($_POST["delete"]) && is_array($_POST["deleteList"])) {print_r $_POST["deleteList"];}
//echo "<br/>$batchListQ<br/>";
        echo '<p align="center"><input id="toggleBatches" type=submit value="Show Inactive" /></p>';

echo '<form action="'. $_SERVER['PHP_SELF'] . '" method="POST">';


if ($_POST['showinactive'] == 'show') {
        echo '<p align="center"><input id=showinactive name=showinactive type=hidden value="hide" /></p>';
} else {
        echo '<p align="center"><input id=showinactive name=showinactive type=hidden value="show" /></p>';
}


//echo "<input type='button' id='inactive_toggle' value='hide' name='showinactive' />";
//Since the variables $orderBy and $abcOrder are unused, refill them with other values to display!
switch ($orderBy) {

	case 'b.batchID':
		$orderBy = 'Batch ID';
		break;

	case 'b.batchName':
		$orderBy = 'Description';
		break; 
	case 'b.startDate':
		$orderBy = 'Start Date';
		break;
	case 'b.endDate':
		$orderBy = 'End Date';
		break;
	case 'maintainer':
		$orderBy = 'Maintainer';
		break;
	default:
		$orderBy = 'oops! error...';

}
switch ($abcOrder) {

	case 'ASC':
		$abcOrder = 'First to Last';
		break;
	case 'DESC':
		$abcOrder = 'Last to First';
		break;
	default:
		$abcOrder = 'oops! Error...';

}



echo "<p align='center'>Currently sorted by (<span class='green strong' > $orderBy , $abcOrder </span>).&nbsp; &nbsp;Resort Batches by:";

//create the option box with the correct thing selected
switch ($orderBy) {

	case 'b.batchID':
		$orderBy = 'Batch ID';
echo "	<select name='orderBy'>
	<option value='batch' selected='selected'>Batch</option>
	<option value='desc'>Description</option>
	<option value='startDate'>Start Date</option>
	<option value='endDate'>End Date</option>
	<option value='maintainer'>Maintainer</option>
	</select>";
		break;

	case 'b.batchName':
		$orderBy = 'Description';
echo "	<select name='orderBy'>
	<option value='batch'>Batch</option>
	<option value='desc' selected='selected'>Description</option>
	<option value='startDate'>Start Date</option>
	<option value='endDate'>End Date</option>
	<option value='maintainer'>Maintainer</option>
	</select>";
		break;
	case 'b.startDate':
		$orderBy = 'Start Date';
echo "	<select name='orderBy'>
	<option value='batch'>Batch</option>
	<option value='desc'>Description</option>
	<option value='startDate' selected='selected'>Start Date</option>
	<option value='endDate'>End Date</option>
	<option value='maintainer' >Maintainer</option>
	</select>";
		break;
	case 'b.endDate':
		$orderBy = 'End Date';
echo "	<select name='orderBy'>
	<option value='batch'>Batch</option>
	<option value='desc'>Description</option>
	<option value='startDate'>Start Date</option>
	<option value='endDate' selected='selected'>End Date</option>
	<option value='maintainer'>Maintainer</option>
	</select>";
		break;
	case 'maintainer':
		$orderBy = 'Maintainer';
		echo "	<select name='orderBy'>
			<option value='batch'>Batch</option>
			<option value='desc'>Description</option>
			<option value='startDate'>Start Date</option>
			<option value='endDate'>End Date</option>
			<option value='maintainer' selected='selected'>Maintainer</option>
		</select>";
		break;
	default:
		$orderBy = 'oops! error...';
echo "	<select name='orderBy'>
	<option value='batch'>Batch</option>
	<option value='desc'>Description</option>
	<option value='startDate' selected='selected'>Start Date</option>
	<option value='endDate'>End Date</option>
	<option value='maintainer' >Maintainer</option>
	</select>";
}
if ($abcOrder == 'ASC') {

	echo "	<select name='abcOrder'>
		<option value='a->z' selected='selected'>A->Z</option>
		<option value='z->a'>Z->A</option>
	</select>";
}else {
	echo "	<select name='abcOrder'>
		<option value='a->z'>A->Z</option>
		<option value='z->a' selected='selected'>Z->A</option>
	</select>";
}

echo '<BUTTON name=resort type="submit" value="resort">Re-sort Batches</button></p>
</form>';

?>
<form name='addBatch' action = 'display.php?batchID=<?php echo $newBatch; ?>' method='POST' target=_blank>
	<table>
		<tr>
			<td>Batch Name</td>
			<td>Batch Type</td>
			<td>Start Date</td>
			<td>End Date</td>
			<td>Maintainer</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td><input type=text name=batchName></td>
			<td>
				<select name=batchType>
		        	<option value="1">Regular</option>
                                <option value="2">Discontinued</option>
				</select>
			</td>
	     	<td><input name="startDate" class="datepick" type="text" size=10></td>
	     	<td><input name="endDate" class="datepick" type="text" size=10></td>
	     	<td>
			<select name="maintainer">
                	<option value="error">Who are You?</option>' . "\n";

		<!-- put a employee selector here -->
<?php

        $query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
        $result = mysqli_query($db_slave, $query);
        while ($row = mysqli_fetch_array($result)) {
                echo "<option value=\"$row[1]\">$row[0]</option>\n";
        } 
?>
        	</select>

		</td>
	     	<td><input type=submit name=submit value=Add></td>
		</tr>
	</table>
</form>

<?php


//echo "<form action='index.php' method='post'>";
//echo "<div class='right'><input type='submit' name='delete' value='Delete Marked'></div>";
echo "<table border=0 cellspacing=0 cellpadding=2 width=100%>";
echo "<th style='text-align: left' >Batch Name</th><th style='text-align: left'>Type</th><th style='text-align:left'>Start</th><th style='text-align: left'>Finish</th>
<th style='text-align: left'>Maintainer</th>
<th style='text-align: left'>Active?</th>
";//<th style='text-align: left'>Mark?</th>
echo "<th style='text-align: left'>Delete?</th>";

$bg = '#eeeeee';
$date = DATE('Y-m-d');
while($batchListW = mysqli_fetch_row($batchListR)){
        $start = $batchListW[3];
        $end = $batchListW[4];

	$deleted = ($batchListW[6]==1?"<input type='checkbox' checked=checked name='deleteList[]' value='$batchListW[0]'/>":"<input type='checkbox' name='deleteList[]' value='$batchListW[0]' />");

	if (isset($batchListW[5])) {
		$batchMaintainer = $batchListW[5];
	} else {
		$batchMaintainer = "no one yet";
	}
/*	if ($_POST['showinactive'] == 'show') {
		if ($batchListW[2] == 1) $batchType = "Sales";
		elseif ($batchListW[2] == 2) $batchType = "Discont.";
		elseif ($batchListW[2] == 6) $batchType = "Price Change";

		if ($start <= $date && $end >= $date) $active = "<font color=green>Active</font>";
		else $active = "<font color=red>InActive</font>";

		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'><td><a href=display.php?batchID=$batchListW[0] target=_blank>";
	   	echo "$batchListW[1]</a><input type='hidden' value='$batchListW[0]' class='batchID' /></td>";
	   	echo "<td>$batchType</td>";
	   	echo "<td>$batchListW[3]</td>";
	   	echo "<td>$batchListW[4]</td>";
		echo "<td>$batchMaintainer</td>";
		echo "<td class='activeFlag'>$active</td>";
//		echo "<td>$deleted</td>";
		echo "<td><input type='submit' name='deleteBatch' value='delete' class='deleteBatchButton'></td>";
		echo "</tr>";
*///	} elseif (($_POST['showinactive'] == 'hide') && ($start <= $date) && ($end >= $date)) {
		if ($batchListW[2] == 1) $batchType = "Sales";
		elseif ($batchListW[2] == 2) $batchType = "Discont.";
		elseif ($batchListW[2] == 6) $batchType = "Price Change";

		if ($start <= $date && $end >= $date) $active = "<font color=green>Active</font>";
		else $active = "<font color=red>InActive</font>";

		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'><td><a href=display.php?batchID=$batchListW[0] target=_blank>";
		echo "$batchListW[1]</a><input type='hidden' value='$batchListW[0]' class='batchID' /></td>";
		echo "<td>$batchType</td>";
		echo "<td>$batchListW[3]</td>";
		echo "<td>$batchListW[4]</td>";
		echo "<td>$batchMaintainer</td>";
		echo "<td class='activeFlag'>$active</td>";
//		echo "<td>$deleted</td>";
		echo "<td><input type='submit' name='deleteBatch' value='delete' class='deleteBatchButton'></td>";
                echo "</tr>";
//	}
}
echo "</table>";
//echo "</form>";
include('../includes/footer.html');
?>
