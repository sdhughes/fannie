<?php

function getDataFromDatabase($upc, $date1, $date2) {

	$db_master = mysqli_connect('localhost','steve','st3v3','is4c_log');
	 
	mysqli_select_db($db_master,'is4c_log') or die("Select DB Error: <br />" . mysqli_error($db_master));

	$upc_where = "";

	if (is_numeric($upc)) $upc_where = " upc = $upc ";
	else $upc_where = ' upc LIKE "%' . $upc . '" ';

	$query = 'select date(datetime) as date,sum(total) as total from transarchive where ' . $upc_where . ' AND date(datetime) between "' . $date1 . '" and "' . $date2 . '" group by date ';


//DEBUG echo "query: $query <br />";

	$result = mysqli_query($db_master, $query) or die('Error<br /> ' . $query . '<br />' . mysqli_query($db_master));
	mysqli_close($db_master);

	return $result;

}

$mergedData = array();
 
if (isset($_POST['date1']) || isset($_GET['date1'])) (isset($_POST['date1'])?$date1 = $_POST['date1']:$date1 = $_GET['date1']);
else $date1 = date('Y-m-d');
if (isset($_POST['date2']) || isset($_GET['date2'])) (isset($_POST['date2'])?$date2 = $_POST['date2']:$date2 = $_GET['date2']);
else $date2 = $date1;

if (isset($_POST['upc']) || isset($_GET['upc'])) (isset($_POST['upc'])?$upc = $_POST['upc']:$upc = $_GET['upc']);
else $upc = '';



 //Get the first set of data you want to graph from the database
 $databaseData1 = getDataFromDatabase($upc, $date1,$date2);

//DEBUG
//print_r($databaseData1);
  
  //loop through the first set of data and pull out the values we want, then format
//  foreach($databaseData1 as $r) {


while($row = mysqli_fetch_array($databaseData1,MYSQLI_ASSOC)) {
          $x = $row['date'];
              $y = $row['total'];
                  $data1[] = array ((float)$x, (float)$y);
  }
   
   //send our data values to $mergedData, add in your custom label and color
   $mergedData[] =  array('label' => "Data 1" , 'data' => $data1, 'color' => '#6bcadb');
    
/*    //Get the second set of data you want to graph from the database
    $databaseData2 = getDataFromDatabase($upc, $date1, $date2);
     
      
      foreach($databaseData2 as $r)
      {
              $x = $r['date'];
                  $y = $r['total'];
                      $data2[] = array ($x, $y);
      }
       
       //send our data values to $mergedData, add in your custom label and color
       $mergedData[] = array('label' => "Data 2" , 'data' => $data2, 'color' => '#6db000');
        
         //now we can JSON encode our data
         echo json_encode($mergedData);
         */
	echo json_encode($data1);
?>
