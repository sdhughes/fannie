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

require_once('includes/mysqli_connect.php');

mysqli_select_db($db_master, 'is4c_log');

$q = "SELECT MAX(end_date) FROM SPINS_2010";
$r = mysqli_query($db_master, $q);

if ($r) {
	list($start) = mysqli_fetch_row($r);
	$date = new DateTime($start);
	$date->add(new DateInterval('P1D'));
	printf("Max 2010 Date: %s<br />", $date->format('Y-m-d'));
	$start = new DateTime($date->format('Y-m-d'));
} else {
	printf('Error! %s', mysqli_error($db_master));
}

$end = new DateTime($start->format('Y-m-d'));
$endDate = new DateTime($start->format('Y-m-d'));
$endDate->add(new DateInterval('P365D'));

$weekCount = 0;
$success = 0;

//strtotime($end->format('Y-m-d')) < strtotime($endDate->format('Y-m-d')) && 

while ($weekCount <= 52) {
	$weekCount++;
	$period = floor(($weekCount - 1) / 4) + 1;
	
	$end = new DateTime($start->format('Y-m-d'));
	$end->add(new DateInterval('P6D'));
	
	printf('Period: %u, WeekCount: %u, Start: %s, End: %s' . "<br />", $period, $weekCount, $start->format('Y-m-d'), $end->format('Y-m-d'));
	$q2 = sprintf("INSERT INTO SPINS_2011 (period, week_tag, start_date, end_date) VALUES (%u, %u, '%s', '%s')", $period, $weekCount, $start->format('Y-m-d'), $end->format('Y-m-d'));
	
	$r2 = mysqli_query($db_master, $q2);
	
	if ($r2 && mysqli_affected_rows($db_master) == 1) {
		$success++;
	} else {
		printf("Error: %s, Query: %s", mysqli_error($db_master), $q2);
	}
	
	$start = new DateTime($end->format('Y-m-d'));
	$start->add(new DateInterval('P1D'));
}

echo $success . " =? " . $weekCount;
