<?php

	include_once('../includes/mysqli_connect.php');
	global $db_master;

// create some commonly called functions with the batches


	function deleteBatches() {
	//this is called delete but it just moves the batch to another tables for storage, to be recalled later.
		$query = "INSERT INTO deletedBatches (batchID,startDate,endDate,batchName,batchTpe,maintainer) SELECT (batchID,startDate,endDate,batchName,batchType,discountType,maintainer) FROM batches WHERE deleted = 1;";

		$result = mysqli_query($db_master, $query);

		return $result;
	}


	function undeleteBatch($batchID) {

		$query = "INSERT INTO batches (batchID,startDate,endDate,batchName,batchTpe,maintainer) SELECT (batchID,startDate,endDate,batchName,batchType,discountType,maintainer) FROM deletedBatches WHERE batchID = $batchID;";
	}
	
	function createBatch() {

	}

	function addToBatch() {


	}

	function deleteFromBatch() {
	

	
	}

?>
