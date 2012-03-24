<?php
//This file is used in Fannie to delete items from the shrinkLog

	if (isset($_POST['upc'])) {
		require_once('../includes/mysqli_connect.php');
		global $db_master;

		mysqli_select_db('is4c_log');

		$upc = $_POST['upc'];
		$datetime = $_POST['datetime'];
		$quantity = $_POST['quantity'];

		$testQ = "select * from is4c_log.shrinkLog where datetime = '$datetime'";
		$result = mysqli_query($db_master, $testQ) or die ('Query Error: ' . mysqli_error($db_master));
		if (mysqli_num_rows($result) == 1) {
			$deleteQ = "delete from is4c_log.shrinkLog where upc = '$upc' and datetime = '$datetime' and quantity = '$quantity'";
			$deleteR = mysqli_query($db_master, $deleteQ) or die('Query error: ' . mysqli_error($db_master));
			echo "you just unshrunk the mistake that took place at $datetime!";

		} else {
			echo "nothing to unshrink. already unshrunk?";
		}

	} else {

		echo "you made a mistake, pal.";
	}




?>
