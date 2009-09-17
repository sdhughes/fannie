<?php
require_once ('../includes/mysqli_connect.php');
$page_title = 'Fannie - Batch Module';
$header = 'Batch Maintanence';
include ('../includes/header.html');

$batchID = $_GET['batchID'];

mysqli_select_db($db_master, 'is4c_op');

$resetQ="UPDATE is4c_op.products AS p,
	is4c_op.batches AS b,
	is4c_op.batchList AS l
	SET p.start_date = NULL,
	p.end_date = NULL,
	p.special_price = 0,
	p.discounttype = 0
	WHERE l.upc = p.upc
	AND b.batchID = l.batchID
	AND b.batchID = $batchID";

$resetR = mysqli_query($db_master, $resetQ);

echo $resetQ;
echo "<h2>Batch $batchID has been reset</h2></br>";
echo "<p>Return to batch list";
echo "<form action=index.php method=post>";
echo "<input type=submit name=back value=back></form></p>";

include ('../includes/footer.html');
?>
