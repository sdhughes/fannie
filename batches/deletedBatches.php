<html>
<head>
<link rel="stylesheet" href="../style.css" type="text/css" />
</head>
<body>
<?php
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_op');

if (isset($_GET['batchID'])) {
    $batchID = $_GET['batchID'];
}

if (isset($_POST['datechange'])) {
    $batchID = $_POST['batchID'];
    $startdate = $_POST['startdate'];
    $enddate = $_POST['enddate'];
  
    $dateQ = "UPDATE batches SET startdate='$startdate',
        enddate='$enddate' WHERE batchID=$batchID";
    $dateR = mysqli_query($db_master, $dateQ);
} else if (isset($_POST['submit']) && $_POST['submit'] == 'submit') {
    foreach ($_POST AS $key => $value) {
        $batchID = $_POST['batchID'];
     
        //echo "values".$key . ": ".$value . "<br>";
        if (substr($key,0,4) == 'sale') {
            $$key = $value;
            $upc1 = substr($key,4);
            $queryTest = "UPDATE batchList SET salePrice = $value WHERE upc = '$upc1' and batchID = $batchID";
            $resultTest = mysqli_query($db_master, $queryTest);
        }

        if (substr($key,0,3) == 'del') {
            $$key = $value;
            $upc1 = substr($key,3);
            $delItmQ = "DELETE FROM batchList WHERE upc = '$upc1' and batchID = $batchID";
            $delItmR = mysqli_query($db_master, $delItmQ);
            $delUpdateQ = "UPDATE products AS p SET
       		p.start_date = NULL,
		p.end_date = NULL,
		p.special_price = 0,
		p.discounttype = 0
		WHERE p.upc = $upc1";
            $delUpdateR = mysqli_query($db_master, $delUpdateQ);
        }
    }   
}else if (isset($_POST['changeMaintainer'])) {
	$newMaint = $_POST['whose'];
	$batchID = $_POST['batchID'];
//	print_r($_POST);
	$changeMaintQ = "UPDATE batches SET maintainer = $newMaint WHERE batchID = '$batchID';";
	$changeMaintR = mysqli_query($db_master, $changeMaintQ) or die ("Query Error: $changeMaintQ <br>" . mysqli_error($db_master));
//	$changeMaintW = mysqli_fetch_array($changeMaintR);
} 

$batchInfoQ = "SELECT * FROM deletedBatches WHERE batchID = $batchID";
$batchInfoR = mysqli_query($db_master, $batchInfoQ);
$batchInfoW = mysqli_fetch_row($batchInfoR);


$selBItemsQ = "SELECT b.*,p.*  from batchList as b LEFT JOIN 
               products as p ON b.upc = p.upc WHERE batchID = $batchID 
               ORDER BY b.added DESC, b.listID DESC";

$selBItemsR = mysqli_query($db_master, $selBItemsQ);
/*
if (isset($_POST['maintainer'])) {
	$maintainer = $_POST['maintainer'];
} else {
	$maintainer = 'no one';
}
$maintainerQ = "SELECT e.FirstName FROM is4c_op.employees as e WHERE e.emp_no = $batchInfoW[6];";
$maintainerR = mysqli_query($db_master, $maintainerQ) or die("Query: $maintainerQ had error: " . mysqli_error($db_master)); 
$maintainerW = mysqli_fetch_row($maintainerR);
*/
echo "<div width=900px>";
echo '
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">';
echo "Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true, duration: 0 });
        });</script>";
echo '<form action="saletags.php" method="POST" target="_blank">
    <button name="tags" type="submit">Generate Sale Tags!</button>
    <input type="hidden" name="batchID" value="' . $batchID . '">
    </form>';
echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='POST'>";
echo "<table border=0 cellspacing=0 cellpadding=5 margin=3>
	<tr>
		<td>Batch Name: <font color=blue>$batchInfoW[3]</font></td>
		<td>Maintainer:
			<select name=whose>

                <option value='error'>Who are You?</option>";
        
$query = "SELECT FirstName, emp_no FROM is4c_op.employees where EmpActive=1 ORDER BY FirstName ASC";
$result = mysqli_query($db_master, $query);
while ($row = mysqli_fetch_array($result)) {
        echo "<option value=\"$row[1]\"";
		if ($batchInfoW[6] == $row[1]) echo "selected";
		
	echo ">$row[0]</option>\n";
}

echo "		</select></td><td><input type=submit value=\"Change Maintainer\" name=changeMaintainer ></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>Start Date: <input type=text class=datepick name=startdate value=\"$batchInfoW[1]\" size=18></td>
		<td>End Date: <input type=text class=datepick name=enddate value=\"$batchInfoW[2]\" size=10></td>
		<td><input type=submit value=\"Change Dates\" name=datechange></td>
    <input type=hidden name=batchID value='$batchID'>
	</tr>
	</table>";
// echo " 	<input type=hidden name=maintainer value=$batchInfoW[6] />";
echo "</form>";
echo "<form action=batches.php method=POST>
    <input type='hidden' name='batchID' value='$batchID'>";
echo "<table style='border: 1px solid black' margin=10>
		<th>UPC</th><th>Description</th><th>Normal Price</th><th>Sale Price</th><th>Delete</th>";
$bg = '#dddddd';
while($selBItemsW = mysqli_fetch_row($selBItemsR)){
	$upc = $selBItemsW[1];
	$field = 'sale'.$upc;
	$del = 'del'.$upc;
	//echo $del;
	$bg = ($bg=='#dddddd' ? '#ffffff' : '#dddddd'); // Switch the background color.
	echo "<tr bgcolor='$bg'><td align=center><a href='../item/itemMaint.php?submitted=search&upc={$selBItemsW[1]}' target='_blank'>$selBItemsW[1]</a></td>";
	if(!$selBItemsW[7]) {
            echo "<td><font color=red>NO PRODUCT RECORD FOR THIS ITEM</font></td>";  //	TODO -- link this to new product page
	} else {
            echo "<td>$selBItemsW[7]</td>";
	}
	echo "<td align=right>$$selBItemsW[8]</td><td align=center><input type=text name=$field field value=$selBItemsW[3] size=8></td>";
	echo "<input type=hidden name=upc value='$upc'>";
	echo "<td><input type=checkbox value=1 name=$del></td></tr>";
}
echo "</table>";
echo "<p>
<a href=forceBatch.php?batchID=$batchID target=blank>Start (Turn On) Batch Now</a>  <a href=resetBatch.php?batchID=$batchID target=blank>Stop (Turn Off) Batch Now</a>
<input type=submit name=submit value=submit> 
</p>";
echo "</form></div>";

?>
</body>
</html>
