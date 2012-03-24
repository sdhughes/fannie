<?php 

require_once('../includes/mysqli_connect.php');

global $db_master;

//figure out what day of the week today is

	$today = date('w'); //returns the day of the week as 0-6

//Use php to create the sales tables
	$date2 = date('Y-m-d'); //get the current date as the 2nd date
	$date1 = time() - ((7 + $today) * 24 * 60 * 60); //calculate 2 sundays ago
	$date1 = date('Y-m-d', $date1);			//turn it into a string for comparing

/* *this code taken from dailyDepartment.php* */
    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    $query = "SELECT ROUND(SUM(total),2), date FROM (";

    //all departments that are valid
    //$dept = '(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,28,29,30,31,32,33,34,35,45)';
    $dept = '(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,28,29,30,31,32,33,34,35,41,42,45)';

    for ($year = $year1; $year <= $year2; $year++) {
        $query .= "SELECT ROUND(SUM(total), 2) AS total, DATE(datetime) AS `date` FROM is4c_log.trans_$year
            WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
            AND department IN $dept
            AND trans_status <> 'X'
            AND emp_no <> 9999
            GROUP BY `date`";

        if ($year == $year2) {
            if (date('Y-m-d') == $date2) {
                $query .= " UNION ALL SELECT ROUND(SUM(total), 2) AS total, DATE(datetime) AS `date` FROM is4c_log.dtransactions
                    WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
                    AND department IN  $dept
                    AND trans_status <> 'X'
                    AND emp_no <> 9999
                    GROUP BY `date`";
            }
            $query .= ") AS yearSpan GROUP BY date";
        } else {
            $query .= " UNION ALL ";
        }
}
    
    $CountQ = "SELECT upc FROM (";
    for ($i = $year1; $i <= $year2; $i++) {
        $CountQ .= "SELECT COUNT(upc) as upc,datetime FROM is4c_log.trans_$i WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN' group by date(datetime)";
        if ($i == $year2) {
        //add the current years data        
            if ($date2 == date('Y-m-d')) {
        	$CountQ .= "UNION ALL SELECT COUNT(upc) as upc,datetime FROM is4c_log.dtransactions WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN' group by date(datetime)";
            }
            $CountQ .= ") AS yearSpan group by date(datetime)";
        } else {
            $CountQ .= " UNION ALL "; 
        }
    }

    $CountC = mysqli_select_db($db_master,'is4c_log') or die("<p>Could not select db: " . mysqli_errno($db_master) . "</p>");
    $CountR = mysqli_query($db_master,$CountQ) or die("<p>Count not complete query: " . mysqli_errno($db_master). "</p>");

//end dailyDepartment.php code

	//the totals calendar
	echo "<table id='sales_table'>
	<tr>
		<td colspan=7>Sales Data from $date1 through $date2</td>
	</tr>";
	echo "<tr id='days_row'>
			<td>sun</td>
			<td>mon</td>
			<td>tue</td>
			<td>wed</td>
			<td>thu</td>
			<td>fri</td>
			<td>sat</td>
		</tr>
		<tr>";
	//DEBUGGGGGG
	//echo $query;

	$result = mysqli_query($db_master, $query) or die('Query Error: ' . mysqli_error($db_master));

	$counter = 0; //to help create a row break at a week
    $day = '';

    $numRows = mysqli_num_rows($result) - 1;

	while ($row = mysqli_fetch_row($result)) {
        $count = mysqli_fetch_row($CountR);
			 $day = substr($row['1'],8,2);

		if ($counter == 7) echo "</tr><tr>"; //create a new row
		//create the days on the calendar
		echo "<td" . (($counter==$numRows)?" id='today' ":"") . ">
			<span class='calDay'>" . substr($row['1'],8,2) . "</span>
			<span class='calCust'>". $count['0'] . "</span>
			<span class='calTotal'>$". $row['0'] . "</span>
		</td>";
		$counter++;

	}
    $j = $counter;

    for ($j = $counter; $j < 14; $j++) {
        $day = (int)$day + 1;
        	echo "<td>
			<span class='calDay'>". $day . "</span>
			<span class='calCust'>0</span>
			<span class='calTotal'>$0.00</span>
            </td>";
    }
	echo "</tr></table><!-- end of totals table -->";

		
mysqli_close($db_master);

?>
