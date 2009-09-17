<?php
require_once('/pos/fannie/includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_log');
mysqli_select_db($db_slave, 'is4c_log');

/********************************************
 *      Options
 *      
/* format datetime */
    $timestamp = date('Y-m-d H:i:s');
/* specify a log file to direct stdout */
    $log_file = '/pos/fannie/logs/subs.log';
/* 
*********************************************/

// First check if the script should be running. Bi-weekly run, but run weekly through cron. Check to see if yesterday was the end of a pay period.
$weekCheckQ = "SELECT periodID FROM payperiods WHERE DATE(periodEND) = DATE_SUB(curdate(), INTERVAL 1 DAY)";
$weekCheckR = mysqli_query($db_slave, $weekCheckQ);

list($weekCheck) = mysqli_fetch_row($weekCheckR);

if (mysqli_num_rows($weekCheckR) == 1) { // Match found, yesterday was the end of a payperiod. Start script.
    
    error_log("[$timestamp] -- Sub Hours Script Starting...\n", 3, $log_file);
    
    // Get a list of current subs...
    $cardnoQ = "SELECT firstname, lastname, id, cardno FROM is4c_op.custdata
	    WHERE staff = 2
	    ORDER BY cardno ASC";
    $cardnoR = mysqli_query($db_slave, $cardnoQ);
    
    $count = 0;
    $changeCount = 0;
    
    if ($cardnoR) {
	while (list($firstname, $lastname, $id, $cardno) = mysqli_fetch_array($cardnoR, MYSQLI_NUM)) {
	    $hoursQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
		    FROM is4c_log.timesheet AS t
		    WHERE t.emp_no = (SELECT emp_no FROM is4c_op.employees WHERE card_no = $cardno)
		    AND t.area <> 13
		    AND t.periodID = $weekCheck";
	    $hoursR = mysqli_query($db_slave, $hoursQ);
	    
	    if ($hoursR) {
		list($hours) = mysqli_fetch_row($hoursR);
		mysqli_free_result($hoursR);
	    } else {
		error_log("[$timestamp] -- mySQL Error: " . mysqli_error($db_slave) . "\n[$timestamp] -- Query: $hoursQ\n", 3, $log_file);
	    }
	    
	    if ($hours != 0) {
		error_log("[$timestamp] -- Processing Card No: $cardno ($lastname, $firstname)...", 3, $log_file);
		
		$checkQ = "SELECT * FROM is4c_op.subs WHERE id=$id";
		$checkR = mysqli_query($db_slave, $checkQ);
		
		if ($checkR) {
		    $subsQ = (mysqli_num_rows($checkR) == 1) ? "UPDATE is4c_op.subs SET hours = hours + $hours, updated = now() WHERE id = $id" : "INSERT INTO is4c_op.subs (id, hours) VALUES ($id, $hours)";
		} else {
		    error_log("[$timestamp] -- mySQL Error: " . mysqli_error($db_slave) . "\n[$timestamp] -- Query: $checkQ\n", 3, $log_file);
		}
		
		mysqli_free_result($checkR);
		
		$custdataQ = "UPDATE is4c_op.custdata SET SSI = SSI + $hours WHERE id = $id";
		$custdataR = mysqli_query($db_master, $custdataQ);
		
		if ($custdataR && mysqli_affected_rows($db_master) == 1) {
		    error_log("added $hours hours to custdata...", 3, $log_file);
		} else {
		    error_log("could not add $hours hours to custdata...", 3, $log_file);
		}
		
		$subsR = mysqli_query($db_master, $subsQ);
		
		if ($subsR && mysqli_affected_rows($db_master) == 1) {
		    error_log("added $hours hours to subs...\n", 3, $log_file);
		} else {
		    error_log("could not add $hours hours to subs...\n", 3, $log_file);
		}
		
		++$changeCount;
	    } else {
		error_log("[$timestamp] -- Processing Card No: $cardno ($lastname, $firstname)...no hours to process.\n", 3, $log_file);
	    }
	    ++$count;
	}
    } else {
	error_log("[$timestamp] -- mySQL Error: " . mysqli_error($db_slave) . "\n[$timestamp] -- Query: $cardnoQ\n", 3, $log_file);
    }
    
    mysqli_free_result($cardnoR);
    
    error_log("[$timestamp] -- Success: updated hours for $changeCount of $count subs.\n\n", 3, $log_file);

} else {
    error_log("[$timestamp] -- Script called. Not at the end of a pay period.\n\n", 3, $log_file);
}
mysqli_close($db_master);
mysqli_close($db_slave);

?>
