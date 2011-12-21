<?php

/*
function printEmployeeSchedule {
    
    require_once('../includes/mysqli_connect.php');
    global $db_master;

    if (isset($_POST)) {
        foreach ($_POST as $key => $value) {
            ${$key} = $value;
        }
    } elseif ($_GET) {
        foreach ($_GET as $key => $value) {
            ${$key} = $value;
        }
    }

    //make the table
    echo "<table border=1>";
    //headers
    echo "<tr class='header_row'>";
    $i = 0;
    $maxhours = 17;
    echo "<td>shift</td>";
    for ($i = 0; $i < $maxhours; $i++) {
        $currHour = 7 + $i;

	if ($currHour > 12) $currHour -= 12;

        echo "<td>$currHour</td>";
        
    }
    echo "<td>who</td>";

    //iterator for how many hours?
    $scheduleQuery = "SELECT 
                        sch.day, 
                        sh.shift_name as shiftName, 
                        sh.start as start, 
                        sh.stop as end, 
                        e.FirstName as name from schedule as sch, 
                        emp_shifts as sh, 
                        emp_details as e 
                      WHERE 
                        sch.emp_no = e.emp_no
                      AND 
                        e.emp_no = $emp_no;";

    mysqli_select_db($db_master, 'fannie') or die("Couldn't select database: " . mysqli_error($db_master));
    $scheduleResult = mysqli_query($db_master, $scheduleQuery);


    //loops through the hours
    while ($scheduleRow = mysqli_fetch_array($scheduleResult, MYSQLI_ASSOC)) {

        //pull start and end times
        $startTimeStamp = $scheduleRow['start'];
        $endTimeStamp = $scheduleRow['end'];
	
	$startHour = substr($startTimeStamp,11,2 );
	$endHour  = substr($endTimeStamp,11,2 );
	$startMin = substr($startTimeStamp,13,2 );
        $endMin  = substr($endTimeStamp,13,2 );

	$name = $scheduleRow['name'];
	$shiftName = $scheduleRow['shiftName'];


	echo "<tr>";
	echo "<td>$shiftName</td>";
	for ($i = 0;$i < 7; $i++) {
	        //color the background
		echo "<td";
		if (($i > $startHour) && ($i < $endHour)) echo " class='red'>---</td> ";
		else echo ">&nbsp;</td>";


	}
        echo "<td>$name</td>";

    }
    echo "</tr>";
    echo "</table>";


    mysqli_close($db_master);
}*/

function returnSchedule () {
    
    require_once('../includes/mysqli_connect.php');
    mysqli_select_db($db_master, 'fannie') or die("Couldn't select database: " . mysqli_error($db_master));

//    $header = "Scheduling"; //top of center DIV
//    $page_title = "Fannie's Scheduling Tool"; //top of window
  //  include('../includes/header.html');

//    if (!function_exists("makeEmployeeSelector")) require_once('../includes/common.php');
  //  if (!function_exists()) require_once('../includes/common.php');
  //  if (!function_exists()) require_once('../includes/common.php');
echo "<style type='text/css'>

        .red {

            background-color: red;
        }

</style>";

    if (isset($_POST)) {
        foreach ($_POST as $key => $value) {
            ${$key} = $value;
        }
    } elseif ($_GET) {
        foreach ($_GET as $key => $value) {
            ${$key} = $value;
        }
    }

/*
    if ($submit == 'add') {
        
        $query = sprintf("INSERT INTO schedule (emp_no,shift_no,day) VALUES ('%s','%s','%s')",$emp_no, $shift_no, $day );

        $result = mysqli_query($db_master) or die ("Error from query -> $query : <br />" . mysqli_error($db_master));

        $row = mysqli_fetch_row($result);
        
        //iterator
        //while ($row = mysqli_fetch_row($result)) {}
    }

    echo "<form>";
    echo "<input type='text' name='' value='' />";
    echo "<input type='text' name='' value='' />";
    echo "<input type='text' name='' value='' />";
    echo "</form>";
  */  
    //create the schedule

    //make the table
    echo "<table border=1>";
    //headers
    echo "<tr class='header_row'>";
    $i = 0;
    $maxhours = 17;
    echo "<td>shift</td>";
    for ($i = 0; $i < $maxhours; $i++) {
        $currHour = 7 + $i;

	if ($currHour > 12) $currHour -= 12;

        echo "<td>$currHour</td>";
        
    }
    echo "<td>who</td>";

//    echo "</tr>";
//    echo "</table>";
    //iterator for how many hours?
    $scheduleQuery = "select 
                        sch.day, 
                        sh.shift_name as shiftName, 
                        sh.start as start, 
                        sh.stop as end, 
                        e.FirstName as name from schedule as sch, 
                        emp_shifts as sh, 
                        emp_details as e 
                      where 
                        sch.shift_id = sh.shift_id 
                            and 
                        sch.emp_no = e.emp_no;";

    $scheduleResult = mysqli_query($db_master, $scheduleQuery);

    while ($scheduleRow = mysqli_fetch_array($scheduleResult, MYSQLI_ASSOC)) {

        //pull start and end times
        $startTimeStamp = $scheduleRow['start'];
        $endTimeStamp = $scheduleRow['end'];
	
        $startHour = substr($startTimeStamp,11,2 );
        $endHour  = substr($endTimeStamp,11,2 );
        $startMin = substr($startTimeStamp,13,2 );
        $endMin  = substr($endTimeStamp,13,2 );

        $name = $scheduleRow['name'];
        $shiftName = $scheduleRow['shiftName'];


        echo "<tr>";
        echo "<td>$shiftName</td>";
        //loop through hours once per row to create the shaded sections.
        for ($i = 0;$i < $maxhours; $i++) {
                //color the background
            echo "<td";
            if (($i > $startHour) && ($i < $endHour)) echo " class='red'>---</td> ";
            else echo ">&nbsp;</td>";
        }
        echo "<td>$name</td>";

    }
    echo "</tr>";
    echo "</table>";


    mysqli_close($db_master);
}
//if (isset($_POST['']) 
    returnSchedule();
    //include('../includes/footer.html');
?>
