<?php 
	require_once('./db_handler.php');

	$myHandler = new db_handler();
	$myHandler->init();
	$myHandler->selectDB('OP');

	$myHandler->printHeader();

//	print_r($_POST);
	
	if (isset($_POST['delete'])) {

		$cardNo = str_replace('delete','',$_POST['delete']);
		
		$myHandler->displayDupeRecords($cardNo);

	} elseif (isset($_POST['submit'])) {

		$cardNo = $_POST['cardNo'];

		$cutOffDate = $_POST['modified'];
		
		$array_size = sizeof($cutOffDate);
		
		$newDate = $cutOffDate[$array_size - 1];

		$myHandler->deleteEarliestDuplicate($cardNo, $newDate);

	} else {
	//first view
		$myHandler->displayCardsWithDupes();	
	}
	$myHandler->printFooter();
	$myHandler->destroy();
?>
