<?php
	require_once('../includes/mysqli_connect.php');
	mysqli_select_db($db_master, 'is4c_log') or die("Could not select Database: " . mysqli_error($db_master));
//	print_r($_POST);

	//copy the variables out of the POST array
	if (isset($_POST['submit'])) { 
		foreach ($_POST as $key => $value) ${$key} = $value;
	} elseif (isset($_GET['submit'])) {
		
		foreach ($_GET as $key => $value) ${$key} = $value;
	} else {
		$upc = "000000000005115";
		$date1 = '2010-01-01';
		$date2 = '2010-01-04';
	}
	//get the years
	$year1 = substr($date1,0,4);
	$year2 = substr($date2,0,4);


	$query = "SELECT DAY(datetime) as datetime, upc, description, SUM(total) as total FROM (";

	for ($i = $year1; $i <= $year2; $i++) {

		 $query .= " SELECT datetime, upc, description, total FROM is4c_log.trans_$i WHERE trans_status <> 'X' AND upc = $upc AND DATE(datetime) BETWEEN '$date1' AND '$date2' AND emp_no <> 9999";

		//if we're gotten to the same yar as the 2nd date, then		
		if ($i == $year2) {

			//since we're inside the last year wanted, if the 2nd date is today, then use the day's transaction table (dtrans)
			if ($date2 == date('Y-m-d') ) {
				$query .= " UNION ALL SELECT datetime, upc, description, total FROM is4c_log.dtransactions WHERE trans_status <> 'X' AND upc = $upc AND DATE(datetime) BETWEEN '$date1' AND '$date2' AND emp_no <> 9999";
			}

		} else {
			$query .= " UNION ALL ";
		}
	}

	//close off the statement
	$query .= " ) AS yearSpan GROUP BY DATE(datetime); ";
//DEBUGGING
//	echo "Query: " . $query . "<br />";

	$result = mysqli_query($db_master,$query) or die("Couldn't complete query: " . mysqli_error($db_master));
	
	$dataArray = array();
    $returnArray = array();
	
//	$filename  = "data.json";
//	$handle = fopen($filename, "w+");
	
	while (	$row = mysqli_fetch_assoc($result)) {
//		echo "<br />" . $row['datetime'] . " -- " . $row['total'];
		$dataArray[] = array((double)$row['datetime'],(double)$row['total']);
	}
	
	//if (isset($_POST['submit'])) $returnArray = array('post'=>$_POST,'data'=>$dataArray);
	//else $returnArray = array('post'=>$_GET,'data'=>$dataArray);
	//$returnArray[] = array('label'=>'Testing','data'=>$dataArray,'color'=>'#501133');
$returnArray = $dataArray;	
	//# a few different methods for outputtings whatever. maybe put it into a switch?
	//print_r($dataArray);
	//fwrite($handle,json_encode($dataArray));
	//return json_encode($dataArray);
	echo json_encode($returnArray);
	//fclose($handle);

?>

