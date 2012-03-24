<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);


require_once('../includes/mysqli_connect.php');
global $db_master;


function printScheduleFor($emp_no = NULL) {

//require_once('../includes/mysqli_connect.php');
  //  global $db_master;

$db_master = mysqli_connect('localhost','steve','st3v3','fannie') or die("Connect error");

    //initialize the output var to nothing.
    $output = "";

    //check to see if there was an emp_no submitted
    //if (!$emp_no) {
    if (1) {

        //create the query, pairing down from the schudule table.
        $shiftsQ = "SELECT 
          s.emp_no, 
          s.shift_id, 
          s.day, 
          es.shift_name, 
          es.area, 
          es.start, 
          es.stop 
        FROM 
          schedule as s 
        INNER JOIN 
          emp_shifts as es 
        ON 
          s.shift_id = es.shift_id 
        WHERE 
          s.emp_no = $emp_no";

//echo $shiftsQ;
        $result = mysqli_query($db_master, $shiftsQ) or die("Shift Query Error: " . mysqli_error($db_master));

        $output .= "<p>Schedule for Employee #: $emp_no</p>";

        $output .= "<table id='schedule_table'><tr>
        <th>Shift</th>
        <th>Area</th>
        <th>Start Time</th>
        <th>Stop Time</th>
        <th>Day</th>
        </tr>";

        while ($row = mysqli_fetch_row($result)) {

               $output .= "<tr>
               <td>$row[3]</td>
               <td>$row[4]</td>
               <td>$row[5]</td>
               <td>$row[6]</td>
               <td>$row[2]</td>
               </tr>";
        }
    
        $output .= "</table>";

        echo $output;
    } else return "<div>Error Retrieving </div>";
}
printScheduleFor(7049);
printScheduleFor(7050);
printScheduleFor(7045);
printScheduleFor(7044);
printScheduleFor(7046);
printScheduleFor(7048);
printScheduleFor(7047);
?>
