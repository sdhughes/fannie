<?php
/********
 *
 *  spins date generator
 *
 *  dynamically calculates weekend start and end dates for the next year 
 *  author: Matthaus Litteken
 *  date: Jan 8, 2011
 *
 ********/


ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

//require_once('src/mysql_connect.php');
require_once('../includes/mysqli_connect.php');

//mysql_select_db($db_master, 'is4c_log');
mysqli_select_db($db_master, 'is4c_log');


//$q = "SELECT MAX(end_date) FROM SPINS_2011";
$q = "SELECT CURDATE()";
//$r = mysql_query($q, $db_master);
$r = mysqli_query($db_master, $q);

if ($r) {
	//list($start) = mysql_fetch_row($r);
//	$date = new DateTime($start);
	$date = new DateTime('2011-11-06');
	
    
//    $date->add(new DateInterval('P1D'));
	printf("Start Date: %s<br />", $date->format('Y-m-d'));
	$start = new DateTime($date->format('Y-m-d'));
} else {
	//printf('Error! %s', mysql_error($db_master));
	printf('Error! %s', mysqli_error($db_master));
}

$end = new DateTime($start->format('Y-m-d'));
$endDate = new DateTime($start->format('Y-m-d'));
$endDate->add(new DateInterval('P5Y'));
//strtotime
$periodID = 0;
//$weekCount = 0;
$success = 0;

//strtotime($end->format('Y-m-d')) < strtotime($endDate->format('Y-m-d')) && 
 echo '<table border=1><tr><td>Period ID:</td><td>Start:</td><td>End:</td></tr>';

while ($periodID <= 1000) {
	$periodID++;
	//$period = floor(($weekCount - 1) / 4) + 1;
	
	$end = new DateTime($start->format('Y-m-d'));
	$end->add(new DateInterval('P13D'));
	
    $startDate = ($start->format('Y-m-d') . " 00:00:00");
    $endDate =  ($end->format('Y-m-d') . " 23:59:59");

	printf('<tr><td>%u</td><td>%s</td><td>%s</td></tr>', $periodID, $startDate, $endDate);
     
	
    /*$q2 = sprintf("INSERT INTO payperiods (periodID, periodStart, periodEnd) VALUES (%u, '%s', '%s')", $periodID, $startDate, $endDate);
	
	//$r2 = mysql_query($q2, $db_master);
	$r2 = mysqli_query($db_master, $q2);
	
	//if ($r2 && mysql_affected_rows($db_master) == 1) {
	if ($r2 && mysqli_affected_rows($db_master) == 1) {
		$success++;
	} else {
		//printf("Error: %s, Query: %s", mysqli_error($db_master), $q2);
		printf("Error: %s, Query: %s", mysql_error($db_master), $q2);
	}
	*/
	$start = new DateTime($end->format('Y-m-d'));
	$start->add(new DateInterval('P1D'));
}
echo "</table>";
echo $success . " =? " . $periodID;
