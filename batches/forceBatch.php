<?php
require_once ('../includes/mysqli_connect.php');
$page_title = 'Fannie - Batch Module';
$header = 'Batch Maintanence';
include ('../includes/header.html');

$batchID = $_GET['batchID'];

mysqli_select_db($db_master, 'is4c_op');

$forceQ="UPDATE is4c_op.products as p,
	is4c_op.batches as b,
	is4c_op.batchList as l
	SET p.start_date = b.startDate,
	p.end_date = b.endDate,
	p.special_price = l.salePrice,
	p.discounttype = b.discounttype 
	WHERE l.upc = p.upc
	AND b.batchID = l.batchID
	AND b.batchID = $batchID";

$forceR = mysqli_query($db_master, $forceQ);

echo "<h2Batch $batchID has been forced</h2></br></br>";
echo "<p>Back to the <a href='index.php'>batch list</a></p>";
include ('../includes/footer.html');
?>
