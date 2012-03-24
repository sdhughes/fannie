<?php
//This file is used in Fannie to delete items from the shrinkLog

	if (isset($_POST['batchID'])) {
		require_once('../includes/mysqli_connect.php');
		global $db_master;

		mysqli_select_db($db_master, 'is4c_op') or die("Select Database Error: " . mysqli_error($db_master));

		$batchID = $_POST['batchID'];
		//$datetime = $_POST['datetime'];
		//$quantity = $_POST['quantity'];

		$testQ = "select * from is4c_op.batches where batchID = '$batchID'";

		$result = mysqli_query($db_master, $testQ) or die ('Query Error: ' . mysqli_error($db_master));

		if (mysqli_num_rows($result) == 1) {
			$reInsertQuery = "INSERT INTO is4c_op.batches_deleted SELECT * FROM is4c_op.batches WHERE batchID = $batchID";
			$reInsertResult = mysqli_query($db_master, $reInsertQuery) or die('Reinsert Query Error: ' . mysqli_error($db_master));

			$reInsertQuery = "INSERT INTO is4c_op.batchList_deleted SELECT * FROM is4c_op.batchList WHERE batchID = $batchID";
			$reInsertResult = mysqli_query($db_master, $reInsertQuery) or die('Reinsert Query Error: ' . mysqli_error($db_master));

			$deleteQ = "DELETE FROM is4c_op.batches WHERE batchID = $batchID";
			$deleteR = mysqli_query($db_master, $deleteQ) or die('Delete Query Error: ' . mysqli_error($db_master));

			$deleteQ = "DELETE FROM is4c_op.batchList WHERE batchID = $batchID";
			$deleteR = mysqli_query($db_master, $deleteQ) or die('Delete Query Error: ' . mysqli_error($db_master));

			echo "Batch $batchID deleted successfully.";

		} else {
			echo "Nothing to delete. Already deleted?";
		}

	} else {

		//echo "you made a mistake, pal.";
		print_r ($_POST);
	}




?>
