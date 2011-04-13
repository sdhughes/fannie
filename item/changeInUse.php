<?php
//////////////////////////////////////////////////
//
//	@file		changeInUse.php
//
//	@author 	steven hughes
//	@purpose 	This file is a utility function to change 
//			the 'inUse' status of an array of products
//
//////////////////////////////////////////////////

	//connect to the database using util file in relative directory...should I redo this for portability?
	include_once('../includes/mysqli_connect.php');
//hardcoded just to make it work
//	$db_master = mysqli_connect('localhost','root','mB7x503');

	mysqli_select_db($db_master,'is4c_op') or die ('Could not select database');

	//find out what they were searching for
	if (isset($_POST['searchTerm'])) {
		$upc = $_POST['searchTerm'];
	} else if (isset($_GET['searchTerm'])) {
		$upc = $_GET['searchTerm'];
	}

	
	if (isset($_POST['submit'])) {
		
		//create the array that holds all products to be updated
		$inUseItems = "(" . implode(',', $_POST['inUse']) . ")";	
		
		if ($_POST['submit'] == 'Take Out of Use') {

			$inUseQ = "UPDATE products SET inUse=0 WHERE upc IN $inUseItems;";

		} elseif ( $_POST['submit'] == 'Put In Use' ) {
		
			$inUseQ = "UPDATE products SET inUse=1 WHERE upc IN $inUseItems;";

		} else {
			$inUseQ = "SELECT * FROM products where upc IN $inUseItems";
		}
	
		$inUseR = mysqli_query($db_master, $inUseQ) or die('Something happened! ... ' . mysqli_error($db_master));	

		//at the end of it all, recall the original page showing changes. 
		header("Location: itemMaint.php?upc=$upc&submitted=search");
		
//		print_r($_POST);

	}

?>
