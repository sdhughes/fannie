<script src="../src/CalendarControl.js"
        language="javascript"></script>
</head>
<?php
$_SESSION['batchID'] = 1;
$header = 'Batch Maintanence';
$page_title = 'Fannie - Batch Module';
include ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');

$batchListQ= "SELECT b.batchID,b.batchName,b.batchType,DATE(b.startDate),b.endDate 
          FROM batches as b
          ORDER BY b.batchID DESC";

mysqli_select_db($db_slave, 'is4c_op');

$batchListR = mysqli_query($db_slave, $batchListQ);

$maxBatchQ = "SELECT max(batchID) FROM batches";
$maxBatchR = mysqli_query($db_slave, $maxBatchQ);
$maxBatchW = mysqli_fetch_row($maxBatchR);
$newBatch = $maxBatchW[0] + 1; 


if (!isset($_POST["showinactive"])) {$_POST["showinactive"] = "hide";}
echo '<form action="index.php" method="POST">';
if ($_POST['showinactive'] == 'hide') {
        echo '<p align="center"><BUTTON name=showinactive type=submit value="show">Show Inactive Batches</BUTTON></p>';
} elseif ($_POST['showinactive'] == 'show') {
        echo '<p align="center"><BUTTON name=showinactive type=submit value="hide">Hide Inactive Batches</BUTTON></p>';
}
echo '</form>';

?>

<form name='addBatch' action = 'display.php?batchID=<?phpecho $newBatch; ?>' method='POST' target=_blank>
	<table>
		<tr>
			<td>&nbsp;</td>
			<td>Batch Name</td>
			<td>Start Date</td>
			<td>End Date</td>
		</tr>
		<tr>
			<td>&nbsp;
				<select name=batchType>
		        	<option value="1">Regular Sale</option>
                                <option value="2">Discontinued Sale</option>
				</select>
			</td>
			<td><input type=text name=batchName></td>
	     	<td><input name="startDate" onfocus="showCalendarControl(this);" type="text" size=10></td>
	     	<td><input name="endDate" onfocus="showCalendarControl(this);" type="text" size=10></td>
	     	<td><input type=submit name=submit value=Add></td>
		</tr>
	</table>
</form>

<?php
echo "<table border=0 cellspacing=0 cellpadding=5 width=90%>";
echo "<th>Batch Name<th>Batch Type<th>Start Date<th>End Date<th>Active?";
$bg = '#eeeeee';
$date = DATE('Y-m-d');
while($batchListW = mysqli_fetch_row($batchListR)){
        $start = $batchListW[3];
        $end = $batchListW[4];
	if ($_POST['showinactive'] == 'show') {
		if ($batchListW[2] == 1) $batchType = "Sales Batch";
		elseif ($batchListW[2] == 2) $batchType = "Discontinued Items Batch";
		elseif ($batchListW[2] == 6) $batchType = "Price Change Batch";
		
		if ($start <= $date && $end >= $date) $active = "<font color=green>Active</font>";
		else $active = "<font color=red>InActive</font>";
	   	
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'><td><a href=display.php?batchID=$batchListW[0] target=_blank>";
	   	echo "$batchListW[1]</a></td>";
	   	echo "<td>$batchType</td>";
	   	echo "<td>$batchListW[3]</td>";
	   	echo "<td>$batchListW[4]</td>";
		echo "<td>$active</td>";
		echo "</td></tr>";
	} elseif (($_POST['showinactive'] == 'hide') && ($start <= $date) && ($end >= $date)) {
		if ($batchListW[2] == 1) $batchType = "Sales Batch";
		elseif ($batchListW[2] == 2) $batchType = "Discontinued Items Batch";
		elseif ($batchListW[2] == 6) $batchType = "Price Change Batch";
		
		if ($start <= $date && $end >= $date) $active = "<font color=green>Active</font>";
		else $active = "<font color=red>InActive</font>";
		
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'><td><a href=display.php?batchID=$batchListW[0] target=_blank>";
		echo "$batchListW[1]</a></td>";
		echo "<td>$batchType</td>";
		echo "<td>$batchListW[3]</td>";
		echo "<td>$batchListW[4]</td>";
		echo "<td>$active</td>";
                echo "</td></tr>";
	}
}
echo "</table>";
include('../includes/footer.html');
?>
