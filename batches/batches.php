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
} else if (isset($_POST['submit'])) {
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
}

$batchInfoQ = "SELECT * FROM batches WHERE batchID = $batchID";
$batchInfoR = mysqli_query($db_master, $batchInfoQ);
$batchInfoW = mysqli_fetch_row($batchInfoR);


$selBItemsQ = "SELECT b.*,p.*  from batchList as b LEFT JOIN 
               products as p ON b.upc = p.upc WHERE batchID = $batchID 
               ORDER BY b.added DESC, b.listID DESC";

$selBItemsR = mysqli_query($db_master, $selBItemsQ);
echo '<form action="saletags.php" method="POST" target="_blank">
    <button name="tags" type="submit">Generate Sale Tags!</button>
    <input type="hidden" name="batchID" value="' . $batchID . '">
    </form>';
echo "<form action=batches.php method=POST>";
echo "<table border=0 cellspacing=0 cellpadding=5>";
echo "<tr><td>Batch Name: <font color=blue>$batchInfoW[3]</font></td>";
echo "<form action=batches.php method=post>";
echo "<td>Start Date: <input type=text name=startdate value=\"$batchInfoW[1]\" size=18></td>";
echo "<td>End Date: <input type=text name=enddate value=\"$batchInfoW[2]\" size=10></td>";
echo "<td><input type=submit value=\"Change Dates\" name=datechange></td></tr>";
echo "<input type=hidden name=batchID value=$batchID>";
echo "</form>";
echo "<th>UPC<th>Description<th>Normal Price<th>Sale Price<th>Delete";
echo "<form action=batches.php method=POST>";
$bg = '#eeeeee';
while($selBItemsW = mysqli_fetch_row($selBItemsR)){
	$upc = $selBItemsW[1];
	$field = 'sale'.$upc;
	$del = 'del'.$upc;
	//echo $del;
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo "<tr bgcolor='$bg'><td align=center><a href='../item/itemMaint.php?submitted=search&upc={$selBItemsW[1]}' target='_blank'>$selBItemsW[1]</a></td>";
	if(!$selBItemsW[7]) {
            echo "<td><font color=red>NO PRODUCT RECORD FOR THIS ITEM</font></td>";  //	TODO -- link this to new product page
	} else {
            echo "<td>$selBItemsW[7]</td>";
	}
	echo "<td align=right>$selBItemsW[8]</td><td align=center><input type=text name=$field field value=$selBItemsW[3] size=8></td>";
	echo "<input type=hidden name=upc value='$upc'>";
	echo "<td><input type=checkbox value=1 name=$del></td></tr>";
}
echo "<input type=hidden value=$batchID name=batchID>";
echo "<tr><td><input type=submit name=submit value=submit></td>";
echo "<td><a href=forceBatch.php?batchID=$batchID target=blank>Start (Turn On) Batch Now</a></td>";
echo "<td><a href=resetBatch.php?batchID=$batchID target=blank>Stop (Turn Off) Batch Now</a></td>";
echo "</tr></form>";

?>
</body>
</html>
