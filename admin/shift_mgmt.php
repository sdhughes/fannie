<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
/*********
 *
 * Tool to Manage Various shifts
 *
 * author:  steve hughes
 * date:    sept 2011
 *
 *  This file is protected under the GPL.
 *
 *********/


    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');
    global $db_master;

    $header = "Fannie's Shift Management Tool"; //top of center DIV
    $page_title = "Shift Management"; //top of window
    include('../includes/header.html');
 
  ?>
  <!--       <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
         <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
         <link rel="STYLESHEET" type="text/css" href="../includes/javascript/timepicker/jquery-ui-timepicker.css" />
         <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
         <script type="text/javascript" src="../includes/javascript/ui.core.js"></script> -->
         <link rel="STYLESHEET" type="text/css" href="../includes/javascript/timepicker/jquery-ui-timepicker.css" />
         <link rel="STYLESHEET" type="text/css" href="../includes/javascript/timepicker/include/jquery-ui-1.8.14.custom.css" />
         <script type="text/javascript" src="../includes/javascript/timepicker/include/jquery-1.5.1.min.js"></script>
         <script type="text/javascript" src="../includes/javascript/timepicker/include/jquery.ui.core.min.js"></script>
         <script type="text/javascript" src="../includes/javascript/timepicker/include/jquery.ui.position.min.js"></script>
         <script type="text/javascript" src="../includes/javascript/timepicker/include/jquery.ui.widget.min.js"></script>
         <script type="text/javascript" src="../includes/javascript/timepicker/include/jquery.ui.tabs.min.js"></script>
         <script type="text/javascript" src="../includes/javascript/timepicker/jquery.ui.timepicker.js"></script>
         <script type="text/javascript">
            $(document).ready(function() {
                $('.timepick').timepicker();
            });

          //  alert('jquery works');
         </script>

    <?php

    //echo "";
    
    //  echo date('H:i:s') . "<br/>";

    if (isset($_POST)) {
        foreach ($_POST AS $key => $value) {
            $$key = $value;
        }

    } elseif (isset($_GET)) {
        foreach ($_GET AS $key => $value) {
            $$key = $value;
        }
    }

    if (isset($_POST['submit']) && ($_POST['submit'] == 'add shift')) {

        if ($start_time > $end_time) {
            echo "<p class='red'>Could not insert. Please choose normal hours.</p>";
        } else {
            $insertQ = sprintf("INSERT INTO fannie.emp_shifts (shift_name,area,start,stop) VALUES ('%s',%s,'%s','%s')",$shift_name,$area, '0000-00-00 ' . $start_time, '0000-00-00 ' . $end_time );
            mysqli_query($db_master, $insertQ) or die("INSERT error ($insertQ) " . mysqli_error($db_master));
        }
        

    } elseif (isset($_POST['submit']) && ($_POST['submit'] == 'schedule')) {


$newShiftQ = sprintf("INSERT INTO fannie.schedule (emp_no,shift_id,day) VALUES ('%s','%s','%s')", $emp_selector, $shift_selection,$day_selection);

mysqli_query($db_master, $newShiftQ) or die("Error Inserting into Schedule: " . mysqli_error($db_master));
echo "<p class='red'>New Shift Scheduled</p>";
}




    $query = "SELECT s.shift_id as shift_id, s.shift_name as shift_name ,i.ShiftName as area, substr(start,12,8) as start, substr(stop,12,8) as stop FROM fannie.emp_shifts as s INNER JOIN is4c_log.shifts as i WHERE s.area = i.ShiftID";

    $result = mysqli_query($db_master,$query) or die ("Error from query -> $query : <br />" . mysqli_error($db_master));

    $shiftCount = mysqli_num_rows($result);

    //$row = mysqli_fetch_row($result);
        echo "<table border=1>
        <tr>
        <th>Shift ID</th>
        <th>Shift Name</th>
        <th>Area</th>
        <th>Shift Start</th>
        <th>Shift End</th>
        </tr>";
    
    //iterator
    while ($row = mysqli_fetch_row($result)) {
        
        echo "
        <tr>
        <td>" . $row[0] . "</td>
        <td>" . $row[1] . "</td>
        <td>" . $row[2] . "</td>
        <td>" . $row[3] . "</td>
        <td>" . $row[4] . "</td>
        </tr>
        ";    
        
    }
echo "</table>";
echo "<p class='shift_mgmt_header'>Add a new shift.</p>Enter times in 24-hour format (HH:MM:SS)";
echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';


$shiftQ = 'SELECT * FROM is4c_log.shifts;';

$shiftR = mysqli_query($db_master,$shiftQ) or die("Shift Query: " . mysqli_error($db_master));

echo "<table id='emp_mgmt_add_form' ><tr>";

echo "<td><input type='text' name='shift_name' value='' class='' /></td>";

echo "<td><select name='area'>";
while ($row = mysqli_fetch_array($shiftR,MYSQLI_ASSOC)) {
    echo "        <option value=". $row['ShiftID'] . " > " . $row['ShiftName'] . "</option>";
}

echo "</select></td>";
echo '<td><input type="text" name="start_time" value="" class="timepick" /></td>
    <td><input type="text" name="end_time" value="" class="timepick" /></td>
    <td><input type="submit" name="submit" value="add shift" class="" /></td></tr>
    </table>
</form>';


$scheduleQ = "SELECT CONCAT(e.FirstName,' ',e.LastName) as Name, sh.shift_name, sch.day FROM fannie.schedule as sch INNER JOIN fannie.emp_shifts as sh INNER JOIN is4c_op.employees as e WHERE sch.shift_id = sh.shift_id AND sch.emp_no = e.emp_no;";

$scheduleR = mysqli_query($db_master,$scheduleQ) or die("Schedule Query: " . mysqli_error($db_master));

echo "<p>Scheduled shifts: </p><table border=1>";
echo "<tr><td>Employee</td><td>Shift</td><td>Day</td></tr>";
while ($row = mysqli_fetch_row($scheduleR)) {


echo "<tr>";
echo "<td>$row[0]</td>";
echo "<td>$row[1]</td>";
echo "<td>$row[2]</td>";
echo "</tr>";

}
echo "</table>";

echo "<form method=post action='./shift_mgmt.php'>";
echo "<table> <tr>";

//echo "<td>Employee</td>";
//echo "<td>Shift</td>";
//echo "<td>Day</td></tr>";
//echo "<tr>";
echo "<td>";
makeEmployeeSelector();
echo "</td>";
echo "<td>";
echo "<select name='shift_selection' >";
$i = 0;
while ( $i < $shiftCount) {
    echo "<option value=$i>$i</option>";
    $i++;
}
echo "</select></td>";
echo "<td>";
echo "<select name='day_selection' >";
    echo "<option value=1>Sunday</option>";
    echo "<option value=2>Monday</option>";
    echo "<option value=3>Tuesday</option>";
    echo "<option value=4>Wednesday</option>";
    echo "<option value=5>Thursday</option>";
    echo "<option value=6>Friday</option>";
    echo "<option value=7>Saturday</option>";
echo "</select></td></tr></table>";

echo "<input type=submit name=submit value='schedule' />";



    mysqli_close($db_master);
    include('../includes/footer.html');
?>
