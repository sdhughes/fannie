<html>
<head>
<link rel="stylesheet" href="../style.css" type="text/css" />
</head>
<body>
<?php

foreach ($_POST AS $key => $value) {
    $$key = $value;
}

require_once ('../includes/mysqli_connect.php');
mysqli_select_db ($db_master, 'is4c_op');

$maxBatchIDQ = "SELECT MAX(batchID) FROM batches";
$maxBatchIDR = mysqli_query($db_master, $maxBatchIDQ);
$maxBatchIDW = mysqli_fetch_array($maxBatchIDR);

$batchID = $maxBatchIDW[0];

$batchInfoQ = "SELECT * FROM batches WHERE batchID = $batchID";
$batchInfoR = mysqli_query($db_master, $batchInfoQ);
$batchInfoW = mysqli_fetch_row($batchInfoR);

//$batchID = 1;
if (isset($_GET['batchID'])) {
   $batchID = (int) $_GET['batchID'];
}

if (isset($_GET['submit'])) {
   $upc = str_pad($_GET['upc'],13,0,STR_PAD_LEFT);
   $salePrice = $_GET['saleprice'];

   if (isset($_GET['delete'])) {
      $del = $_GET['delete'];
   }
?>   <script language="javascript">
    parent.frames[1].location.reload();
    </script>
<?php
} else {
	$upc = '';
	$salePrice = '';
	$del = 0;
}

echo "<div class='inline_item'>";
echo "<form action=addItems.php action=GET>Add/Remove Item in Sales Batch<br />";
echo "<b>Sale Price: </b><input type=text name=saleprice size=6></td>";
echo "<b>UPC: </b><input type=text name=upc>";
echo "<input type=hidden name=batchID value=$batchID>";
echo "<b>Delete</b><input type=checkbox name=delete value=1>";
echo "<input type=submit name=submit value=submit>";
echo "</form>";

//echo "<h2>this is upc" . $upc . "</h3>";
if ($upc == '0000000000000') {
    echo "<script type='text/javascript' language='javascript' >
            alert('No upc entered, please try again');
            </script>";

} else {


$selBListQ = "SELECT * FROM batchList WHERE upc = $upc
              AND batchID = $batchID";
$selBListR = mysqli_query($db_master, $selBListQ);
$selBListN = mysqli_num_rows($selBListR);

$startDate = $batchInfoW[1];
$endDate = $batchInfoW[2];

$checkItemQ = "SELECT l.* FROM batchList AS l JOIN batches AS b ON b.batchID = l.batchID
               where upc = $upc and b.endDate >= '$startDate'";
$checkItemR = mysqli_query($db_master, $checkItemQ);
$checkItemN = mysqli_num_rows($checkItemR);
$checkItemW = mysqli_fetch_row($checkItemR);


if ($del == 1) {
   $delBListQ = "DELETE FROM batchList WHERE upc = $upc AND
                batchID = $batchID";
   $delBListR = mysqli_query($db_master, $delBListQ);
   $delUpdateQ = "UPDATE products AS p SET p.start_date = NULL,
   		p.end_date = NULL,
		p.special_price = 0,
		p.discounttype = 0
		WHERE p.upc = $upc";
   $delUpdateR = mysqli_query($db_master, $delUpdateQ);
} else {
      if ($selBListN == 0) {
         $insBItemQ = "INSERT INTO batchList(upc,batchID,salePrice,added)
                   VALUES('$upc',$batchID,$salePrice, now())";
         //echo $insBItemQ;
         $insBItemR = mysqli_query($db_master, $insBItemQ);
      } else {
         $upBItemQ = "UPDATE batchList SET salePrice=$salePrice WHERE upc = '$upc'
                   AND batchID = $batchID";
         //echo $upBItemQ;
         $upBItemR = mysqli_query($db_master, $upBItemQ);
      }
}

}
?>
</body>
</html>
